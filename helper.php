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
