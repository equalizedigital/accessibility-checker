/**
 * Tests for looking up a scanned element by its selector.
 */
import { findElement } from '../../../../src/pageScanner/helpers/findElement';

describe( 'findElement', () => {
	beforeEach( () => {
		document.body.innerHTML = '<main><img id="here"></main>';
	} );

	test( 'returns the element a selector points at', () => {
		expect( findElement( '#here' ) ).toBe( document.getElementById( 'here' ) );
		// axe reports selectors as an array.
		expect( findElement( [ '#here' ] ) ).toBe( document.getElementById( 'here' ) );
	} );

	test( 'returns null when the element is gone', () => {
		document.getElementById( 'here' ).remove();

		expect( findElement( '#here' ) ).toBeNull();
	} );

	test( 'returns null for a selector the browser rejects, instead of throwing', () => {
		expect( () => findElement( 'div:::nope(' ) ).not.toThrow();
		expect( findElement( 'div:::nope(' ) ).toBeNull();
		expect( findElement( undefined ) ).toBeNull();
	} );

	test( 'looks in the document it is given', () => {
		const other = document.implementation.createHTMLDocument( 'other' );
		other.body.innerHTML = '<p id="there"></p>';

		expect( findElement( '#there' ) ).toBeNull();
		expect( findElement( '#there', other ) ).toBe( other.getElementById( 'there' ) );
	} );
} );
