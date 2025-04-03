<?php
/**
 * Plugin Name: Optimization Detective Content Visibility
 * Plugin URI: https://github.com/westonruter/od-content-visibility
 * Description: Applies content-visibility to posts in The Loop to improve rendering performance.
 * Requires at least: 6.5
 * Requires PHP: 7.2
 * Requires Plugins: optimization-detective
 * Version: 0.2.0
 * Author: Weston Ruter
 * Author URI: https://weston.ruter.net/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: od-content-visibility
 * Update URI: https://github.com/westonruter/od-content-visibility
 * GitHub Plugin URI: https://github.com/westonruter/od-content-visibility
 *
 * @package od-content-visibility
 */

// @codeCoverageIgnoreStart
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
// @codeCoverageIgnoreEnd

const OD_CONTENT_VISIBILITY_VERSION = '0.2.0';

add_action(
	'od_init',
	static function ( $optimization_detective_version ): void {
		$required_od_version = '1.0.0-beta4';
		if ( ! version_compare( $optimization_detective_version, $required_od_version, '>=' ) ) {
			add_action(
				'admin_notices',
				static function (): void {
					global $pagenow;
					if ( ! in_array( $pagenow, array( 'index.php', 'plugins.php' ), true ) ) {
						return;
					}
					wp_admin_notice(
						esc_html(
							sprintf(
								/* translators: %s is plugin name */
								__( 'The %s plugin requires a newer version of the Optimization Detective plugin. Please update your plugins.', 'od-content-visibility' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
								plugin_basename( __FILE__ )
							)
						),
						array( 'type' => 'warning' )
					);
				}
			);
			return;
		}

		require_once __DIR__ . '/helper.php';

		add_action( 'od_register_tag_visitors', 'odcv_register_tag_visitor' );
		add_filter( 'od_extension_module_urls', 'odcv_filter_extension_module_urls' );
		add_filter( 'od_url_metric_schema_element_item_additional_properties', 'odcv_add_element_item_schema_properties' );
		add_action( 'od_url_metric_stored', 'odcv_persist_element_height_outside_url_metrics' );
	}
);
