<?php
/**
 * Define the internationalization functionality.
 *
 * @package Better_Media_Manager
 */

namespace Better_Media_Manager;

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 */
class I18n {

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'better-media-manager',
			false,
			dirname( BETTER_MEDIA_MANAGER_BASENAME ) . '/languages/'
		);
	}
}
