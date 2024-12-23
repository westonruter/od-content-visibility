/**
 * @typedef {import("web-vitals").LCPMetric} LCPMetric
 * @typedef {import("../optimization-detective/types.ts").InitializeCallback} InitializeCallback
 * @typedef {import("../optimization-detective/types.ts").InitializeArgs} InitializeArgs
 * @typedef {import("../optimization-detective/types.ts").FinalizeArgs} FinalizeArgs
 * @typedef {import("../optimization-detective/types.ts").FinalizeCallback} FinalizeCallback
 */

const dataCVAutoViewportsAttribute = 'data-od-cv-auto-viewports';

const dataXPathAttribute = 'data-od-xpath';

/**
 * Map of XPath to its corresponding element.
 *
 * @type {Map<string, HTMLElement>}
 */
const elementsByXPath = new Map();

/**
 * Map of XPath to the height of elements with content-visibility:auto which have been made visible.
 *
 * @type {Map<string, number>}
 */
const visibleElementHeights = new Map();

/**
 * Map of element XPath to the original boundingClientRect.
 *
 * @todo Remove.
 * @type {Map<string, number>}
 */
const originalElementHeights = new Map();

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
	const height = target.getBoundingClientRect().height;
	console.log( `[Content Visibility] Height: ${ height } for`, event.target );
	visibleElementHeights.set( xpath, height );

	// Now that we've determined the actual height, we don't need to keep listening for this event on this element.
	target.removeEventListener(
		'contentvisibilityautostatechange',
		onContentVisibilityAutoStateChange
	);

	// TODO: If the current viewport is included in the ranges of data-od-meta-content-visibility-auto, then we need to capture the
	console.info( 'contentvisibilityautostatechange', event.target, {
		originalHeight: originalElementHeights.get( xpath ),
		currentHeight: height,
		heightDiff: originalElementHeights.get( xpath ) - height,
	} );
}

/**
 * Initializes extension.
 *
 * @since 0.1.0
 *
 * @type {InitializeCallback}
 */
export async function initialize() {
	/** @type NodeListOf<HTMLElement> */
	const candidateElements = document.querySelectorAll(
		[ dataCVAutoViewportsAttribute, dataXPathAttribute ]
			.map( ( attrName ) => `[${ attrName }]` )
			.join( '' )
	);
	for ( /** @type {HTMLElement} */ const el of candidateElements ) {
		const xpath = el.getAttribute( dataXPathAttribute );
		elementsByXPath.set( xpath, el );

		originalElementHeights.set( xpath, el.getBoundingClientRect().height ); // TODO: Remove.

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

/**
 * Finalizes extension.
 *
 * @since 0.1.0
 *
 * @type {FinalizeCallback}
 * @param {FinalizeArgs} args Args.
 */
export async function finalize( { getElementData, extendElementData } ) {
	for ( const [ xpath, element ] of elementsByXPath.entries() ) {
		let contentVisibilityVisibleHeight = null;

		if ( visibleElementHeights.has( xpath ) ) {
			contentVisibilityVisibleHeight = visibleElementHeights.get( xpath );
		} else if ( ! hasContentVisibilityApplied( element ) ) {
			const elementData = getElementData( xpath );
			if ( elementData ) {
				contentVisibilityVisibleHeight =
					elementData.boundingClientRect.height;
			}
		}

		if ( contentVisibilityVisibleHeight !== null ) {
			extendElementData( xpath, {
				contentVisibilityVisibleHeight,
			} );
		}
	}
}
