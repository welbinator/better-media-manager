<?php
/**
 * Media taxonomy registration and management.
 *
 * @package Better_Media_Manager
 */

namespace Better_Media_Manager;

/**
 * Handles custom taxonomy for media attachments.
 */
class Media_Taxonomy {

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
	 * Taxonomy slug.
	 *
	 * @var string
	 */
	const TAXONOMY_SLUG = 'bmm_media_category';

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
	 * Register custom taxonomy for attachments.
	 *
	 * @return void
	 */
	public function register_taxonomy(): void {
		$labels = array(
			'name'                       => _x( 'Categories', 'taxonomy general name', 'better-media-manager' ),
			'singular_name'              => _x( 'Category', 'taxonomy singular name', 'better-media-manager' ),
			'search_items'               => __( 'Search Categories', 'better-media-manager' ),
			'popular_items'              => __( 'Popular Categories', 'better-media-manager' ),
			'all_items'                  => __( 'All Categories', 'better-media-manager' ),
			'parent_item'                => __( 'Parent Category', 'better-media-manager' ),
			'parent_item_colon'          => __( 'Parent Category:', 'better-media-manager' ),
			'edit_item'                  => __( 'Edit Category', 'better-media-manager' ),
			'update_item'                => __( 'Update Category', 'better-media-manager' ),
			'add_new_item'               => __( 'Add New Category', 'better-media-manager' ),
			'new_item_name'              => __( 'New Category Name', 'better-media-manager' ),
			'separate_items_with_commas' => __( 'Separate categories with commas', 'better-media-manager' ),
			'add_or_remove_items'        => __( 'Add or remove categories', 'better-media-manager' ),
			'choose_from_most_used'      => __( 'Choose from the most used categories', 'better-media-manager' ),
			'not_found'                  => __( 'No categories found.', 'better-media-manager' ),
			'menu_name'                  => __( 'Categories', 'better-media-manager' ),
		);

		$args = array(
			'labels'               => $labels,
			'public'               => true,
			'hierarchical'         => true,
			'show_ui'              => true,
			'show_admin_column'    => true,
			'show_in_nav_menus'    => true,
			'show_tagcloud'        => false,
			'show_in_rest'         => true,
			'rewrite'              => array( 'slug' => self::TAXONOMY_SLUG ),
			'update_count_callback' => array( $this, 'update_attachment_term_count' ),
			'capabilities'         => array(
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'manage_categories',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'upload_files',
			),
		);

		register_taxonomy( self::TAXONOMY_SLUG, 'attachment', $args );
	}

	/**
	 * Custom term count callback for attachments.
	 * 
	 * Attachments have post_status of 'inherit', so we need custom counting.
	 *
	 * @param array  $terms    List of term IDs.
	 * @param object $taxonomy Taxonomy object.
	 * @return void
	 */
	public function update_attachment_term_count( $terms, $taxonomy ) {
		global $wpdb;

		foreach ( (array) $terms as $term ) {
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $wpdb->term_relationships tr
					INNER JOIN $wpdb->posts p ON p.ID = tr.object_id
					WHERE tr.term_taxonomy_id = %d
					AND p.post_type = 'attachment'
					AND p.post_status = 'inherit'",
					$term
				)
			);

