<?php
/**
 * PHPStan bootstrap file for WordPress plugin analysis.
 *
 * @package Better_Media_Manager
 */

// Define WordPress constants that PHPStan needs.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../../../' );
}

if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}

if ( ! defined( 'BETTER_MEDIA_MANAGER_VERSION' ) ) {
	define( 'BETTER_MEDIA_MANAGER_VERSION', '1.0.0' );
}

if ( ! defined( 'BETTER_MEDIA_MANAGER_PLUGIN_DIR' ) ) {
	define( 'BETTER_MEDIA_MANAGER_PLUGIN_DIR', __DIR__ . '/' );
}

if ( ! defined( 'BETTER_MEDIA_MANAGER_PLUGIN_URL' ) ) {
	define( 'BETTER_MEDIA_MANAGER_PLUGIN_URL', 'http://example.com/wp-content/plugins/better-media-manager/' );
}

if ( ! defined( 'BETTER_MEDIA_MANAGER_BASENAME' ) ) {
	define( 'BETTER_MEDIA_MANAGER_BASENAME', 'better-media-manager/better-media-manager.php' );
}
