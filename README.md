# Better Media Manager

A comprehensive WordPress media management solution that combines powerful image scraping capabilities with bulk download functionality for your media library.

## Features

### 🖼️ Image Scraper
- **Dual Scraping Methods**: Choose between Simple Mode (free, direct HTML) or Firecrawl API (advanced, JavaScript-heavy sites)
- **Automatic Image Import**: Import scraped images directly to WordPress media library
- **Per-Image Customization**: Edit individual settings for each image (filename, alt text, title, format, dimensions)
- **Bulk Options**: Apply global settings to all images or customize each one individually
- **Image Processing**: Convert formats (WebP, JPEG, PNG), resize to max width, compress to max file size
- **Selective Import**: Choose which images to import with checkboxes
- **CSS Class Targeting**: Optionally scrape only images with specific CSS classes

### 📦 Bulk Download
- **One-Click Download**: Select multiple media files and download them as a ZIP archive
- **List & Grid View Support**: Works seamlessly in both WordPress media library views
- **Smart Naming**: Files are prefixed with attachment IDs to prevent conflicts
- **Automatic Cleanup**: Temporary ZIP files are automatically removed after download

### 🔒 Security & Best Practices
- WordPress security standards (nonces, sanitization, escaping)
- Clean object-oriented architecture
- Proper namespacing and autoloading
- Responsive admin interface

## Installation

1. Upload the `better-media-manager` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to "Better Media Manager" in the admin menu
4. Configure settings if needed (optional API key for advanced scraping)

## Usage

### Bulk Download

1. Go to **Media Library** (either List or Grid view)
2. Select the files you want to download
3. Choose **Download** from the bulk actions dropdown
4. Click **Apply** - your ZIP file will download automatically

### Image Scraper

#### Basic Workflow

1. Navigate to **Better Media Manager** → **Image Scraper**
2. Enter the URL of the webpage containing images
3. Optional: Target specific CSS class to scrape only certain images
4. Click **Start Scraping** to fetch images
5. Review preview and customize images as needed
6. Select images to import with checkboxes
7. Configure processing options (format, size, alt text, etc.)
8. Click **Add to Media Library**

#### Scraping Methods

**Simple Mode (Recommended)**
- ✅ Free - no API costs
- ✅ Fast - direct HTTP requests
- ✅ No API key required
- ✅ Works for most standard websites
- ❌ Cannot handle JavaScript-rendered content

**Firecrawl API Mode**
- ✅ Handles JavaScript-heavy sites (React, Vue, Angular)
- ✅ Bypasses anti-bot protections
- ✅ Works with Single Page Applications
- ✅ More reliable for protected content
- ❌ Requires API key from [firecrawl.dev](https://firecrawl.dev)
- ❌ Costs money (API credits)

## Configuration

### Settings Page

Navigate to **Better Media Manager** → **Settings**

#### Better Media Manager Section
- Information about bulk download feature
- No configuration required - works out of the box

#### Image Scraper Section
- **Scraping Method**: Choose between Simple Mode or Firecrawl API
- **Firecrawl API Key**: Enter your API key (only needed for Firecrawl mode)
- **Maximum Images Per Scrape**: Limit images per request (1-500)
- **Request Timeout**: Set timeout for API requests (5-300 seconds)

## Technical Details

### Requirements
- WordPress 5.8 or higher
- PHP 7.4 or higher
- ZipArchive PHP extension (for bulk download)

### Plugin Architecture

```
better-media-manager/
├── better-media-manager.php       # Main plugin file (bootstrap)
├── includes/                       # Core plugin classes
│   ├── class-core.php             # Main orchestrator
│   ├── class-loader.php           # Hooks/filters manager
│   ├── class-activator.php        # Activation hooks
│   ├── class-deactivator.php      # Deactivation hooks
│   ├── class-i18n.php             # Internationalization
│   ├── class-firecrawl-api.php    # Firecrawl API integration
│   ├── class-html-scraper.php     # Simple Mode HTML scraper
│   ├── class-media-importer.php   # Image processing & import
│   └── class-bulk-download.php    # Bulk download functionality
├── admin/                          # Admin-specific functionality
│   ├── class-admin.php            # Admin menu and pages
│   ├── class-settings.php         # Settings API integration
│   ├── class-ajax-handler.php     # AJAX request handlers
│   ├── css/
│   │   └── admin.css              # Admin styles
│   ├── js/
│   │   └── admin.js               # Admin JavaScript (AJAX)
│   └── partials/                  # View templates
│       ├── settings-display.php   # Settings page UI
│       └── scraper-display.php    # Main scraper page UI
```

### Namespace
All classes use the `Better_Media_Manager` namespace with proper autoloading.

### WordPress Coding Standards
- Follows WordPress PHP Coding Standards
- Proper sanitization, validation, and escaping
- Nonce verification for all forms and AJAX requests
- Capability checks for security

## Frequently Asked Questions

**Q: Do I need an API key to use this plugin?**  
A: No! The Simple Mode works without any API key and is suitable for most websites. You only need an API key if you want to use the Firecrawl API for advanced scraping of JavaScript-heavy sites.

**Q: What happens if the ZIP extension isn't available?**  
A: The bulk download feature requires the ZipArchive PHP extension. If it's not available, you'll see an error message. Contact your hosting provider to enable it.

**Q: Can I customize individual images before importing?**  
A: Yes! Click "Edit Settings" on any image in the preview to set custom filename, alt text, title, format, and dimensions for that specific image.

**Q: What image formats are supported?**  
A: You can convert images to WebP, JPEG, or PNG during import. The plugin also supports importing SVG and GIF files.

**Q: Is there a limit on how many images I can download at once?**  
A: The bulk download has no hard limit, but very large ZIP files may be limited by your server's memory and execution time settings.

**Q: Will this plugin slow down my site?**  
A: No. All functionality is admin-only and doesn't affect your site's frontend performance.

## Changelog

### 1.0.0 - 2025-12-15
- Initial release combining Image Scraper and Bulk Image Download
- Unified admin interface with single settings page
- Dual scraping modes (Simple Mode + Firecrawl API)
- Bulk download support for List and Grid views
- Per-image customization for scraped images
- Advanced image processing (format conversion, resizing, compression)

## Credits

**Author**: James Welbes  
**Website**: [jameswelbes.com](https://jameswelbes.com)  
**License**: GPL v2 or later

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/welbinator/better-media-manager).