			$wpdb->update(
				$wpdb->term_taxonomy,
				array( 'count' => $count ),
				array( 'term_taxonomy_id' => $term ),
				array( '%d' ),
				array( '%d' )
			);
		}
	}

	/**
	 * Add category field to attachment edit screen.
	 *
	 * @param array   $form_fields Array of form fields.
	 * @param WP_Post $post        The attachment post object.
	 * @return array Modified form fields.
	 */
	public function add_attachment_category_field( $form_fields, $post ) {
		// Get terms assigned to this attachment.
		$terms         = wp_get_object_terms( $post->ID, self::TAXONOMY_SLUG );
		$selected_ids  = array();
		$selected_data = array();

		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$selected_ids[]  = $term->term_id;
				$selected_data[] = array(
					'id'   => $term->term_id,
					'text' => $term->name,
				);
			}
		}

		// Get all available terms.
		$all_terms = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY_SLUG,
				'hide_empty' => false,
			)
		);

		$options_html = '';
		if ( ! is_wp_error( $all_terms ) && ! empty( $all_terms ) ) {
			foreach ( $all_terms as $term ) {
				$selected      = in_array( $term->term_id, $selected_ids, true ) ? ' selected="selected"' : '';
				$options_html .= sprintf(
					'<option value="%d"%s>%s</option>',
					absint( $term->term_id ),
					$selected,
					esc_html( $term->name )
				);
			}
		}

		$form_fields[ self::TAXONOMY_SLUG ] = array(
			'label' => __( 'Categories', 'better-media-manager' ),
			'input' => 'html',
			'html'  => sprintf(
				'<select name="attachments[%d][%s][]" id="attachments-%d-%s" class="bmm-category-select" multiple="multiple" style="width: 100%%; max-width: 25em;" data-selected=\'%s\'>%s</select>',
				absint( $post->ID ),
				esc_attr( self::TAXONOMY_SLUG ),
				absint( $post->ID ),
				esc_attr( self::TAXONOMY_SLUG ),
				wp_json_encode( $selected_data ),
				$options_html
			),
		);

		return $form_fields;
	}

	/**
	 * Save category field when attachment is saved.
	 *
	 * @param array $post       The attachment post array.
	 * @param array $attachment The attachment data from $_POST.
	 * @return array The unmodified post array.
	 */
	public function save_attachment_category( $post, $attachment ) {
		// Get the post ID from the array.
		$post_id = isset( $post['ID'] ) ? absint( $post['ID'] ) : 0;
		
		if ( ! $post_id ) {
			return $post;
		}

		if ( ! isset( $attachment[ self::TAXONOMY_SLUG ] ) ) {
			// If field not set, remove all terms.
			wp_set_object_terms( $post_id, array(), self::TAXONOMY_SLUG );
			return $post;
		}

		$term_ids = array();
		$raw_terms = (array) $attachment[ self::TAXONOMY_SLUG ];

		foreach ( $raw_terms as $term_value ) {
			// Check if this is a new term (format: "new:TermName").
			if ( is_string( $term_value ) && strpos( $term_value, 'new:' ) === 0 ) {
				// Extract the term name.
				$term_name = substr( $term_value, 4 );
				$term_name = sanitize_text_field( $term_name );

				if ( ! empty( $term_name ) ) {
					// Check if term already exists.
					$existing_term = get_term_by( 'name', $term_name, self::TAXONOMY_SLUG );

					if ( $existing_term ) {
						$term_ids[] = $existing_term->term_id;
					} else {
						// Create new term.
						$new_term = wp_insert_term( $term_name, self::TAXONOMY_SLUG );
						if ( ! is_wp_error( $new_term ) && isset( $new_term['term_id'] ) ) {
							$term_ids[] = $new_term['term_id'];
						}
					}
				}
			} else {
				// Existing term ID.
				$term_ids[] = absint( $term_value );
			}
		}

		wp_set_object_terms( $post_id, $term_ids, self::TAXONOMY_SLUG );

		// Update term counts for the taxonomy.
		if ( ! empty( $term_ids ) ) {
			wp_update_term_count_now( $term_ids, self::TAXONOMY_SLUG );
		}

		return $post;
	}

	/**
	 * Enqueue select2 for category field.
	 *
	 * @return void
	 */
	public function enqueue_select2() {
		// Enqueue on any admin page that might use media modal.
		if ( ! is_admin() ) {
			return;
		}

		// Enqueue Select2.
		wp_enqueue_style(
			'select2',
			'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
			array(),
			'4.1.0'
		);

		wp_enqueue_script(
			'select2',
			'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
			array( 'jquery' ),
			'4.1.0',
			true
		);

		// Add custom initialization script.
		$script = "
		jQuery(document).ready(function($) {
			console.log('BMM Category: Initializing select2');
			
			function initCategorySelect() {
				$('.bmm-category-select').each(function() {
					var \$select = $(this);
					
					if (!\$select.hasClass('select2-hidden-accessible')) {
						var selectedData = \$select.data('selected') || [];
						console.log('BMM Category: Initializing select2 on', this, 'with data:', selectedData);
						
						\$select.select2({
							tags: true,
							tokenSeparators: [','],
							placeholder: 'Select or create categories',
							allowClear: true,
							createTag: function(params) {
								var term = $.trim(params.term);
								if (term === '') {
									return null;
								}
								return {
									id: 'new:' + term,
									text: term + ' (create new)',
									newTag: true
								};
							}
						});
						
						// Add flag to prevent infinite loop
						\$select.data('bmm-saving', false);
						
						// Listen for changes and save with a small delay to ensure value is committed
						\$select.on('change', function() {
							var \$currentSelect = $(this);
							
							// Prevent recursive saves
							if (\$currentSelect.data('bmm-saving')) {
								console.log('BMM Category: Save already in progress, skipping');
								return;
							}
							
							\$currentSelect.data('bmm-saving', true);
							
							// Small delay to ensure Select2 has committed the value
							setTimeout(function() {
								var attachmentId = \$currentSelect.attr('id').match(/attachments-(\\d+)-/);
								if (attachmentId && attachmentId[1]) {
									var postId = attachmentId[1];
									var selectedValues = \$currentSelect.val() || [];
									
									console.log('BMM Category: Saving categories for attachment', postId);
									console.log('BMM Category: Selected values:', selectedValues);
									
									// Save via AJAX
									$.ajax({
										url: ajaxurl,
										type: 'POST',
										data: {
											action: 'bmm_save_attachment_categories',
											attachment_id: postId,
											categories: selectedValues,
											nonce: '" . wp_create_nonce( 'bmm_save_categories' ) . "'
										},
										success: function(response) {
											console.log('BMM Category: Save response', response);
											if (response.success) {
												console.log('BMM Category: Categories saved successfully', response.data);
												
												// Update the select with actual term IDs if new terms were created
												if (response.data.term_mapping) {
													var currentVals = \$currentSelect.val() || [];
													var newVals = currentVals.map(function(val) {
														return response.data.term_mapping[val] || val;
													});
													
													// Update options for new terms (without triggering change)
													for (var oldId in response.data.term_mapping) {
														if (oldId.indexOf('new:') === 0) {
															var newId = response.data.term_mapping[oldId];
															var termName = response.data.term_names[newId];
															// Remove old option
															\$currentSelect.find('option[value=\"' + oldId + '\"]').remove();
															// Add new option if it doesn't exist
															if (\$currentSelect.find('option[value=\"' + newId + '\"]').length === 0) {
																\$currentSelect.append(new Option(termName, newId, true, true));
															}
														}
													}
													
													// Update selected values without triggering change event
													\$currentSelect.val(newVals);
												}
											} else {
												console.error('BMM Category: Failed to save categories', response.data);
												alert('Failed to save categories: ' + (response.data.message || 'Unknown error'));
											}
											
											// Re-enable saving after a short delay
											setTimeout(function() {
												\$currentSelect.data('bmm-saving', false);
											}, 500);
										},
										error: function(xhr, status, error) {
											console.error('BMM Category: AJAX error', error);
											console.error('BMM Category: Response:', xhr.responseText);
											alert('Error saving categories. Check console for details.');
											
											// Re-enable saving on error
											\$currentSelect.data('bmm-saving', false);
										}
									});
								} else {
									// No attachment ID found, re-enable saving
									\$currentSelect.data('bmm-saving', false);
								}
							}, 250);
						});
					} else {
						// Already initialized, just refresh the data
						console.log('BMM Category: Select2 already initialized, checking if data needs refresh');
						
						// Get the current attachment ID
						var attachmentId = \$select.attr('id').match(/attachments-(\\d+)-/);
						if (attachmentId && attachmentId[1]) {
							var postId = attachmentId[1];
							var selectedData = \$select.data('selected') || [];
							var currentVals = \$select.val() || [];
							var dataIds = selectedData.map(function(item) { return String(item.id); }).sort();
							var selectIds = currentVals.map(String).sort();
							
							console.log('BMM Category: Data IDs:', dataIds);
							console.log('BMM Category: Select IDs:', selectIds);
							
							// If the data attribute differs from current value, update it
							if (JSON.stringify(dataIds) !== JSON.stringify(selectIds)) {
								console.log('BMM Category: Updating select with fresh data');
								
								// Clear current selection
								\$select.val(null);
								
								// Set new values
								if (selectedData.length > 0) {
									var valuesToSet = selectedData.map(function(item) { return String(item.id); });
									
									// Make sure options exist
									selectedData.forEach(function(item) {
										if (\$select.find('option[value=\"' + item.id + '\"]').length === 0) {
											\$select.append(new Option(item.text, item.id, true, true));
										}
									});
									
									\$select.val(valuesToSet);
								}
							}
						}
					}
				});
			}
			
			// Initialize on page load.
			initCategorySelect();
			
			// Re-initialize when media modal opens.
			$(document).on('click', '.attachment', function() {
				setTimeout(initCategorySelect, 500);
			});
			
			// Watch for DOM changes in media modal.
			if (typeof MutationObserver !== 'undefined') {
				var observer = new MutationObserver(function(mutations) {
					mutations.forEach(function(mutation) {
						if (mutation.addedNodes && mutation.addedNodes.length > 0) {
							for (var i = 0; i < mutation.addedNodes.length; i++) {
								var node = mutation.addedNodes[i];
								if (node.nodeType === 1) {
									var \$selects = $(node).find('.bmm-category-select');
									if (\$selects.length > 0) {
										setTimeout(initCategorySelect, 100);
										break;
									}
								}
							}
						}
					});
				});
				
				observer.observe(document.body, {
					childList: true,
					subtree: true
				});
			}
		});
		";

		wp_add_inline_script( 'select2', $script );
	}

	/**
	 * Handle AJAX request to save attachment categories.
	 *
	 * @return void
	 */
	public function ajax_save_attachment_categories() {
		// Debug: Log that function was called
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'BMM: ajax_save_attachment_categories called' );
			error_log( 'BMM: POST data: ' . wp_json_encode( $_POST ) );
		}

		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'bmm_save_categories' ) ) {
			error_log( 'BMM: Nonce verification failed' );
			wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'better-media-manager' ) ) );
		}

		// Check capability.
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to edit attachments.', 'better-media-manager' ) ) );
		}

		// Get attachment ID.
		if ( ! isset( $_POST['attachment_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Attachment ID is required.', 'better-media-manager' ) ) );
		}

		$attachment_id = absint( $_POST['attachment_id'] );

		// Verify this is an attachment.
		if ( 'attachment' !== get_post_type( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid attachment ID.', 'better-media-manager' ) ) );
		}

		// Get the old terms before we change anything (for count updates).
		$old_terms = wp_get_object_terms( $attachment_id, self::TAXONOMY_SLUG, array( 'fields' => 'ids' ) );
		if ( is_wp_error( $old_terms ) ) {
			$old_terms = array();
		}

		// Get categories - properly handle array from POST.
		$categories = isset( $_POST['categories'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['categories'] ) ) : array();

		if ( empty( $categories ) ) {
			// Remove all terms.
			$result = wp_set_object_terms( $attachment_id, array(), self::TAXONOMY_SLUG );
			
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}
			
			// Clear caches.
			clean_object_term_cache( $attachment_id, 'attachment' );
			wp_cache_delete( $attachment_id, 'post_meta' );
			
			// Update counts for the old terms that were removed.
			if ( ! empty( $old_terms ) ) {
				wp_update_term_count_now( $old_terms, self::TAXONOMY_SLUG );
			}
			
			wp_send_json_success(
				array(
					'message'      => __( 'Categories removed.', 'better-media-manager' ),
					'term_ids'     => array(),
					'term_mapping' => array(),
					'term_names'   => array(),
				)
			);
		}

		$term_ids     = array();
		$term_mapping = array(); // Maps old IDs (including "new:Name") to actual term IDs.
		$term_names   = array(); // Maps term IDs to names.

		foreach ( $categories as $term_value ) {
			// Check if this is a new term (format: "new:TermName").
			if ( strpos( $term_value, 'new:' ) === 0 ) {
				// Extract the term name.
				$term_name = substr( $term_value, 4 );

				if ( ! empty( $term_name ) ) {
					// Check if term already exists.
					$existing_term = get_term_by( 'name', $term_name, self::TAXONOMY_SLUG );

					if ( $existing_term ) {
						$term_ids[]                 = $existing_term->term_id;
						$term_mapping[ $term_value ] = (string) $existing_term->term_id;
						$term_names[ $existing_term->term_id ] = $existing_term->name;
					} else {
						// Create new term.
						$new_term = wp_insert_term( $term_name, self::TAXONOMY_SLUG );
						if ( ! is_wp_error( $new_term ) && isset( $new_term['term_id'] ) ) {
							$term_ids[]                 = $new_term['term_id'];
							$term_mapping[ $term_value ] = (string) $new_term['term_id'];
							
							// Get the term object to get the name.
							$created_term = get_term( $new_term['term_id'], self::TAXONOMY_SLUG );
							if ( $created_term && ! is_wp_error( $created_term ) ) {
								$term_names[ $new_term['term_id'] ] = $created_term->name;
							}
						}
					}
				}
			} else {
				// Existing term ID.
				$term_id                       = absint( $term_value );
				$term_ids[]                    = $term_id;
				$term_mapping[ $term_value ]    = (string) $term_id;
				
				// Get term name.
				$term = get_term( $term_id, self::TAXONOMY_SLUG );
				if ( $term && ! is_wp_error( $term ) ) {
					$term_names[ $term_id ] = $term->name;
				}
			}
		}

		// Set the terms.
		$result = wp_set_object_terms( $attachment_id, $term_ids, self::TAXONOMY_SLUG );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Clear object term cache to ensure fresh data on next load.
		clean_object_term_cache( $attachment_id, 'attachment' );
		wp_cache_delete( $attachment_id, 'post_meta' );

		// Verify the terms were actually saved
		$saved_terms = wp_get_object_terms( $attachment_id, self::TAXONOMY_SLUG, array( 'fields' => 'ids' ) );
		error_log( 'BMM Category: Attempted to save term IDs: ' . print_r( $term_ids, true ) );
		error_log( 'BMM Category: Actually saved term IDs: ' . print_r( $saved_terms, true ) );

		// Update term counts for both old and new terms.
		$all_affected_terms = array_unique( array_merge( $old_terms, $term_ids ) );
		if ( ! empty( $all_affected_terms ) ) {
			wp_update_term_count_now( $all_affected_terms, self::TAXONOMY_SLUG );
			error_log( 'BMM Category: Updated counts for terms: ' . print_r( $all_affected_terms, true ) );
		}

		wp_send_json_success(
			array(
				'message'      => __( 'Categories saved.', 'better-media-manager' ),
				'term_ids'     => $term_ids,
				'saved_terms'  => $saved_terms, // Send back what was actually saved for debugging
				'term_mapping' => $term_mapping,
				'term_names'   => $term_names,
			)
		);
	}

	/**
	 * Handle AJAX request to create new category.
	 *
	 * @return void
	 */
	public function ajax_create_category() {
		// Verify nonce.
		check_ajax_referer( 'better_media_manager_nonce', 'nonce' );

		// Check capability.
		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to create categories.', 'better-media-manager' ) ) );
		}

		// Get and sanitize category name.
		if ( ! isset( $_POST['name'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Category name is required.', 'better-media-manager' ) ) );
		}

		$category_name = sanitize_text_field( wp_unslash( $_POST['name'] ) );

		if ( empty( $category_name ) ) {
			wp_send_json_error( array( 'message' => __( 'Category name cannot be empty.', 'better-media-manager' ) ) );
		}

		// Create the term.
		$term = wp_insert_term( $category_name, self::TAXONOMY_SLUG );

		if ( is_wp_error( $term ) ) {
			wp_send_json_error( array( 'message' => $term->get_error_message() ) );
		}

		// Get the created term.
		$created_term = get_term( $term['term_id'], self::TAXONOMY_SLUG );

		if ( is_wp_error( $created_term ) || ! $created_term ) {
			wp_send_json_error( array( 'message' => __( 'Failed to retrieve created term.', 'better-media-manager' ) ) );
		}

		wp_send_json_success(
			array(
				'term' => array(
					'id'   => $created_term->term_id,
					'name' => $created_term->name,
					'slug' => $created_term->slug,
				),
			)
		);
	}
}
