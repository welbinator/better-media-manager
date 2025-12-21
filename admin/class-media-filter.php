<?php
/**
 * Media library filtering functionality.
 *
 * @package Better_Media_Manager
 */

namespace Better_Media_Manager\Admin;

/**
 * Handles filtering media library by file type.
 */
class Media_Filter {

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
	 * Add file type filter dropdown to media library.
	 */
	public function add_filetype_filter() {
		$screen = get_current_screen();

		// Only show on media library page.
		if ( ! $screen || 'upload' !== $screen->id ) {
			return;
		}

		// Get available file types from the database.
		$file_types = $this->get_available_file_types();

		// Get current filter value.
		$current_filetype = isset( $_GET['bmm_filetype'] ) ? sanitize_text_field( wp_unslash( $_GET['bmm_filetype'] ) ) : '';

		// Output the dropdown.
		?>
		<select name="bmm_filetype" id="bmm_filetype">
			<option value=""><?php esc_html_e( 'All file types', 'better-media-manager' ); ?></option>
			<?php foreach ( $file_types as $extension => $data ) : ?>
				<option value="<?php echo esc_attr( $extension ); ?>" <?php selected( $current_filetype, $extension ); ?>>
					<?php
					/* translators: 1: file extension (uppercase), 2: count of files */
					printf(
						esc_html__( '%1$s (%2$d)', 'better-media-manager' ),
						esc_html( strtoupper( $extension ) ),
						absint( $data['count'] )
					);
					?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Filter media library query by file type (List view).
	 *
	 * @param WP_Query $query The WordPress query object.
	 */
	public function filter_media_by_filetype( $query ) {
		global $pagenow;

		// Only filter on media library page.
		if ( ! is_admin() || 'upload.php' !== $pagenow ) {
			return;
		}

		// Check if we're on the main query.
		if ( ! $query->is_main_query() ) {
			return;
		}

		// Check if filter is applied.
		if ( empty( $_GET['bmm_filetype'] ) ) {
			return;
		}

		$filetype = sanitize_text_field( wp_unslash( $_GET['bmm_filetype'] ) );

		// Modify the query to filter by file extension.
		$meta_query = $query->get( 'meta_query' );
		if ( ! is_array( $meta_query ) ) {
			$meta_query = array();
		}

		$meta_query[] = array(
			'key'     => '_wp_attached_file',
			'value'   => '.' . $filetype,
			'compare' => 'LIKE',
		);

		$query->set( 'meta_query', $meta_query );
	}

	/**
	 * Filter AJAX query for grid view.
	 *
	 * @param array $query Query variables.
	 * @return array Modified query variables.
	 */
	public function filter_ajax_query_attachments( $query ) {
		// Check if filter is applied in AJAX request.
		if ( empty( $_REQUEST['query']['bmm_filetype'] ) ) {
			return $query;
		}

		$filetype = sanitize_text_field( wp_unslash( $_REQUEST['query']['bmm_filetype'] ) );

		// Add meta query to filter by file extension.
		if ( ! isset( $query['meta_query'] ) ) {
			$query['meta_query'] = array();
		}

		$query['meta_query'][] = array(
			'key'     => '_wp_attached_file',
			'value'   => '.' . $filetype,
			'compare' => 'LIKE',
		);

		return $query;
	}

	/**
	 * Add custom filter template for grid view.
	 */
	public function print_media_templates() {
		// Allow template on any admin page that might use media modal
		if ( ! is_admin() ) {
			return;
		}

		$file_types = $this->get_available_file_types();
		?>
		<script type="text/html" id="tmpl-bmm-filetype-filter">
			<label for="bmm-filetype-filter" class="screen-reader-text"><?php esc_html_e( 'Filter by file type', 'better-media-manager' ); ?></label><select class="attachment-filters" id="bmm-filetype-filter" data-setting="bmm_filetype">
				<option value=""><?php esc_html_e( 'All file types', 'better-media-manager' ); ?></option>
				<?php foreach ( $file_types as $extension => $data ) : ?>
					<option value="<?php echo esc_attr( $extension ); ?>">
						<?php
						/* translators: 1: file extension (uppercase), 2: count of files */
						printf(
							esc_html__( '%1$s (%2$d)', 'better-media-manager' ),
							esc_html( strtoupper( $extension ) ),
							absint( $data['count'] )
						);
						?>
					</option>
				<?php endforeach; ?>
			</select>
		</script>
		<?php
	}

	/**
	 * Enqueue styles for media library page.
	 */
	public function enqueue_media_styles() {
		// Allow styles on any admin page that might use media modal
		if ( ! is_admin() ) {
			return;
		}

		wp_enqueue_style(
			'bmm-media-filter',
			BETTER_MEDIA_MANAGER_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Enqueue scripts for grid view filter.
	 */
	public function enqueue_media_scripts() {
		// Allow scripts on any admin page that might use media modal
		if ( ! is_admin() ) {
			return;
		}

		// Add inline script to media-views
		$script = "
		jQuery(document).ready(function($) {
			console.log('BMM Media Filter: Script loaded');
			
			if (typeof wp === 'undefined' || !wp.media || !wp.media.view) {
				console.log('BMM Media Filter: wp.media not available');
				return;
			}
			
			console.log('BMM Media Filter: Extending AttachmentsBrowser');
			
			// Store original AttachmentsBrowser
			var OriginalAttachmentsBrowser = wp.media.view.AttachmentsBrowser;
			
			// Extend AttachmentsBrowser
			wp.media.view.AttachmentsBrowser = OriginalAttachmentsBrowser.extend({
				createToolbar: function() {
					console.log('BMM Media Filter: createToolbar called');
					
					// Call parent
					OriginalAttachmentsBrowser.prototype.createToolbar.apply(this, arguments);
					
					var filters = this.options.filters;
					console.log('BMM Media Filter: filters =', filters);
					
if (filters === 'uploaded' || filters === 'all' || !filters) {
						// Get the template content
						var template = $('#tmpl-bmm-filetype-filter').html();
						console.log('BMM Media Filter: template found =', !!template);
						
						if (template) {
							// Insert template directly into toolbar without wrapper div
							var self = this;
							setTimeout(function() {
								var \$toolbar = self.toolbar.\$el.find('.media-toolbar-secondary');
								console.log('BMM Media Filter: toolbar found =', \$toolbar.length);
								
								var \$dateFilter = \$toolbar.find('#media-attachment-date-filters');
								console.log('BMM Media Filter: date filter found =', \$dateFilter.length);
								
								// Check if our filter already exists
								if ($('#bmm-filetype-filter').length > 0) {
									console.log('BMM Media Filter: Filter already exists, skipping');
									return;
								}
								
								if (\$dateFilter.length) {
									\$dateFilter.after(template);
									console.log('BMM Media Filter: Inserted after date filter');
								} else {
									\$toolbar.append(template);
									console.log('BMM Media Filter: Appended to toolbar');
								}
								
								console.log('BMM Media Filter: Filter HTML added');
								console.log('BMM Media Filter: Filter exists now?', $('#bmm-filetype-filter').length);
								
								// Bind change event
								$('#bmm-filetype-filter').on('change', function() {
									var val = $(this).val();
									console.log('BMM Media Filter: Filter changed to', val);
									if (self.collection && self.collection.props) {
										self.collection.props.set({bmm_filetype: val});
									}
								});
							}, 100);
						}
					}
				}
			});
			
			console.log('BMM Media Filter: Extension complete');
		});
		";

		wp_add_inline_script( 'media-views', $script );
	}

	/**
	 * Get available file types from media library.
	 *
	 * @return array Array of file types with counts.
	 */
	private function get_available_file_types() {
		global $wpdb;

		// Query to get all file extensions from attachments.
		$results = $wpdb->get_results(
			"SELECT SUBSTRING_INDEX(meta_value, '.', -1) as extension, COUNT(*) as count
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = '_wp_attached_file'
			AND p.post_type = 'attachment'
			GROUP BY extension
			ORDER BY count DESC",
			ARRAY_A
		);

		$file_types = array();
		if ( $results ) {
			foreach ( $results as $row ) {
				$extension = strtolower( sanitize_text_field( $row['extension'] ) );
				
				// Only include common image/media file types.
				if ( in_array( $extension, array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico', 'pdf', 'mp4', 'mov', 'avi', 'mp3', 'wav', 'zip', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx' ), true ) ) {
					$file_types[ $extension ] = array(
						'count' => absint( $row['count'] ),
					);
				}
			}
		}

		return $file_types;
	}
}
