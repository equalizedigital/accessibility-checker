import { overlayHideSelectors } from '../config/exclusions';

const STYLE_ID = 'edac-scan-hide-overlays';

/**
 * Temporarily hide absolutely-positioned UI overlays while axe runs.
 *
 * Excluding an element from the scan context only drops it from the results;
 * axe still sees it when it builds the element stack for color contrast. An
 * overlay painted above page content (Elementor's element overlay, our own
 * highlight markers) makes axe report the content's contrast as incomplete
 * ("bgOverlap") instead of as a violation, so real issues go unreported.
 * These overlays are out of flow, so hiding them doesn't shift the layout.
 *
 * @param {Document} doc       The document being scanned.
 * @param {Array}    selectors Selectors to hide. Defaults to overlayHideSelectors.
 * @return {Function} Call to restore the overlays.
 */
export function hideOverlaysDuringScan( doc = document, selectors = overlayHideSelectors ) {
	if ( ! doc?.head || ! selectors.length || doc.getElementById( STYLE_ID ) ) {
		return () => {};
	}

	const style = doc.createElement( 'style' );
	style.id = STYLE_ID;
	style.textContent = `${ selectors.join( ',' ) }{display:none !important;}`;
	doc.head.appendChild( style );

	return () => style.remove();
}
