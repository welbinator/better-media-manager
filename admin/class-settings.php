<?php
/**
 * The settings-specific functionality of the plugin.
 *
 * @package Better_Media_Manager
 */

namespace Better_Media_Manager\Admin;

/**
 * The settings-specific functionality of the plugin.
 *
 * Handles all settings registration and sanitization.
 */
class Settings {

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
	 * Register all settings.
	 */
	public function register_settings() {
		// Register setting.
		register_setting(
			'better_media_manager_settings_group',
			'better_media_manager_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		// Add Better Media Manager general section.
		add_settings_section(
			'bmm_general_section',
			__( 'Better Media Manager', 'better-media-manager' ),
			array( $this, 'general_section_callback' ),
			'better-media-manager-settings'
		);

		// Add Image Scraper scraping method section.
		add_settings_section(
			'bmm_scraping_method_section',
			__( 'Image Scraper', 'better-media-manager' ),
			array( $this, 'scraping_method_section_callback' ),
			'better-media-manager-settings'
		);

		// Add scraping method field.
		add_settings_field(
			'scraping_method',
			__( 'Scraping Method', 'better-media-manager' ),
			array( $this, 'scraping_method_field_callback' ),
			'better-media-manager-settings',
			'bmm_scraping_method_section'
		);

		// Add settings section for Firecrawl.
		add_settings_section(
			'bmm_firecrawl_section',
			__( 'Firecrawl API Configuration', 'better-media-manager' ),
			array( $this, 'api_section_callback' ),
			'better-media-manager-settings'
		);

		// Add API key field.
		add_settings_field(
			'firecrawl_api_key',
			__( 'Firecrawl API Key', 'better-media-manager' ),
			array( $this, 'api_key_field_callback' ),
			'better-media-manager-settings',
			'bmm_firecrawl_section'
		);

		// Add settings section for scraping options.
		add_settings_section(
			'bmm_scraping_options_section',
			__( 'Scraping Options', 'better-media-manager' ),
			array( $this, 'options_section_callback' ),
			'better-media-manager-settings'
		);

		// Add max images field.
		add_settings_field(
			'max_images',
			__( 'Maximum Images Per Scrape', 'better-media-manager' ),
			array( $this, 'max_images_field_callback' ),
			'better-media-manager-settings',
			'bmm_scraping_options_section'
		);

		// Add timeout field.
		add_settings_field(
			'timeout',
			__( 'Request Timeout (seconds)', 'better-media-manager' ),
			array( $this, 'timeout_field_callback' ),
			'better-media-manager-settings',
			'bmm_scraping_options_section'
		);
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param array $input The input array to sanitize.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		// Sanitize scraping method.
		if ( isset( $input['scraping_method'] ) ) {
			$method = sanitize_text_field( $input['scraping_method'] );
			$sanitized['scraping_method'] = in_array( $method, array( 'simple', 'firecrawl' ), true ) ? $method : 'simple';
		}

		// Sanitize API key.
		if ( isset( $input['firecrawl_api_key'] ) ) {
			$sanitized['firecrawl_api_key'] = sanitize_text_field( $input['firecrawl_api_key'] );
		}

		// Sanitize max images.
		if ( isset( $input['max_images'] ) ) {
			$max_images = absint( $input['max_images'] );
			// Enforce reasonable limits.
			$sanitized['max_images'] = max( 1, min( 500, $max_images ) );
		}

		// Sanitize timeout.
		if ( isset( $input['timeout'] ) ) {
			$timeout = absint( $input['timeout'] );
			// Enforce reasonable limits.
			$sanitized['timeout'] = max( 5, min( 300, $timeout ) );
		}

		return $sanitized;
	}

	/**
	 * General section callback.
	 */
	public function general_section_callback() {
		echo '<p>' . esc_html__( 'Bulk download media files from your WordPress media library and scrape images from external websites.', 'better-media-manager' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Bulk Download:', 'better-media-manager' ) . '</strong> ' . esc_html__( 'Go to Media Library, select files, and choose "Download" from the bulk actions menu to download them as a ZIP file.', 'better-media-manager' ) . '</p>';
	}

	/**
	 * Scraping method section callback.
	 */
	public function scraping_method_section_callback() {
		echo '<p>' . esc_html__( 'Configure how to scrape images from external websites.', 'better-media-manager' ) . '</p>';
	}

	/**
	 * Scraping method field callback.
	 */
	public function scraping_method_field_callback() {
		$options = get_option( 'better_media_manager_settings' );
		$method  = isset( $options['scraping_method'] ) ? $options['scraping_method'] : 'simple';
		?>
		<fieldset>
			<label>
				<input 
					type="radio" 
					name="better_media_manager_settings[scraping_method]" 
					value="simple"
					<?php checked( $method, 'simple' ); ?>
				/>
				<strong><?php esc_html_e( 'Simple Mode (Recommended)', 'better-media-manager' ); ?></strong>
				<p class="description" style="margin-left: 25px;">
					<?php esc_html_e( 'Direct HTML scraping - fast, free, and works for most websites. No API key required.', 'better-media-manager' ); ?>
				</p>
			</label>
			<br><br>
			<label>
				<input 
					type="radio" 
					name="better_media_manager_settings[scraping_method]" 
					value="firecrawl"
					<?php checked( $method, 'firecrawl' ); ?>
				/>
				<strong><?php esc_html_e( 'Firecrawl API', 'better-media-manager' ); ?></strong>
				<p class="description" style="margin-left: 25px;">
					<?php esc_html_e( 'Advanced scraping for JavaScript-heavy sites, SPAs, and protected content. Requires API key.', 'better-media-manager' ); ?>
				</p>
			</label>
		</fieldset>
		<?php
	}

	/**
	 * API section callback.
	 */
	public function api_section_callback() {
		$options = get_option( 'better_media_manager_settings' );
		$method  = isset( $options['scraping_method'] ) ? $options['scraping_method'] : 'simple';
		
		if ( $method === 'simple' ) {
			echo '<p class="description">' . esc_html__( 'Firecrawl API is not required when using Simple Mode.', 'better-media-manager' ) . '</p>';
		} else {
			echo '<p>' . esc_html__( 'Configure your Firecrawl API credentials. Get your API key from', 'better-media-manager' ) . ' <a href="https://firecrawl.dev" target="_blank">firecrawl.dev</a>.</p>';
		}
	}

	/**
	 * Options section callback.
	 */
	public function options_section_callback() {
		echo '<p>' . esc_html__( 'Configure scraping behavior and limits.', 'better-media-manager' ) . '</p>';
	}

	/**
	 * Render API key field.
	 */
	public function api_key_field_callback() {
		$options = get_option( 'better_media_manager_settings' );
		$api_key = isset( $options['firecrawl_api_key'] ) ? $options['firecrawl_api_key'] : '';
		?>
		<input 
			type="password" 
			id="firecrawl_api_key" 
			name="better_media_manager_settings[firecrawl_api_key]" 
			value="<?php echo esc_attr( $api_key ); ?>" 
			class="regular-text"
			autocomplete="off"
		/>
		<p class="description">
			<?php esc_html_e( 'Your Firecrawl API key. This will be stored securely.', 'better-media-manager' ); ?>
		</p>
		<p>
			<button type="button" class="button button-secondary" id="test-api-key" <?php echo empty( $api_key ) ? 'disabled' : ''; ?>>
				<?php esc_html_e( 'Test API Connection', 'better-media-manager' ); ?>
			</button>
			<span id="api-test-result"></span>
		</p>
		<?php
	}

	/**
	 * Render max images field.
	 */
	public function max_images_field_callback() {
		$options    = get_option( 'better_media_manager_settings' );
		$max_images = isset( $options['max_images'] ) ? $options['max_images'] : 50;
		?>
		<input 
			type="number" 
			id="max_images" 
			name="better_media_manager_settings[max_images]" 
			value="<?php echo esc_attr( $max_images ); ?>" 
			min="1" 
			max="500"
			class="small-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Maximum number of images to scrape per request (1-500).', 'better-media-manager' ); ?>
		</p>
		<?php
	}

	/**
	 * Render timeout field.
	 */
	public function timeout_field_callback() {
		$options = get_option( 'better_media_manager_settings' );
		$timeout = isset( $options['timeout'] ) ? $options['timeout'] : 30;
		?>
		<input 
			type="number" 
			id="timeout" 
			name="better_media_manager_settings[timeout]" 
			value="<?php echo esc_attr( $timeout ); ?>" 
			min="5" 
			max="300"
			class="small-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Timeout for API requests in seconds (5-300).', 'better-media-manager' ); ?>
		</p>
		<?php
	}
}
