<?php
/**
 * Provide a admin area view for the settings page.
 *
 * @package Better_Media_Manager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php settings_errors(); ?>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'better_media_manager_settings_group' );
		do_settings_sections( 'better-media-manager-settings' );
		submit_button();
		?>
	</form>

	<div class="better-media-manager-info">
		<h2><?php esc_html_e( 'About Firecrawl API', 'better-media-manager' ); ?></h2>
		<p><?php esc_html_e( 'Firecrawl is a web scraping API that allows you to extract content from websites. To use this plugin, you need a Firecrawl API key.', 'better-media-manager' ); ?></p>
		<ul>
			<li><strong><?php esc_html_e( 'Sign up:', 'better-media-manager' ); ?></strong> <a href="https://firecrawl.dev" target="_blank">https://firecrawl.dev</a></li>
			<li><strong><?php esc_html_e( 'Documentation:', 'better-media-manager' ); ?></strong> <a href="https://docs.firecrawl.dev" target="_blank">https://docs.firecrawl.dev</a></li>
		</ul>
	</div>
</div>
