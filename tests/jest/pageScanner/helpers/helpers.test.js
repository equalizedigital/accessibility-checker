/**
 * Tests for the shared hidden-element helpers.
 *
 * The rule tests cover how the checks use these helpers. These tests cover the
 * helpers themselves, including that a shared cache means each ancestor's style
 * is only read once.
 */
import { isScreenReaderOnly } from '../../../../src/pageScanner/helpers/helpers';

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
