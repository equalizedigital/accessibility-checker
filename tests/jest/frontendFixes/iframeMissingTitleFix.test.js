/**
 * Tests for iframe missing title frontend fix.
 */

describe( 'iframeMissingTitleFix', () => {
	let iframeMissingTitleFix;

	beforeEach( async () => {
		document.body.innerHTML = '';
		jest.resetModules();
		window.edac_frontend_fixes = {
			iframe_missing_title: {
				enabled: true,
			},
		};

		const module = await import( '../../../src/frontendFixes/Fixes/iframeMissingTitleFix.js' );
		iframeMissingTitleFix = module.default;
	} );

	test( 'adds fallback title using iframe hostname when src exists', () => {
		document.body.innerHTML = '<iframe src="https://www.youtube.com/embed/example"></iframe>';

		iframeMissingTitleFix();

		const iframe = document.querySelector( 'iframe' );
		expect( iframe.getAttribute( 'title' ) ).toBe( 'Embedded content from www.youtube.com' );
	} );

	test( 'adds generic fallback title when src is missing', () => {
		document.body.innerHTML = '<iframe></iframe>';

		iframeMissingTitleFix();

		const iframe = document.querySelector( 'iframe' );
		expect( iframe.getAttribute( 'title' ) ).toBe( 'Embedded content' );
	} );

	test( 'does not overwrite existing title attributes', () => {
		document.body.innerHTML = '<iframe src="https://example.com" title="Custom iframe title"></iframe>';

		iframeMissingTitleFix();

		const iframe = document.querySelector( 'iframe' );
		expect( iframe.getAttribute( 'title' ) ).toBe( 'Custom iframe title' );
	} );
} );
