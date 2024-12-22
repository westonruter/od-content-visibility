<?php
/**
 * Helper functions used for Optimization Detective Content Visibility.
 *
 * @package od-content-visibility
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers tag visitor.
 *
 * @since 0.1.0
 *
 * @param OD_Tag_Visitor_Registry $registry Registry.
 */
function odcv_register_tag_visitor( OD_Tag_Visitor_Registry $registry ): void {
	if ( is_singular() ) {
		return; // TODO: Also consider adding CV for root-level blocks?
	}
	require_once __DIR__ . '/class-odcv-content-visibility-visitor.php';
	$registry->register( 'od-content-visibility', new ODCV_Content_Visibility_Visitor() );
}

/**
 * Filters the list of Optimization Detective extension module URLs to include the extension for Content Visibility.
 *
 * @since 0.1.0
 * @access private
 *
 * @param string[]|mixed $extension_module_urls Extension module URLs.
 * @return string[] Extension module URLs.
 */
function odcv_filter_extension_module_urls( $extension_module_urls ): array {
	if ( ! is_array( $extension_module_urls ) ) {
		$extension_module_urls = array();
	}
	$extension_module_urls[] = plugins_url( add_query_arg( 'ver', OD_CONTENT_VISIBILITY_VERSION, 'detect.js' ), __FILE__ );
	return $extension_module_urls;
}

/**
 * Filters additional properties for the root schema for Optimization Detective.
 *
 * @since 0.1.0
 * @access private
 *
 * @param array<string, array{type: string}>|mixed $additional_properties Additional properties.
 * @return array<string, array{type: string}> Additional properties.
 */
function odcv_add_element_item_schema_properties( $additional_properties ): array {
	if ( ! is_array( $additional_properties ) ) {
		$additional_properties = array();
	}

	$additional_properties['contentVisibilityVisibleHeight'] = array(
		'type'    => 'number',
		'minimum' => 0,
	);
	return $additional_properties;
}
