/**
 * @typedef {import("web-vitals").LCPMetric} LCPMetric
 * @typedef {import("../optimization-detective/types.ts").InitializeCallback} InitializeCallback
 * @typedef {import("../optimization-detective/types.ts").InitializeArgs} InitializeArgs
 * @typedef {import("../optimization-detective/types.ts").LogFunction} LogFunction
 * @typedef {import("../optimization-detective/types.ts").ExtendElementDataFunction} ExtendElementDataFunction
 */

/**
 * Add type definition for the sake of eslint's jsdoc/no-undefined-types rule.
 *
 * @typedef {Object} ContentVisibilityAutoStateChangeEvent
 * @augments Event
 * @property {boolean} skipped - Returns true if the user agent is skipping the element's rendering, or false otherwise.
 */

/**
 * Data attribute.
 *
 * @type {string}
 */
const dataCVAutoViewportsAttribute = 'data-od-cv-auto-viewports';

/**
 * Data attribute.
 *
 * @type {string}
 */
const dataXPathAttribute = 'data-od-xpath';

/**
 * Map of XPath to its corresponding element.
 *
 * @type {Map<string, HTMLElement>}
 */
const elementsByXPath = new Map();

/**
 * @type {ExtendElementDataFunction}
 */
let extendElementData;

/**
 * Handles contentvisibilityautostatechange event on a tracked element.
 *
 * @param {ContentVisibilityAutoStateChangeEvent} event - Event.
 */
function onContentVisibilityAutoStateChange( event ) {
	if ( event.skipped ) {
		return;
	}
	const target = /** @type {HTMLElement} */ ( event.target );

	const xpath = target.getAttribute( dataXPathAttribute );

	// Capture the height of the now-visible element.
	// TODO: What about hasContentVisibilityApplied( element )
	extendElementData( xpath, {
		contentVisibilityVisibleHeight: target.getBoundingClientRect().height,
	} );

	// Now that we've determined the actual height, we don't need to keep listening for this event on this element.
	target.removeEventListener(
		'contentvisibilityautostatechange',
		onContentVisibilityAutoStateChange
	);
}

/**
 * Initializes extension.
 *
 * @since 0.1.0
 *
 * @type {InitializeCallback}
 */
export async function initialize( { extendElementData: _extendElementData } ) {
	extendElementData = _extendElementData;

	/** @type NodeListOf<HTMLElement> */
	const candidateElements = document.querySelectorAll(
		[ dataCVAutoViewportsAttribute, dataXPathAttribute ]
			.map( ( attrName ) => `[${ attrName }]` )
			.join( '' )
	);
	for ( /** @type {HTMLElement} */ const el of candidateElements ) {
		const xpath = el.getAttribute( dataXPathAttribute );
		elementsByXPath.set( xpath, el );

		el.addEventListener(
			'contentvisibilityautostatechange',
			onContentVisibilityAutoStateChange
		);
	}
}

/**
 * Determines whether the element has content-visibility applied.
 *
 * We know in the tag visitor whether we are applying `content-visibility: auto` and this fact is encoded in the data
 * attribute in addition to being added as a style rule. We can look at the data attribute to avoid calling
 * `getComputedStyle()` to improve performance. When this returns `false`, we can rely on the initial
 * `boundingClientRect` that was obtained by the intersection observer. Otherwise, we have to determine the height of
 * the element once it is displayed at the `contentvisibilityautostatechange` event.
 *
 * @todo This may not be needed, or it could be refactored to be combined with an intersection observer. Are there guarantees that CV-auto will be visible when an intersection observer callback fires? If so, it could be relied on exclusively instead of this event.
 * @since 0.1.0
 *
 * @param {HTMLElement} element - Element.
 * @return {boolean} Whether the element is visible.
 */
function hasContentVisibilityApplied( element ) {
	const value = element.getAttribute( dataCVAutoViewportsAttribute );
	if ( null === value ) {
		return false;
	}

	const ranges = value.trim().split( /\s+/ );
	for ( const range of ranges ) {
		const matches = range.match( /^(\d+)-(\d+)?$/ );
		if ( ! matches ) {
			continue;
		}
		const minViewportWidth = parseInt( matches[ 1 ], 10 );
		const maxViewportWidth =
			matches[ 2 ] !== undefined
				? parseInt( matches[ 2 ], 10 )
				: Infinity;
		if (
			window.innerWidth >= minViewportWidth &&
			window.innerWidth <= maxViewportWidth
		) {
			return true;
		}
	}

	return false;
}
