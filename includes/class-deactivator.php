<?php
/**
 * Fired during plugin deactivation.
 *
 * @package Better_Media_Manager
 */

namespace Better_Media_Manager;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
		// Flush rewrite rules.
		flush_rewrite_rules();
	}
}
