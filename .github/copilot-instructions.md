# Better Media Manager - AI Coding Instructions

## Architecture Overview

This is a **WordPress plugin** (not a theme) using a clean object-oriented architecture with custom autoloading. The plugin provides two main features:
1. **Image Scraper**: Scrape images from URLs (Simple HTML or Firecrawl API) and import to WordPress media library with processing options
2. **Bulk Download**: ZIP multiple media library files for download

### Core Components

- **Namespace**: All classes use `Better_Media_Manager\` namespace
- **Autoloader**: Custom PSR-0-style autoloader in main plugin file converts `Class_Name` → `class-class-name.php`
- **Hook Loader Pattern**: `class-loader.php` collects all actions/filters, then registers them via `run()` in `class-core.php`
- **Entry Point**: `better-media-manager.php` defines constants, registers autoloader, initializes `Core` class

### Directory Structure

```
includes/          # Core business logic (scrapers, importers, bulk download)
admin/             # Admin UI (menu, AJAX handlers, settings)
  partials/        # View templates (scraper-display.php, settings-display.php)
  css/            # Admin styles
  js/             # Admin JavaScript (AJAX interactions)
```

## Critical Patterns

### 1. Class Naming & File Locations

**File naming convention**: `class-{lowercase-with-hyphens}.php`
- `Firecrawl_Api` → `includes/class-firecrawl-api.php`
- `Admin\Settings` → `admin/class-settings.php` (Admin classes live in `admin/`, not `includes/`)

**Namespace rules**:
- Base namespace: `Better_Media_Manager`
- Admin classes: `Better_Media_Manager\Admin\`
- Autoloader handles base namespace classes in `includes/` only
- Admin classes require explicit `require_once` in `class-core.php`

### 2. Hook Registration Pattern

Never call `add_action()` directly in class constructors. Always use the Loader pattern:

```php
// In class-core.php define_admin_hooks():
$plugin_admin = new Admin\Admin( $this->get_plugin_name(), $this->get_version() );
$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
```

All hooks are registered in `Core::define_admin_hooks()`, then executed via `$this->loader->run()`.

### 3. AJAX Handler Pattern

AJAX handlers in `admin/class-ajax-handler.php` follow strict pattern:
1. Verify nonce: `check_ajax_referer( 'better_media_manager_nonce', 'nonce' )`
2. Check capability: `current_user_can( 'manage_options' )`
3. Sanitize inputs: `esc_url_raw()`, `sanitize_text_field()`, `wp_unslash()`
4. Return JSON: `wp_send_json_success()` or `wp_send_json_error()`

Register AJAX in `define_admin_hooks()`:
```php
$this->loader->add_action( 'wp_ajax_better_media_manager_scrape', $ajax_handler, 'handle_scrape' );
```

### 4. Settings Storage

Settings stored as single option: `better_media_manager_settings` (array)
- Accessed via `get_option( 'better_media_manager_settings', array() )`
- Keys: `scraping_method`, `firecrawl_api_key`, `timeout`, `max_images`
- Registered in `admin/class-settings.php` with sanitization callbacks

### 5. Dual Scraper Architecture

Two scraper implementations with identical interface (`scrape_url()` method):
- **Html_Scraper**: Direct `wp_remote_get()` + HTML parsing (Simple Mode, no API)
- **Firecrawl_Api**: External API for JavaScript-rendered sites (requires API key)

Method selection in AJAX handler based on settings; instantiate appropriate class dynamically.

## Development Workflows

### Adding a New Feature

1. Create class file: `includes/class-feature-name.php` or `admin/class-feature-name.php`
2. Add to namespace: `namespace Better_Media_Manager;` (or `Better_Media_Manager\Admin;`)
3. Load dependency in `Core::load_dependencies()` if admin class or not autoloaded
4. Register hooks in `Core::define_admin_hooks()` using loader pattern
5. Add AJAX endpoint if needed in `Ajax_Handler` class

### Adding Settings

1. Add field to `Settings::register_settings()` → `add_settings_field()`
2. Create render callback method: `render_field_name_field()`
3. Add sanitization in `Settings::sanitize_settings()`
4. Access in other classes: `$options = get_option( 'better_media_manager_settings' )`

### Testing Locally

This plugin requires WordPress environment (not standalone). Use:
- **Lando** (current setup based on workspace path `/home/highprrrr/lando/`)
- Local by Flywheel
- Docker with WordPress

Commands (if using Lando):
```bash
lando start
lando wp plugin activate better-media-manager
```

## Common Pitfalls

1. **Don't use `add_action()` directly** - always use Loader pattern via `Core` class
2. **Admin classes need explicit require** - they're not autoloaded; add to `load_dependencies()`
3. **Always escape output** - use `esc_html()`, `esc_url()`, `esc_attr()` in templates
4. **Sanitize all input** - `sanitize_text_field()`, `sanitize_url()`, `absint()`, etc.
5. **Check nonces in AJAX** - every AJAX handler must verify nonce first
6. **WP_Error for failures** - return `new \WP_Error( 'code', 'message' )` not exceptions
7. **Include admin files** - `media_handle_sideload()`, `wp_generate_attachment_metadata()` need explicit includes (see `Media_Importer::__construct()`)

## External Dependencies

- **Firecrawl API** (optional): https://api.firecrawl.dev/v1 - requires API key for JS-heavy site scraping
- **ZipArchive PHP extension**: Required for bulk download feature
- **WordPress functions**: Requires full WP environment; uses `wp_remote_get()`, media functions, etc.

## Key Files Reference

- [better-media-manager.php](better-media-manager.php) - Entry point, constants, autoloader
- [includes/class-core.php](includes/class-core.php) - Main plugin orchestration, hook registration
- [includes/class-loader.php](includes/class-loader.php) - Hook registration system
- [admin/class-ajax-handler.php](admin/class-ajax-handler.php) - All AJAX endpoint handlers
- [includes/class-media-importer.php](includes/class-media-importer.php) - Image processing & import logic
- [admin/js/admin.js](admin/js/admin.js) - Frontend AJAX interactions, UI updates
