<?php
/**
 * Hook callbacks used for Optimization Detective Content Visibility.
 *
 * @package od-content-visibility
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'od_register_tag_visitors', 'odcv_register_tag_visitor' );
