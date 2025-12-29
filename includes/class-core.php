<?php
/**
 * The core plugin class.
 *
 * @package Better_Media_Manager
 */

namespace Better_Media_Manager;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 */
class Core {

	/**
	 * The loader that's responsible for maintaining and registering all hooks.
	 *
	 * @var Loader
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 */
	public function __construct() {
		$this->version     = BETTER_MEDIA_MANAGER_VERSION;
		$this->plugin_name = 'better-media-manager';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @return void
	 */
	private function load_dependencies(): void {
		require_once BETTER_MEDIA_MANAGER_PLUGIN_DIR . 'includes/class-loader.php';
		require_once BETTER_MEDIA_MANAGER_PLUGIN_DIR . 'includes/class-i18n.php';

		// Image Scraper functionality.
		require_once BETTER_MEDIA_MANAGER_PLUGIN_DIR . 'includes/class-firecrawl-api.php';
		require_once BETTER_MEDIA_MANAGER_PLUGIN_DIR . 'includes/class-html-scraper.php';
		require_once BETTER_MEDIA_MANAGER_PLUGIN_DIR . 'includes/class-media-importer.php';

		// Bulk Download functionality.
		require_once BETTER_MEDIA_MANAGER_PLUGIN_DIR . 'includes/class-bulk-download.php';

		// Media Taxonomy.
		require_once BETTER_MEDIA_MANAGER_PLUGIN_DIR . 'includes/class-media-taxonomy.php';

		// Admin classes.
		require_once BETTER_MEDIA_MANAGER_PLUGIN_DIR . 'admin/class-admin.php';
		require_once BETTER_MEDIA_MANAGER_PLUGIN_DIR . 'admin/class-settings.php';
		require_once BETTER_MEDIA_MANAGER_PLUGIN_DIR . 'admin/class-ajax-handler.php';
		require_once BETTER_MEDIA_MANAGER_PLUGIN_DIR . 'admin/class-media-filter.php';

		$this->loader = new Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @return void
	 */
	private function set_locale(): void {
		$plugin_i18n = new I18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality.
	 *
	 * @return void
	 */
	private function define_admin_hooks(): void {
		$plugin_admin = new Admin\Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );

		// Settings.
		$plugin_settings = new Admin\Settings( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_init', $plugin_settings, 'register_settings' );

		// Image Scraper AJAX handlers.
		$ajax_handler = new Admin\Ajax_Handler( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'wp_ajax_better_media_manager_scrape', $ajax_handler, 'handle_scrape' );
		$this->loader->add_action( 'wp_ajax_better_media_manager_import', $ajax_handler, 'handle_import' );
		$this->loader->add_action( 'wp_ajax_better_media_manager_validate_api', $ajax_handler, 'handle_validate_api' );

		// Bulk Download functionality.
		$bulk_download = new Bulk_Download( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_filter( 'bulk_actions-upload', $bulk_download, 'register_bulk_action' );
		$this->loader->add_filter( 'handle_bulk_actions-upload', $bulk_download, 'handle_bulk_download', 10, 3 );
		$this->loader->add_action( 'admin_init', $bulk_download, 'handle_grid_download' );
		$this->loader->add_action( 'admin_notices', $bulk_download, 'admin_notices' );
		$this->loader->add_action( 'admin_enqueue_scripts', $bulk_download, 'enqueue_grid_scripts' );

		// Media Taxonomy.
		$media_taxonomy = new Media_Taxonomy( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'init', $media_taxonomy, 'register_taxonomy' );
		$this->loader->add_filter( 'attachment_fields_to_edit', $media_taxonomy, 'add_attachment_category_field', 10, 2 );
		$this->loader->add_filter( 'attachment_fields_to_save', $media_taxonomy, 'save_attachment_category', 10, 2 );
		$this->loader->add_action( 'admin_enqueue_scripts', $media_taxonomy, 'enqueue_select2' );
		$this->loader->add_action( 'wp_ajax_bmm_save_attachment_categories', $media_taxonomy, 'ajax_save_attachment_categories' );
		$this->loader->add_action( 'wp_ajax_bmm_create_category', $media_taxonomy, 'ajax_create_category' );
		$this->loader->add_action( 'pre_get_posts', $media_taxonomy, 'search_media_by_category' );

		// Media Library Filtering.
		$media_filter = new Admin\Media_Filter( $this->get_plugin_name(), $this->get_version() );
		// List view filters.
		$this->loader->add_action( 'restrict_manage_posts', $media_filter, 'add_filetype_filter' );
		$this->loader->add_action( 'restrict_manage_posts', $media_filter, 'add_category_filter' );
		$this->loader->add_action( 'pre_get_posts', $media_filter, 'filter_media_by_filetype' );
		$this->loader->add_action( 'pre_get_posts', $media_filter, 'filter_media_by_category' );
		// Grid view filters.
		$this->loader->add_filter( 'ajax_query_attachments_args', $media_filter, 'filter_ajax_query_attachments' );
		$this->loader->add_action( 'print_media_templates', $media_filter, 'print_media_templates' );
		$this->loader->add_action( 'admin_enqueue_scripts', $media_filter, 'enqueue_media_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $media_filter, 'enqueue_media_scripts' );
		// Clear cache when attachments are added/deleted.
		$this->loader->add_action( 'add_attachment', $media_filter, 'clear_file_types_cache' );
		$this->loader->add_action( 'delete_attachment', $media_filter, 'clear_file_types_cache' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @return void
	 */
	public function run(): void {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it.
	 *
	 * @return string The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return string The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
