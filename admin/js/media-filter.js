/**
 * Media library grid view filtering functionality.
 *
 * @package Better_Media_Manager
 */

(function($) {
	'use strict';

	// Wait for media library to be ready
	$(document).ready(function() {
		if (typeof wp !== 'undefined' && wp.media && wp.media.view) {
			// Extend the AttachmentsBrowser view to add our custom filter
			var AttachmentsBrowser = wp.media.view.AttachmentsBrowser;

			wp.media.view.AttachmentsBrowser = AttachmentsBrowser.extend({
				createToolbar: function() {
					// Call the original createToolbar
					AttachmentsBrowser.prototype.createToolbar.call(this);

					// Add our custom file type filter
					var filters = this.options.filters;

				if (filters === 'uploaded' || filters === 'all' || !filters) {
					var template = $('#tmpl-bmm-filetype-filter').html();
					
					// Insert the template HTML directly into the toolbar
					var self = this;
					setTimeout(function() {
						var $toolbar = self.toolbar.$el.find('.media-toolbar-secondary');
						
						// Find the date filter select and insert after it
						var $dateFilter = $toolbar.find('#media-attachment-date-filters');
						if ($dateFilter.length) {
							$dateFilter.after(template);
						} else {
							// If no date filter, append to toolbar
							$toolbar.append(template);
						}

						// Bind the change event to the filter
						$('#bmm-filetype-filter').on('change', function(e) {
							var value = $(this).val();
							
							// Update the collection with the new filter
							if (self.collection && self.collection.props) {
								self.collection.props.set({
									bmm_filetype: value
								});
							}
						});
					}, 100);
		}
	});

})(jQuery);
