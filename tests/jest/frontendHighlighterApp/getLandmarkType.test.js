/**
 * Tests for getLandmarkType utility function
 */

import { getLandmarkType } from '../../../src/frontendHighlighterApp/getLandmarkType';

/**
 * Helper to create a DOM element with optional attributes and children.
 *
 * @param {string} tagName       The HTML tag name to create.
 * @param {Object} attributes    Optional attributes to set.
 * @param {Array}  childTagNames Optional child element tag names to append.
 * @return {HTMLElement} The created element.
 */
function createElement( tagName, attributes = {}, childTagNames = [] ) {
	const el = document.createElement( tagName );
	for ( const [ key, value ] of Object.entries( attributes ) ) {
		el.setAttribute( key, value );
	}
	for ( const childTag of childTagNames ) {
		el.appendChild( document.createElement( childTag ) );
	}
	return el;
}

describe( 'getLandmarkType', () => {
	describe( 'Semantic HTML elements', () => {
		test.each( [
			[ 'header', 'Header' ],
			[ 'nav', 'Navigation' ],
			[ 'main', 'Main' ],
			[ 'aside', 'Complementary' ],
			[ 'footer', 'Footer' ],
			[ 'article', 'Article' ],
		] )( '<%s> returns %s', ( tag, expected ) => {
			const el = createElement( tag );
			expect( getLandmarkType( el ) ).toBe( expected );
		} );

		test( '<section> without accessible name returns "Section"', () => {
			const el = createElement( 'section' );
			expect( getLandmarkType( el ) ).toBe( 'Section' );
		} );

		test( '<section> with aria-label returns "Region"', () => {
			const el = createElement( 'section', { 'aria-label': 'My Region' } );
			expect( getLandmarkType( el ) ).toBe( 'Region' );
		} );

		test( '<section> with aria-labelledby returns "Region"', () => {
			const el = createElement( 'section', { 'aria-labelledby': 'heading-id' } );
			expect( getLandmarkType( el ) ).toBe( 'Region' );
		} );

		test( '<section> containing a heading returns "Region"', () => {
			const el = createElement( 'section', {}, [ 'h2' ] );
			expect( getLandmarkType( el ) ).toBe( 'Region' );
		} );

		test( '<form> without accessible name returns "Form (unlabeled)"', () => {
			const el = createElement( 'form' );
			expect( getLandmarkType( el ) ).toBe( 'Form (unlabeled)' );
		} );

		test( '<form> with aria-label returns "Form"', () => {
			const el = createElement( 'form', { 'aria-label': 'Search form' } );
			expect( getLandmarkType( el ) ).toBe( 'Form' );
		} );

		test( '<form> with aria-labelledby returns "Form"', () => {
			const el = createElement( 'form', { 'aria-labelledby': 'form-heading' } );
			expect( getLandmarkType( el ) ).toBe( 'Form' );
		} );

		test( 'unknown tag returns "Landmark"', () => {
			const el = createElement( 'div' );
			expect( getLandmarkType( el ) ).toBe( 'Landmark' );
		} );
	} );

	describe( 'ARIA role overrides', () => {
		test.each( [
			[ 'banner', 'Header' ],
			[ 'navigation', 'Navigation' ],
			[ 'main', 'Main' ],
			[ 'complementary', 'Complementary' ],
			[ 'contentinfo', 'Footer' ],
			[ 'search', 'Search' ],
			[ 'form', 'Form' ],
			[ 'region', 'Region' ],
		] )( 'role="%s" returns %s', ( role, expected ) => {
			const el = createElement( 'div', { role } );
			expect( getLandmarkType( el ) ).toBe( expected );
		} );

		test( 'unknown ARIA role returns capitalised role name', () => {
			const el = createElement( 'div', { role: 'application' } );
			expect( getLandmarkType( el ) ).toBe( 'Application' );
		} );

		test( 'role attribute takes precedence over tag name', () => {
			// <header> would normally return "Header", but role="main" should win
			const el = createElement( 'header', { role: 'main' } );
			expect( getLandmarkType( el ) ).toBe( 'Main' );
		} );

		test( 'role is case-insensitive', () => {
			const el = createElement( 'div', { role: 'NAVIGATION' } );
			expect( getLandmarkType( el ) ).toBe( 'Navigation' );
		} );
	} );
} );
