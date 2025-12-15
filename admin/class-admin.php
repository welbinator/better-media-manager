<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package Better_Media_Manager
 */

namespace Better_Media_Manager\Admin;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for managing admin area.
 */
class Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		$screen = get_current_screen();

		// Only load on our plugin pages.
		if ( isset( $screen->id ) && false !== strpos( $screen->id, 'better-media-manager' ) ) {
			wp_enqueue_style(
				$this->plugin_name,
				BETTER_MEDIA_MANAGER_PLUGIN_URL . 'admin/css/admin.css',
				array(),
				$this->version,
				'all'
			);
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();

		// Only load on our plugin pages.
		if ( isset( $screen->id ) && false !== strpos( $screen->id, 'better-media-manager' ) ) {
			wp_enqueue_script(
				$this->plugin_name,
				BETTER_MEDIA_MANAGER_PLUGIN_URL . 'admin/js/admin.js',
				array( 'jquery' ),
				$this->version,
				false
			);

			// Pass data to JavaScript.
			wp_localize_script(
				$this->plugin_name,
				'betterMediaManagerAdmin',
				array(
					'ajax_url'          => admin_url( 'admin-ajax.php' ),
					'nonce'             => wp_create_nonce( 'better_media_manager_nonce' ),
					'media_library_url' => admin_url( 'upload.php' ),
				)
			);
		}
	}

	/**
	 * Register the administration menu for this plugin.
	 */
	public function add_plugin_admin_menu() {
		// Add main menu page.
		add_menu_page(
			__( 'Better Media Manager', 'better-media-manager' ),
			__( 'Better Media Manager', 'better-media-manager' ),
			'manage_options',
			'better-media-manager',
			array( $this, 'display_plugin_scraper_page' ),
			'dashicons-images-alt2',
			30
		);

		// Rename first submenu to "Image Scraper".
		add_submenu_page(
			'better-media-manager',
			__( 'Image Scraper', 'better-media-manager' ),
			__( 'Image Scraper', 'better-media-manager' ),
			'manage_options',
			'better-media-manager',
			array( $this, 'display_plugin_scraper_page' )
		);

		// Add settings submenu.
		add_submenu_page(
			'better-media-manager',
			__( 'Settings', 'better-media-manager' ),
			__( 'Settings', 'better-media-manager' ),
			'manage_options',
			'better-media-manager-settings',
			array( $this, 'display_plugin_settings_page' )
		);
	}

	/**
	 * Render the main scraper page.
	 */
	public function display_plugin_scraper_page() {
		require_once BETTER_MEDIA_MANAGER_PLUGIN_DIR . 'admin/partials/scraper-display.php';
	}

	/**
	 * Render the settings page for this plugin.
	 */
	public function display_plugin_settings_page() {
		require_once BETTER_MEDIA_MANAGER_PLUGIN_DIR . 'admin/partials/settings-display.php';
	}
}
