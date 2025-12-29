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
					/* translators: 1: file extension (uppercase), 2: count of files with that extension */
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
	 * Add category filter dropdown to media library.
	 */
	public function add_category_filter() {
		$screen = get_current_screen();

		// Only show on media library page.
		if ( ! $screen || 'upload' !== $screen->id ) {
			return;
		}

		// Get available categories.
		$categories = get_terms(
			array(
				'taxonomy'   => 'bmm_media_category',
				'hide_empty' => true,
			)
		);

		if ( is_wp_error( $categories ) || empty( $categories ) ) {
			return;
		}

		// Get current filter value.
		$current_category = isset( $_GET['bmm_media_category'] ) ? absint( $_GET['bmm_media_category'] ) : 0;

		// Output the dropdown.
		?>
		<select name="bmm_media_category" id="bmm_media_category">
			<option value=""><?php esc_html_e( 'All categories', 'better-media-manager' ); ?></option>
			<?php foreach ( $categories as $category ) : ?>
				<option value="<?php echo absint( $category->term_id ); ?>" <?php selected( $current_category, $category->term_id ); ?>>
					<?php
					/* translators: 1: category name, 2: count of media items in category */
					printf(
						esc_html__( '%1$s (%2$d)', 'better-media-manager' ),
						esc_html( $category->name ),
						absint( $category->count )
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
	 * @param \WP_Query $query The WordPress query object.
	 * @return void
	 */
	public function filter_media_by_filetype( $query ): void {
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
	 * Filter media library query by category (List view).
	 *
	 * @param \WP_Query $query The WordPress query object.
	 * @return void
	 */
	public function filter_media_by_category( $query ): void {
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
		if ( empty( $_GET['bmm_media_category'] ) ) {
			return;
		}

		$category_id = absint( $_GET['bmm_media_category'] );

		// Modify the query to filter by taxonomy.
		$tax_query = $query->get( 'tax_query' );
		if ( ! is_array( $tax_query ) ) {
			$tax_query = array();
		}

		$tax_query[] = array(
			'taxonomy' => 'bmm_media_category',
			'field'    => 'term_id',
			'terms'    => $category_id,
		);

		$query->set( 'tax_query', $tax_query );
	}

	/**
	 * Filter AJAX query for grid view.
	 *
	 * @param array $query Query variables.
	 * @return array Modified query variables.
	 */
	public function filter_ajax_query_attachments( $query ) {
		// Check if file type filter is applied in AJAX request.
		if ( ! empty( $_REQUEST['query']['bmm_filetype'] ) ) {
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
		}

		// Check if category filter is applied in AJAX request.
		if ( ! empty( $_REQUEST['query']['bmm_media_category'] ) ) {
			$category_id = absint( $_REQUEST['query']['bmm_media_category'] );

			// Remove the bmm_media_category from top-level query args (it shouldn't be there).
			if ( isset( $query['bmm_media_category'] ) ) {
				unset( $query['bmm_media_category'] );
			}

			// Add tax query to filter by category.
			if ( ! isset( $query['tax_query'] ) ) {
				$query['tax_query'] = array();
			}

			// Don't use 'relation' if there's only one condition.
			$query['tax_query'][] = array(
				'taxonomy' => 'bmm_media_category',
				'field'    => 'term_id',
				'terms'    => $category_id,
			);
		}

		return $query;
	}

	/**
	 * Add custom filter template for grid view.
	 *
	 * @return void
	 */
	public function print_media_templates(): void {
		// Allow template on any admin page that might use media modal.
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
						/* translators: 1: file extension (uppercase), 2: count of files with that extension */
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
		// Get available categories for filter.
		$categories = get_terms(
			array(
				'taxonomy'   => 'bmm_media_category',
				'hide_empty' => false,
			)
		);

		// Update term counts to ensure they're accurate.
		if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
			$term_ids = wp_list_pluck( $categories, 'term_id' );
			wp_update_term_count_now( $term_ids, 'bmm_media_category' );

			// Re-fetch to get updated counts.
			$categories = get_terms(
				array(
					'taxonomy'   => 'bmm_media_category',
					'hide_empty' => false,
				)
			);
		}

		if ( ! is_wp_error( $categories ) ) :
			?>
			<script type="text/html" id="tmpl-bmm-category-filter">
				<label for="bmm-category-filter" class="screen-reader-text"><?php esc_html_e( 'Filter by category', 'better-media-manager' ); ?></label><select class="attachment-filters" id="bmm-category-filter" data-setting="bmm_media_category">
					<option value=""><?php esc_html_e( 'All categories', 'better-media-manager' ); ?></option>
					<?php foreach ( $categories as $category ) : ?>
						<option value="<?php echo absint( $category->term_id ); ?>">
							<?php
							/* translators: 1: category name, 2: count of media items in category */
							printf(
								esc_html__( '%1$s (%2$d)', 'better-media-manager' ),
								esc_html( $category->name ),
								absint( $category->count )
							);
							?>
						</option>
					<?php endforeach; ?>
				</select>
			</script>
			<?php
		endif;
	}

	/**
	 * Enqueue styles for media library page.
	 *
	 * @return void
	 */
	public function enqueue_media_styles(): void {
		// Allow styles on any admin page that might use media modal.
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
	 *
	 * @return void
	 */
	public function enqueue_media_scripts(): void {
		// Allow scripts on any admin page that might use media modal.
		if ( ! is_admin() ) {
			return;
		}

		// Add inline script to media-views.
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
						var self = this;
						
						// Get the template content for file type filter
						var filetypeTemplate = $('#tmpl-bmm-filetype-filter').html();
						console.log('BMM Media Filter: filetype template found =', !!filetypeTemplate);
						
						// Get the template content for category filter
						var categoryTemplate = $('#tmpl-bmm-category-filter').html();
						console.log('BMM Media Filter: category template found =', !!categoryTemplate);
						
						setTimeout(function() {
							var \$toolbar = self.toolbar.\$el.find('.media-toolbar-secondary');
							console.log('BMM Media Filter: toolbar found =', \$toolbar.length);
							
							var \$dateFilter = \$toolbar.find('#media-attachment-date-filters');
							console.log('BMM Media Filter: date filter found =', \$dateFilter.length);
							
							// Add file type filter
							if (filetypeTemplate) {
								// Check if our filter already exists
								if ($('#bmm-filetype-filter').length === 0) {
									if (\$dateFilter.length) {
										\$dateFilter.after(filetypeTemplate);
										console.log('BMM Media Filter: Filetype filter inserted after date filter');
									} else {
										\$toolbar.append(filetypeTemplate);
										console.log('BMM Media Filter: Filetype filter appended to toolbar');
									}
									
									// Bind change event
									$('#bmm-filetype-filter').on('change', function() {
										var val = $(this).val();
										console.log('BMM Media Filter: Filetype filter changed to', val);
										if (self.collection && self.collection.props) {
											self.collection.props.set({bmm_filetype: val});
										}
									});
								}
							}
							
							// Add category filter
							if (categoryTemplate) {
								// Check if our filter already exists
								if ($('#bmm-category-filter').length === 0) {
									var \$filetypeFilter = \$toolbar.find('#bmm-filetype-filter');
									if (\$filetypeFilter.length) {
										\$filetypeFilter.after(categoryTemplate);
										console.log('BMM Media Filter: Category filter inserted after filetype filter');
									} else if (\$dateFilter.length) {
										\$dateFilter.after(categoryTemplate);
										console.log('BMM Media Filter: Category filter inserted after date filter');
									} else {
										\$toolbar.append(categoryTemplate);
										console.log('BMM Media Filter: Category filter appended to toolbar');
									}
									
									// Bind change event
									$('#bmm-category-filter').on('change', function() {
										var val = $(this).val();
										console.log('BMM Media Filter: Category filter changed to', val);
										if (self.collection && self.collection.props) {
											self.collection.props.set({bmm_media_category: val});
										}
									});
								}
							}
						}, 100);
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
		// Try to get cached results.
		$cache_key  = 'bmm_file_types';
		$file_types = get_transient( $cache_key );

		if ( false !== $file_types ) {
			return $file_types;
		}

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

		// Cache for 1 hour.
		set_transient( $cache_key, $file_types, HOUR_IN_SECONDS );

		return $file_types;
	}

	/**
	 * Clear cached file types when attachments are added/deleted.
	 *
	 * @return void
	 */
	public function clear_file_types_cache() {
		delete_transient( 'bmm_file_types' );
	}
}
