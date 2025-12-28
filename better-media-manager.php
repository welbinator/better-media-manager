<?php
/**
 * Plugin Name:       Better Media Manager
 * Plugin URI:        https://github.com/welbinator/better-media-manager
 * Description:       Complete media management solution: scrape images from websites, bulk download media files, and import directly to your WordPress media library with advanced processing options.
 * Version:           1.2.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            James Welbes
 * Author URI:        https://jameswelbes.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       better-media-manager
 * Domain Path:       /languages
 *
 * @package Better_Media_Manager
 */

namespace Better_Media_Manager;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Plugin version.
 */
define( 'BETTER_MEDIA_MANAGER_VERSION', '1.2.0' );

/**
 * Plugin directory path.
 */
define( 'BETTER_MEDIA_MANAGER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'BETTER_MEDIA_MANAGER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 */
define( 'BETTER_MEDIA_MANAGER_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader for plugin classes.
 *
 * @param string $class The fully-qualified class name.
 */
function better_media_manager_autoloader( $class ) {
	// Project-specific namespace prefix.
	$prefix = 'Better_Media_Manager\\';

	// Base directory for the namespace prefix.
	$base_dir = BETTER_MEDIA_MANAGER_PLUGIN_DIR . 'includes/';

	// Does the class use the namespace prefix?
	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	// Get the relative class name.
	$relative_class = substr( $class, $len );

	// Replace namespace separators with directory separators, and append .php.
	$file = $base_dir . 'class-' . strtolower( str_replace( '_', '-', $relative_class ) ) . '.php';

	// If the file exists, require it.
	if ( file_exists( $file ) ) {
		require $file;
	}
}

spl_autoload_register( __NAMESPACE__ . '\\better_media_manager_autoloader' );

/**
 * The code that runs during plugin activation.
 */
function activate_better_media_manager() {
	require_once BETTER_MEDIA_MANAGER_PLUGIN_DIR . 'includes/class-activator.php';
	Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_better_media_manager() {
	require_once BETTER_MEDIA_MANAGER_PLUGIN_DIR . 'includes/class-deactivator.php';
	Deactivator::deactivate();
}

register_activation_hook( __FILE__, __NAMESPACE__ . '\\activate_better_media_manager' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\deactivate_better_media_manager' );

/**
 * Initialize the plugin.
 */
function run_better_media_manager() {
	$plugin = new Core();
	$plugin->run();
}

run_better_media_manager();



function bmm_cleanup_all_categories() {
    // Get all terms in the taxonomy
    $terms = get_terms( array(
        'taxonomy'   => 'bmm_media_category',
        'hide_empty' => false,
        'fields'     => 'ids',
    ) );
    
    if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
        // Get all attachments
        $attachments = get_posts( array(
            'post_type'      => 'attachment',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ) );
        
        // Remove all terms from all attachments
        foreach ( $attachments as $attachment_id ) {
            wp_set_object_terms( $attachment_id, array(), 'bmm_media_category' );
            clean_object_term_cache( $attachment_id, 'attachment' );
        }
        
        // Delete all terms
        foreach ( $terms as $term_id ) {
            wp_delete_term( $term_id, 'bmm_media_category' );
        }
        
        // Clear all caches
        delete_option( 'bmm_media_category_children' );
        clean_taxonomy_cache( 'bmm_media_category' );
    }
    
    echo 'All categories and associations cleaned up!';
}

// Run once on any admin page load
add_action( 'admin_init', function() {
    if ( isset( $_GET['bmm_cleanup'] ) ) {
        bmm_cleanup_all_categories();
        wp_die( 'Cleanup complete! Remove this code from functions.php now.' );
    }
});