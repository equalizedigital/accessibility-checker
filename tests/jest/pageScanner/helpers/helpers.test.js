/**
 * Tests for the shared hidden-element helpers.
 *
 * The rule tests cover how the checks use these helpers. These tests cover the
 * helpers themselves, including that a shared cache means each ancestor's style
 * is only read once.
 */
import { isElementVisible, isScreenReaderOnly } from '../../../../src/pageScanner/helpers/helpers';

describe( 'isElementVisible', () => {
	beforeEach( () => {
		document.body.innerHTML = '';
	} );

	test.each( [
		[ 'visible element', '<div><p id="target">Text</p></div>', true ],
		[ 'display:none on the element', '<p id="target" style="display: none;">Text</p>', false ],
		[ 'display:none on an ancestor', '<div style="display: none;"><div><p id="target">Text</p></div></div>', false ],
		[ 'opacity:0 on an ancestor', '<div style="opacity: 0;"><p id="target">Text</p></div>', false ],
		[ 'visibility:hidden on the element', '<p id="target" style="visibility: hidden;">Text</p>', false ],
		[ 'visibility:hidden inherited from an ancestor', '<div style="visibility: hidden;"><p id="target">Text</p></div>', false ],
		[ 'visibility:visible inside a visibility:hidden ancestor', '<div style="visibility: hidden;"><p id="target" style="visibility: visible;">Text</p></div>', true ],
		[ 'visibility:visible inside a display:none ancestor', '<div style="display: none;"><p id="target" style="visibility: visible;">Text</p></div>', false ],
	] )( '%s', ( name, html, expected ) => {
		document.body.innerHTML = html;
		expect( isElementVisible( document.getElementById( 'target' ) ) ).toBe( expected );
	} );

	test( 'returns false when there is no element', () => {
		expect( isElementVisible( null ) ).toBe( false );
	} );

	test( 'reads the style of a shared ancestor once when a cache is passed', () => {
		document.body.innerHTML = '<div id="wrap"><p id="a">A</p><p id="b">B</p><p id="c">C</p></div>';
		const spy = jest.spyOn( window, 'getComputedStyle' );
		const cache = new Map();

		[ 'a', 'b', 'c' ].forEach( ( id ) => isElementVisible( document.getElementById( id ), cache ) );

		const readsOfWrapper = spy.mock.calls.filter( ( [ el ] ) => el.id === 'wrap' ).length;
		spy.mockRestore();

		expect( readsOfWrapper ).toBe( 1 );
	} );
} );

describe( 'isScreenReaderOnly', () => {
	beforeEach( () => {
		document.body.innerHTML = '';
	} );

	test.each( [
		[ 'clip-path inset(50%)', '<span id="target" style="clip-path: inset(50%);">Text</span>', true ],
		[ 'clip rect on a positioned element', '<span id="target" style="position: absolute; clip: rect(0px, 0px, 0px, 0px);">Text</span>', true ],
		[ '1px box with overflow hidden', '<span id="target" style="position: absolute; width: 1px; height: 1px; overflow: hidden;">Text</span>', true ],
		[ 'sr-only ancestor', '<div style="position: absolute; width: 1px; height: 1px; overflow: hidden;"><span id="target">Text</span></div>', true ],
		[ 'clip rect without positioning', '<span id="target" style="clip: rect(0px, 0px, 0px, 0px);">Text</span>', false ],
		[ 'plain element', '<span id="target">Text</span>', false ],
		[ 'offscreen only', '<span id="target" style="position: absolute; left: -9999px;">Text</span>', false ],
	] )( '%s', ( name, html, expected ) => {
		document.body.innerHTML = html;
		expect( isScreenReaderOnly( document.getElementById( 'target' ) ) ).toBe( expected );
	} );

	test( 'returns false when there is no element', () => {
		expect( isScreenReaderOnly( null ) ).toBe( false );
	} );

	test( 'reads the style of a shared ancestor once when a cache is passed', () => {
		document.body.innerHTML = '<div id="wrap"><span id="a">A</span><span id="b">B</span><span id="c">C</span></div>';
		const spy = jest.spyOn( window, 'getComputedStyle' );
		const cache = new Map();

		[ 'a', 'b', 'c' ].forEach( ( id ) => isScreenReaderOnly( document.getElementById( id ), cache ) );

		const readsOfWrapper = spy.mock.calls.filter( ( [ el ] ) => el.id === 'wrap' ).length;
		spy.mockRestore();

		expect( readsOfWrapper ).toBe( 1 );
	} );
} );
