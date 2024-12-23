<?php
/**
 * Plugin Name: Optimization Detective Content Visibility
 * Plugin URI: https://github.com/westonruter/od-content-visibility
 * Description: Applies content-visibility to posts in The Loop to improve rendering performance.
 * Requires at least: 6.5
 * Requires PHP: 7.2
 * Requires Plugins: optimization-detective
 * Version: 0.1.0
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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

const OD_CONTENT_VISIBILITY_VERSION = '0.1.0';

add_action(
	'od_init',
	static function (): void {
		require_once __DIR__ . '/helper.php';

		add_action( 'od_register_tag_visitors', 'odcv_register_tag_visitor' );
		add_filter( 'od_extension_module_urls', 'odcv_filter_extension_module_urls' );
		add_filter( 'od_url_metric_schema_element_item_additional_properties', 'odcv_add_element_item_schema_properties' );
		add_action( 'od_url_metric_stored', 'odcv_persist_element_height_outside_url_metrics' );
	}
);
