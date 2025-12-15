<?php
/**
 * Bulk download functionality for media library.
 *
 * @package Better_Media_Manager
 */

namespace Better_Media_Manager;

/**
 * Handles bulk download of media files from WordPress media library.
 */
class Bulk_Download {

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
	 * Add "Download" option to Media Library bulk actions dropdown.
	 *
	 * @param array $bulk_actions Existing bulk actions.
	 * @return array Modified bulk actions.
	 */
	public function register_bulk_action( $bulk_actions ) {
		$bulk_actions['download'] = __( 'Download', 'better-media-manager' );
		return $bulk_actions;
	}

	/**
	 * Handle bulk download action.
	 *
	 * @param string $redirect_to The redirect URL.
	 * @param string $action      The action being taken.
	 * @param array  $post_ids    The items to take the action on.
	 * @return string The redirect URL (or exits if download initiated).
	 */
	public function handle_bulk_download( $redirect_to, $action, $post_ids ) {
		// Only process if this is our action (List view) or bulk_download (Grid view).
		if ( $action !== 'download' && $action !== 'bulk_download' ) {
			return $redirect_to;
		}

		// For Grid view, get IDs from $_POST['media'] instead.
		if ( $action === 'bulk_download' && ! empty( $_POST['media'] ) ) {
			$post_ids = array_map( 'intval', $_POST['media'] );
		}

		// Verify user has permission to upload files (required for media library access).
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have permission to download files.', 'better-media-manager' ) );
		}

		// Validate that we have items to download.
		if ( empty( $post_ids ) ) {
			$redirect_to = add_query_arg( 'bmm_error', 'no_items', $redirect_to );
			return $redirect_to;
		}

		// Check if ZipArchive class is available.
		if ( ! class_exists( 'ZipArchive' ) ) {
			$redirect_to = add_query_arg( 'bmm_error', 'zip_not_available', $redirect_to );
			return $redirect_to;
		}

		// Create temporary ZIP file.
		$upload_dir   = wp_upload_dir();
		$zip_filename = 'bulk-download-' . time() . '-' . wp_generate_password( 8, false ) . '.zip';
		$zip_path     = $upload_dir['basedir'] . '/' . $zip_filename;

		// Initialize ZIP archive.
		$zip = new \ZipArchive();
		if ( $zip->open( $zip_path, \ZipArchive::CREATE ) !== true ) {
			$redirect_to = add_query_arg( 'bmm_error', 'zip_creation_failed', $redirect_to );
			return $redirect_to;
		}

		// Track statistics.
		$added_count   = 0;
		$skipped_count = 0;

		// Add each selected file to the ZIP.
		foreach ( $post_ids as $post_id ) {
			$file_path = get_attached_file( $post_id );

			// Skip if file doesn't exist.
			if ( ! $file_path || ! file_exists( $file_path ) ) {
				$skipped_count++;
				continue;
			}

			// Get filename with attachment ID prefix to avoid conflicts.
			$filename = $post_id . '-' . basename( $file_path );

			// Add file to ZIP.
			if ( $zip->addFile( $file_path, $filename ) ) {
				$added_count++;
			} else {
				$skipped_count++;
			}
		}

		// Close the ZIP archive.
		$zip->close();

		// If no files were added, delete the ZIP and show error.
		if ( $added_count === 0 ) {
			unlink( $zip_path );
			$redirect_to = add_query_arg( 'bmm_error', 'no_files_added', $redirect_to );
			return $redirect_to;
		}

		// Set headers for file download.
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="media-library-download-' . gmdate( 'Y-m-d-His' ) . '.zip"' );
		header( 'Content-Length: ' . filesize( $zip_path ) );
		header( 'Pragma: public' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );

		// Stream the file to browser.
		readfile( $zip_path );

		// Delete temporary ZIP file.
		unlink( $zip_path );

