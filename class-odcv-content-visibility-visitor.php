<?php
/**
 * Content visibility tag visitor.
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
 * Tag visitor that adds content-visibility styles.
 *
 * @since 0.1.0
 * @access private
 */
class ODCV_Content_Visibility_Visitor {

	/**
	 * Visible heights of elements with CV grouped by minimum viewport width.
	 *
	 * @since 0.1.0
	 * @var array<int, array<string, int>>
	 */
	private $content_visibility_visible_heights_by_minimum_viewport_width;

	/**
	 * Gets the height for the CV-auto visible element.
	 *
	 * @param OD_URL_Metric_Group $group               Group.
	 * @param positive-int        $url_metrics_post_id Post ID for the od_url_metrics post.
	 * @param string              $xpath               XPath.
	 *
	 * @return float|null Height or null if not present.
	 */
	private function get_content_visibility_visible_height( OD_URL_Metric_Group $group, int $url_metrics_post_id, string $xpath ): ?float {
		if ( null === $this->content_visibility_visible_heights_by_minimum_viewport_width ) {
			$this->content_visibility_visible_heights_by_minimum_viewport_width = array();
			foreach ( $group->get_collection() as $other_group ) {
				$content_visibility_visible_heights = get_post_meta( $url_metrics_post_id, odcv_get_content_visibility_visible_heights_post_meta_key( $other_group ), true );
				if ( is_array( $content_visibility_visible_heights ) ) {
					$this->content_visibility_visible_heights_by_minimum_viewport_width[ $other_group->get_minimum_viewport_width() ] = $content_visibility_visible_heights;
				}
			}
		}

		return $this->content_visibility_visible_heights_by_minimum_viewport_width[ $group->get_minimum_viewport_width() ][ $xpath ] ?? null;
	}

	/**
	 * Visits a tag.
	 *
	 * @param OD_Tag_Visitor_Context $context Tag visitor context.
	 */
	public function __invoke( OD_Tag_Visitor_Context $context ): void {
		$processor = $context->processor;

		// We cannot proceed if there is no od_url_metrics post created with the necessary CV postmeta.
		$url_metrics_post_id = $context->url_metrics_post_id;
		if ( null === $url_metrics_post_id ) {
			return;
		}

		// We only look at elements that have the hentry class, so we skip over any elements that don't have class attributes.
		if ( ! is_string( $processor->get_attribute( 'class' ) ) ) {
			return;
		}

		// The one class that is always present on the container elements for posts in The Loop is hentry. See get_post_class().
		if ( true !== $processor->has_class( 'hentry' ) ) {
			return;
		}

		// At this point, we know that this is a relevant element for applying CV, so we will track it in URL Metrics to obtain the required height.
		$context->track_tag();

		$xpath = $processor->get_xpath();

		// Make sure we have an ID on the element that we can use to apply the CV style rules.
		$id = $processor->get_attribute( 'id' );
		if ( ! is_string( $id ) ) {
			$id = 'odcv-' . md5( $xpath );
			$processor->set_attribute( 'id', $id );
		}

		/**
		 * Groups of URL Metrics which have CV applied.
		 *
		 * @var OD_URL_Metric_Group[] $applied_groups
		 */
		$applied_groups = array();

		$style_rules = array();
		foreach ( $context->url_metric_group_collection as $group ) {
			$max_intersection_ratio = $group->get_element_max_intersection_ratio( $xpath );
			if ( null === $max_intersection_ratio || $max_intersection_ratio > PHP_FLOAT_EPSILON ) {
				continue;
			}

			$height = $this->get_content_visibility_visible_height( $group, $url_metrics_post_id, $xpath );
			if ( null === $height ) {
				continue;
			}

			$media_query      = 'screen';
			$media_conditions = od_generate_media_query( $group->get_minimum_viewport_width(), $group->get_maximum_viewport_width() );
			if ( null !== $media_conditions ) {
				$media_query .= " and {$media_conditions}";
			}
			$style_rules[] = "@media {$media_query} { #{$id} { content-visibility: auto; contain-intrinsic-size: auto {$height}px; } }";

			$applied_groups[] = $group;
		}

		$processor->set_meta_attribute(
			'cv-auto-viewports',
			join(
				' ',
				array_map(
					static function ( OD_URL_Metric_Group $group ): string {
						$range = $group->get_minimum_viewport_width() . '-';
						if ( null !== $group->get_maximum_viewport_width() ) {
							$range .= $group->get_maximum_viewport_width();
						}
						return $range;
					},
					$applied_groups
				)
			)
		);

		if ( count( $style_rules ) > 0 ) {
			$processor->append_head_html( '<style>' . join( "\n", $style_rules ) . '</style>' );
		}
	}
}
