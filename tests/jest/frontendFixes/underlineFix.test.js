/**
 * Tests for the underline fix.
 */

describe( 'Underline fix', () => {
	let UnderlineFix;

	beforeEach( async () => {
		jest.resetModules();
		window.edac_frontend_fixes = { underline: { enabled: true, target: 'a' } };
		UnderlineFix = ( await import( '../../../src/frontendFixes/Fixes/underlineFix.js' ) ).default;
	} );

	afterEach( () => {
		delete window.edac_frontend_fixes;
		document.body.innerHTML = '';
	} );

	test( 'underlines links outside navigation', () => {
		document.body.innerHTML = '<a id="link" href="#">Link</a><nav><a id="nav" href="#">Nav</a></nav>';

		UnderlineFix();

		expect( document.getElementById( 'link' ).style.textDecoration ).toBe( 'underline' );
		expect( document.getElementById( 'nav' ).style.textDecoration ).toBe( '' );
	} );

	test( 'does not add listeners twice when run again', () => {
		document.body.innerHTML = '<a id="link" href="#">Link</a>';
		const link = document.getElementById( 'link' );
		const spy = jest.spyOn( link, 'addEventListener' );

		UnderlineFix();
		UnderlineFix();

		expect( spy ).toHaveBeenCalledTimes( 4 );
	} );

	test( 'processes links added after the first run', () => {
		document.body.innerHTML = '<a id="first" href="#">First</a>';
		UnderlineFix();

		document.body.insertAdjacentHTML( 'beforeend', '<a id="second" href="#">Second</a>' );
		UnderlineFix();

		expect( document.getElementById( 'second' ).style.textDecoration ).toBe( 'underline' );
	} );
} );