		// Exit to prevent WordPress from continuing with normal redirect.
		exit;
	}

	/**
	 * Handle direct POST requests from Grid view.
	 */
	public function handle_grid_download() {
		// Check if this is a grid view download request.
		if ( empty( $_POST['action'] ) || $_POST['action'] !== 'bulk_download' ) {
			return;
		}

		// Verify nonce.
		check_admin_referer( 'bulk-attachments' );

		// Get the redirect URL and post IDs.
		$redirect_to = admin_url( 'upload.php?mode=grid' );
		$post_ids    = ! empty( $_POST['media'] ) ? array_map( 'intval', $_POST['media'] ) : array();

		// Call the main download handler.
		$this->handle_bulk_download( $redirect_to, 'bulk_download', $post_ids );
	}

	/**
	 * Enqueue JavaScript for Grid view support.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_grid_scripts( $hook ) {
		// Only load on media library page.
		if ( $hook !== 'upload.php' ) {
			return;
		}

		// Inline script to add Download button to Grid view.
		$script = "
		jQuery(document).ready(function($) {
			// Wait for media grid to load
			var checkInterval = setInterval(function() {
				var bulkSelect = $('.media-toolbar-secondary .media-button[class*=\"select-mode\"]');
				if (bulkSelect.length && !$('#bmm-download-button').length) {
					// Create download button
					var downloadBtn = $('<button type=\"button\" id=\"bmm-download-button\" class=\"button media-button\" style=\"display:none; margin-left:10px;\">' + 
						'" . esc_js( __( 'Download Selected', 'better-media-manager' ) ) . "' +
						'</button>');
					
					// Add button after bulk select button
					bulkSelect.after(downloadBtn);
					
					// Show/hide download button based on selections
					wp.media.frame.on('selection:toggle', function() {
						var selected = wp.media.frame.state().get('selection');
						if (selected && selected.length > 0) {
							downloadBtn.show();
						} else {
							downloadBtn.hide();
						}
					});
					
					// Handle download button click
					downloadBtn.on('click', function(e) {
						e.preventDefault();
						var selected = wp.media.frame.state().get('selection');
						if (!selected || selected.length === 0) {
							alert('" . esc_js( __( 'Please select images to download.', 'better-media-manager' ) ) . "');
							return;
						}
						
						// Get all selected attachment IDs
						var ids = selected.map(function(attachment) {
							return attachment.id;
						});
						
						// Create form and submit
						var form = $('<form>', {
							method: 'POST',
							action: '" . esc_url( admin_url( 'upload.php' ) ) . "'
						});
						form.append($('<input>', {type: 'hidden', name: 'action', value: 'bulk_download'}));
						form.append($('<input>', {type: 'hidden', name: 'action2', value: '-1'}));
						form.append($('<input>', {type: 'hidden', name: '_wpnonce', value: '" . wp_create_nonce( 'bulk-attachments' ) . "'}));
						
						// Add each ID as media[] parameter
						$.each(ids, function(i, id) {
							form.append($('<input>', {type: 'hidden', name: 'media[]', value: id}));
						});
						
						// Submit form
						$('body').append(form);
						form.submit();
					});
					
					clearInterval(checkInterval);
				}
			}, 500);
		});
		";

		wp_add_inline_script( 'media-views', $script );
	}

	/**
	 * Display admin notices for errors.
	 */
	public function admin_notices() {
		// Check for error parameter.
		if ( empty( $_GET['bmm_error'] ) ) {
			return;
		}

		$error   = sanitize_text_field( wp_unslash( $_GET['bmm_error'] ) );
		$message = '';

		switch ( $error ) {
			case 'no_items':
				$message = __( 'No items selected for download.', 'better-media-manager' );
				break;
			case 'zip_not_available':
				$message = __( 'ZIP archive functionality is not available on this server. Please contact your hosting provider.', 'better-media-manager' );
				break;
			case 'zip_creation_failed':
				$message = __( 'Failed to create ZIP file. Please check file permissions.', 'better-media-manager' );
				break;
			case 'no_files_added':
				$message = __( 'No valid files could be added to the download. The selected items may not exist or are inaccessible.', 'better-media-manager' );
				break;
			default:
				$message = __( 'An unknown error occurred during download.', 'better-media-manager' );
		}

		if ( $message ) {
			printf(
				'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
				esc_html( $message )
			);
		}
	}
}
