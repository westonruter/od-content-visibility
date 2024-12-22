export async function initialize() {
	const originalBoundingClientRects = new Map();
	for ( /** @type Element */ const hentry of document.querySelectorAll(
		'.hentry'
	) ) {
		const boundingClientRect = hentry.getBoundingClientRect();
		originalBoundingClientRects.set( hentry, boundingClientRect );
	}
	document.addEventListener(
		'contentvisibilityautostatechange',
		( /** @type ContentVisibilityAutoStateChangeEvent */ event ) => {
			if ( event.skipped ) {
				return;
			}
			const target = /** @type Element */ ( event.target );

			console.info(
				'contentvisibilityautostatechange',
				event.target,
				originalBoundingClientRects.get( target ),
				target.getBoundingClientRect(),
				originalBoundingClientRects.get( target ).height -
					target.getBoundingClientRect().height
			);
		}
	);
}
