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

### �️ File Type Filtering
- **Smart Filter Dropdown**: Filter media library by file extension (JPG, PNG, WebP, PDF, etc.)
- **Real-Time Counts**: Shows number of files for each type
- **Universal Support**: Works in both List and Grid views, including media modal popups
- **Auto-Detection**: Automatically discovers file types in your media library
- **Cached Performance**: Intelligent caching with automatic refresh on upload/delete
### 🏷️ Category Management
- **Custom Taxonomy**: Organize media files with custom categories
- **Multi-Select Interface**: Add multiple categories to any media file using Select2
- **Create On-The-Fly**: Create new categories directly from the attachment editor
- **Smart Filtering**: Filter media library by category in List and Grid views
- **Accurate Counts**: Real-time category counts that understand attachment post status
- **Instant Save**: Categories save automatically when changed
### �🔒 Security & Best Practices
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

### File Type Filtering

1. Go to **Media Library** (List, Grid, or media modal in post editor)
2. Find the **File Type** dropdown in the toolbar
3. Select a file extension to filter (e.g., "PNG (45)" shows only PNG files)
4. Filter instantly updates - select "All file types" to show everything
5. Counts update automatically when you upload or delete files

### Category Management

1. Open any attachment in the **Media Library** (click to edit or use the attachment details modal)
2. Find the **Category** field with a multi-select dropdown
3. Select existing categories or type to create new ones (press Enter/comma to add)
4. Categories save automatically when changed
5. Use the **Category** dropdown in the media library toolbar to filter by category
6. Category counts show the number of attachments in each category

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
│   ├── class-media-taxonomy.php   # Category taxonomy management
│   └── class-bulk-download.php    # Bulk download functionality
├── admin/                          # Admin-specific functionality
│   ├── class-admin.php            # Admin menu and pages
│   ├── class-settings.php         # Settings API integration
│   ├── class-ajax-handler.php     # AJAX request handlers
│   ├── class-media-filter.php     # File type filtering
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

**Q: What file types can I filter by?**  
A: The filter automatically detects file extensions in your media library, supporting common formats like JPG, PNG, WebP, GIF, SVG, PDF, MP4, and many more (20+ types).

**Q: Does the filter work in the post/page editor's "Add Media" modal?**  
A: Yes! The file type filter works everywhere - List view, Grid view, and all media modal popups throughout WordPress.

**Q: Can I add categories to multiple attachments at once?**  
A: Currently, categories must be added individually to each attachment. Future versions may include bulk category assignment.

**Q: Are categories searchable?**  
A: Yes! Categories use WordPress's standard taxonomy system, so they're searchable and can be queried using standard WordPress functions.

## Changelog

### 1.2.1 - 2025-12-28
- Added category search functionality to media library
- Categories are now searchable alongside attachment titles
- Search results include attachments with matching category names

### 1.2.0 - 2025-12-28
- Added custom category taxonomy for media files (bmm_media_category)
- Multi-select category interface with Select2 integration
- Create new categories on-the-fly from attachment editor
- Category filtering in List and Grid views
- Automatic category saving with AJAX
- Custom term count callback for accurate attachment counts
- Category filter works alongside file type filter

### 1.1.0 - 2025-12-22
- Added file type filtering to media library
- Filter by file extension in List view, Grid view, and media modals
- Shows file counts for each type (e.g., "PNG (45)")
- Automatic cache invalidation on upload/delete
- Performance optimizations with transient caching
- Fixed 26 WordPress coding standards violations (Yoda conditions)
- Improved translator comments for i18n compliance

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
