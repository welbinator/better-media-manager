<?php
/**
 * Provide an admin area view for scraping images.
 *
 * @package Better_Media_Manager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check settings.
$options = get_option( 'better_media_manager_settings' );
$method  = isset( $options['scraping_method'] ) ? $options['scraping_method'] : 'simple';
$api_key = isset( $options['firecrawl_api_key'] ) ? $options['firecrawl_api_key'] : '';
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<!-- Scraping Method Badge -->
	<div class="notice notice-info inline" style="margin: 10px 0; display: flex; align-items: center; justify-content: space-between;">
		<p style="margin: 0;">
			<strong><?php esc_html_e( 'Current Scraping Method:', 'better-media-manager' ); ?></strong>
			<?php if ( 'firecrawl' === $method ) : ?>
				<span class="dashicons dashicons-cloud" style="color: #2271b1;"></span>
				<?php esc_html_e( 'Firecrawl API (Advanced)', 'better-media-manager' ); ?>
			<?php else : ?>
				<span class="dashicons dashicons-html" style="color: #00a32a;"></span>
				<?php esc_html_e( 'Simple Mode (Direct HTML)', 'better-media-manager' ); ?>
			<?php endif; ?>
		</p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=better-media-manager-settings' ) ); ?>" class="button button-small">
			<?php esc_html_e( 'Change Method', 'better-media-manager' ); ?>
		</a>
	</div>

	<!-- API Key Warning for Firecrawl -->
	<?php if ( 'firecrawl' === $method && empty( $api_key ) ) : ?>
		<div class="notice notice-warning">
			<p>
				<?php
				printf(
					/* translators: %s: link to settings page */
					esc_html__( 'Firecrawl API key is not configured. Please add your API key in %s or switch to Simple Mode.', 'better-media-manager' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=better-media-manager-settings' ) ) . '">' . esc_html__( 'Settings', 'better-media-manager' ) . '</a>'
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<div class="better-media-manager-container">
		<!-- Step 1: Initial Scrape Form -->
		<div class="better-media-manager-form-wrapper">
			<h2><?php esc_html_e( 'Step 1: Scrape Images from URL', 'better-media-manager' ); ?></h2>
			
			<form id="better-media-manager-form" method="post">
				<?php wp_nonce_field( 'better_media_manager_scrape_action', 'better_media_manager_scrape_nonce' ); ?>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="target_url"><?php esc_html_e( 'Target URL', 'better-media-manager' ); ?></label>
						</th>
						<td>
							<input 
								type="url" 
								id="target_url" 
								name="target_url" 
								class="regular-text" 
								placeholder="https://example.com"
								required
							<?php echo ( 'firecrawl' === $method && empty( $api_key ) ) ? 'disabled' : ''; ?>
							/>
							<p class="description">
								<?php esc_html_e( 'Enter the URL of the webpage you want to scrape images from.', 'better-media-manager' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="target_class_toggle"><?php esc_html_e( 'Target Specific Class', 'better-media-manager' ); ?></label>
						</th>
						<td>
							<label>
								<input 
									type="checkbox" 
									id="target_class_toggle" 
									name="target_class_toggle"
								<?php echo ( 'firecrawl' === $method && empty( $api_key ) ) ? 'disabled' : ''; ?>
								/>
								<?php esc_html_e( 'Only scrape images with a specific CSS class', 'better-media-manager' ); ?>
							</label>
							<div id="target_class_wrapper" style="display: none; margin-top: 10px;">
								<input 
									type="text" 
									id="target_class" 
									name="target_class" 
									class="regular-text" 
									placeholder="my-image-class or .my-image-class"
								<?php echo ( 'firecrawl' === $method && empty( $api_key ) ) ? 'disabled' : ''; ?>
								/>
								<p class="description">
									<?php esc_html_e( 'Enter the CSS class name (with or without the dot).', 'better-media-manager' ); ?>
								</p>
							</div>
						</td>
					</tr>
				</table>

				<?php
				$button_disabled = ( 'firecrawl' === $method && empty( $api_key ) );
				submit_button(
					__( 'Start Scraping', 'better-media-manager' ),
					'primary',
					'submit',
					true,
					$button_disabled ? array( 'disabled' => 'disabled' ) : array()
				);
				?>
			</form>
		</div>

		<!-- Progress Indicator -->
		<div id="scraping-progress" class="better-media-manager-progress" style="display: none;">
			<h3><?php esc_html_e( 'Scraping in Progress...', 'better-media-manager' ); ?></h3>
			<div class="progress-bar">
				<div class="progress-bar-fill"></div>
			</div>
			<p class="progress-message"></p>
		</div>

		<!-- Step 2: Preview and Options -->
		<div id="scraping-results" class="better-media-manager-results" style="display: none;">
			<h3><?php esc_html_e( 'Step 2: Preview & Configure Import Options', 'better-media-manager' ); ?></h3>
			
			<div class="results-summary">
				<p class="images-found-message"></p>
			</div>

			<!-- Image Preview Grid -->
			<div id="scraped-images-preview" class="scraped-images-grid"></div>

			<!-- Import Options Form -->
			<div class="import-options-wrapper">
				<h3><?php esc_html_e( 'Import Options', 'better-media-manager' ); ?></h3>
				
				<form id="import-options-form">
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="convert_format_toggle"><?php esc_html_e( 'Convert Image Format', 'better-media-manager' ); ?></label>
							</th>
							<td>
								<label>
									<input type="checkbox" id="convert_format_toggle" name="convert_format_toggle" />
									<?php esc_html_e( 'Convert all images to a specific format', 'better-media-manager' ); ?>
								</label>
								<div id="convert_format_wrapper" style="display: none; margin-top: 10px;">
									<select id="convert_format" name="convert_format" class="regular-text">
										<option value="webp"><?php esc_html_e( 'WebP', 'better-media-manager' ); ?></option>
										<option value="jpeg"><?php esc_html_e( 'JPEG', 'better-media-manager' ); ?></option>
										<option value="png"><?php esc_html_e( 'PNG', 'better-media-manager' ); ?></option>
									</select>
								</div>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="max_width"><?php esc_html_e( 'Maximum Width', 'better-media-manager' ); ?></label>
							</th>
							<td>
								<input 
									type="number" 
									id="max_width" 
									name="max_width" 
									class="small-text" 
									placeholder="e.g., 1920"
									min="1"
								/>
								<span class="description"><?php esc_html_e( 'pixels (leave empty for no limit)', 'better-media-manager' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="max_filesize"><?php esc_html_e( 'Maximum File Size', 'better-media-manager' ); ?></label>
							</th>
							<td>
								<input 
									type="number" 
									id="max_filesize" 
									name="max_filesize" 
									class="small-text" 
									placeholder="e.g., 500"
									min="1"
								/>
								<span class="description"><?php esc_html_e( 'KB (leave empty for no limit)', 'better-media-manager' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="filename_prefix"><?php esc_html_e( 'Filename Prefix', 'better-media-manager' ); ?></label>
							</th>
							<td>
								<input 
									type="text" 
									id="filename_prefix" 
									name="filename_prefix" 
									class="regular-text" 
									placeholder="e.g., my-site-"
								/>
								<p class="description">
									<?php esc_html_e( 'Add a prefix to all imported image filenames (optional).', 'better-media-manager' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="image_alt"><?php esc_html_e( 'Alt Text', 'better-media-manager' ); ?></label>
							</th>
							<td>
								<input 
									type="text" 
									id="image_alt" 
									name="image_alt" 
									class="regular-text" 
									placeholder="e.g., Product image"
								/>
								<p class="description">
									<?php esc_html_e( 'This alt text will be applied to all imported images (optional).', 'better-media-manager' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="image_title"><?php esc_html_e( 'Image Title', 'better-media-manager' ); ?></label>
							</th>
							<td>
								<input 
									type="text" 
									id="image_title" 
									name="image_title" 
									class="regular-text" 
									placeholder="e.g., Product photo"
								/>
								<p class="description">
									<?php esc_html_e( 'This title will be applied to all imported images (optional).', 'better-media-manager' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<p class="submit">
						<button type="submit" class="button button-primary button-large" id="add-to-media-library">
							<?php esc_html_e( 'Add to Media Library', 'better-media-manager' ); ?>
						</button>
						<button type="button" class="button button-secondary" id="start-over">
							<?php esc_html_e( 'Start Over', 'better-media-manager' ); ?>
						</button>
					</p>
				</form>
			</div>
		</div>

		<!-- Import Progress -->
		<div id="import-progress" class="better-media-manager-progress" style="display: none;">
			<h3><?php esc_html_e( 'Importing Images...', 'better-media-manager' ); ?></h3>
			<div class="progress-bar">
				<div class="progress-bar-fill"></div>
			</div>
			<p class="progress-message"></p>
		</div>

		<!-- Final Results -->
		<div id="import-results" class="better-media-manager-results" style="display: none;">
			<h3><?php esc_html_e( 'Import Complete!', 'better-media-manager' ); ?></h3>
			<div class="results-content"></div>
		</div>
	</div>
</div>
