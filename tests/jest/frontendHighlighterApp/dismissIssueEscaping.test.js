/**
 * Translated strings are escaped before they are written into the panel's HTML.
 */

import { buildDismissMarkup } from '../../../src/frontendHighlighterApp/dismissIssue';

jest.mock( '@wordpress/i18n', () => ( {
	__: ( text ) => `<img src=x onerror=alert(1)>${ text }`,
	sprintf: ( format, ...args ) => require( 'util' ).format( format.replace( /%s/g, '%s' ), ...args ),
} ) );

const render = ( html ) => {
	const wrapper = document.createElement( 'div' );
	wrapper.innerHTML = html;
	return wrapper;
};

describe( 'buildDismissMarkup escaping', () => {
	test( 'markup in a translation never becomes an element', () => {
		const issue = { id: '1', ignre: '0', ignre_global: '0' };
		const open = render( buildDismissMarkup( { issue, reasons: {}, canDismiss: true, canDismissGlobal: true } ) );
		const dismissed = render( buildDismissMarkup( { issue: { ...issue, ignre: '1', ignre_global: '1' }, reasons: {}, canDismiss: true, canDismissGlobal: true } ) );

		expect( open.querySelector( 'img[src="x"]' ) ).toBeNull();
		expect( dismissed.querySelector( 'img[src="x"]' ) ).toBeNull();
		expect( open.querySelector( '[data-scope="global"]' ).textContent ).toContain( '<img src=x' );
	} );
} );
