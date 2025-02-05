<?php
/**
 * Helper functions used for Optimization Detective Content Visibility.
 *
 * @package od-content-visibility
 * @since 0.1.0
 */

// @codeCoverageIgnoreStart
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
// @codeCoverageIgnoreEnd

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

/**
 * Gets the post meta key for storing the content-visibility visible heights.
 *
 * @since n.e.x.t
 *
 * @param OD_URL_Metric_Group $group Group.
 * @return non-empty-string Post meta key.
 */
function odcv_get_content_visibility_visible_heights_post_meta_key( OD_URL_Metric_Group $group ): string {
	return 'content_visibility_visible_heights:' . $group->get_minimum_viewport_width();
}

/**
 * Persists height of CV element in post meta so it is available even when no URL Metrics have collected the height.
 *
 * If visitors repeatedly visit the page and never scroll a CV-auto element into view, then the actual height of the
 * element will never be found. This necessitates that whenever we discover the height of an element that we persist
 * it outside of URL Metrics. Note: This same issue can occur for the resized heights of embeds in Embed Optimizer!
 *
 * @since 0.1.0
 * @access private
 *
 * @param OD_URL_Metric_Store_Request_Context $context Context.
 */
function odcv_persist_element_height_outside_url_metrics( OD_URL_Metric_Store_Request_Context $context ): void {
	$post_meta_key = odcv_get_content_visibility_visible_heights_post_meta_key( $context->url_metric_group );

	$current_xpaths                     = array();
	$content_visibility_visible_heights = array();

	foreach ( $context->url_metric->get_elements() as $element ) {
		$current_xpaths[] = $element->get_xpath();

		$content_visibility_visible_height = $element->get( 'contentVisibilityVisibleHeight' );
		if ( is_numeric( $content_visibility_visible_height ) ) {
			$content_visibility_visible_heights[ $element->get_xpath() ] = $content_visibility_visible_height;
		}
	}

	// Parse out the stored content-visibility visible heights to retain any that are still valid but not to overwrite any which were just submitted.
	$stored_content_visibility_visible_heights = get_post_meta( $context->post_id, $post_meta_key, true );
	if ( is_array( $stored_content_visibility_visible_heights ) ) {
		foreach ( $stored_content_visibility_visible_heights as $xpath => $stored_content_visibility_visible_height ) {
			if (
				in_array( $xpath, $current_xpaths, true )
				&&
				! isset( $content_visibility_visible_heights[ $xpath ] )
				&&
				is_numeric( $stored_content_visibility_visible_height )
				&&
				$stored_content_visibility_visible_height >= 0
			) {
				$content_visibility_visible_heights[ $xpath ] = $stored_content_visibility_visible_height;
			}
		}
	}

	if ( count( $content_visibility_visible_heights ) === 0 ) {
		delete_post_meta( $context->post_id, $post_meta_key );
	} else {
		update_post_meta( $context->post_id, $post_meta_key, $content_visibility_visible_heights );
	}
}
