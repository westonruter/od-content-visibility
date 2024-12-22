<?php
/**
 * Content visibility tag visitor.
 *
 * @package od-content-visibility
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Tag visitor that adds content-visibility styles.
 *
 * @since 0.1.0
 * @access private
 */
class ODCV_Content_Visibility_Visitor {

	/**
	 * Visits a tag.
	 *
	 * @param OD_Tag_Visitor_Context $context Tag visitor context.
	 * @return bool Whether the tag should be tracked in URL Metrics.
	 */
	public function __invoke( OD_Tag_Visitor_Context $context ): bool {
		$processor = $context->processor;

		if ( ! is_string( $processor->get_attribute( 'class' ) ) ) {
			return false;
		}
		if ( true !== $processor->has_class( 'hentry' ) ) {
			return false;
		}

		$xpath = $processor->get_xpath();
		$id    = $processor->get_attribute( 'id' );
		if ( ! is_string( $id ) ) {
			$id = 'odcv-' . md5( $xpath );
			$processor->set_attribute( 'id', $id );
		}

		$style_rules = array();
		foreach ( $context->url_metric_group_collection as $group ) {
			$max_intersection_ratio = $group->get_element_max_intersection_ratio( $xpath );
			if ( null === $max_intersection_ratio || $max_intersection_ratio > PHP_FLOAT_EPSILON ) {
				continue;
			}

			$media_query      = 'screen';
			$media_conditions = od_generate_media_query( $group->get_minimum_viewport_width(), $group->get_maximum_viewport_width() );
			if ( null !== $media_conditions ) {
				$media_query .= " and {$media_conditions}";
			}
			$xpath_elements_map = $group->get_xpath_elements_map();
			if ( ! isset( $xpath_elements_map[ $xpath ] ) ) {
				continue;
			}
			$heights = array();
			foreach ( $xpath_elements_map[ $xpath ] as $element ) {
				$heights[] = $element->get_bounding_client_rect()['height'];
			}
			if ( count( $heights ) === 0 ) {
				continue;
			}
			$average_height = array_sum( $heights ) / count( $heights );
			$style_rules[]  = "@media {$media_query} { #{$id} { content-visibility: auto; contain-intrinsic-size: auto {$average_height}px; } }";
		}

		if ( count( $style_rules ) > 0 ) {
			$processor->append_head_html( '<style>' . join( "\n", $style_rules ) . '</style>' );
		}

		return true;
	}

}
