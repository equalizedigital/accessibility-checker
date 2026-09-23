/**
 * Build the axe context for a page scan.
 *
 * While a page is being edited Elementor renders it inside the edit area, with
 * the theme template around that area and Elementor's own editor UI inside it.
 * Scoping the scan to the edit area in that context keeps the results to the
 * page itself. Published pages are scanned unscoped.
 *
 * @param {Array}    exclusions Selectors excluded from every scan.
 * @param {Document} doc        Document to look for the edit area in.
 * @return {Object} axe context.
 */
export const EDIT_AREA_SELECTOR = '.elementor-edit-mode[data-elementor-type="wp-page"]';

// Editor-only containers Elementor renders inside the edit area.
export const EDITOR_UI_EXCLUSIONS = [
	'#elementor-add-new-section',
	'.elementor-add-section',
	'.elementor-empty-view',
];

export function buildScanContext( exclusions = [], doc = document ) {
	const context = { exclude: [ ...exclusions ] };
	const editArea = doc.querySelector( EDIT_AREA_SELECTOR );

	if ( editArea ) {
		context.include = [ editArea ];
		context.exclude = [ ...exclusions, ...EDITOR_UI_EXCLUSIONS ];
	}

	return context;
}
