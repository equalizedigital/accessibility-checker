/**
 * Tests for iframe missing title frontend fix.
 */

describe( 'IframeMissingTitleFix', () => {
	let IframeMissingTitleFix;

	beforeEach( async () => {
		document.body.innerHTML = '';
		window.edac_frontend_fixes = {
			iframe_missing_title: {
				enabled: true,
				fallback_title: 'Embedded content',
			},
		};

		jest.resetModules();
		const module = await import( '../../../src/frontendFixes/Fixes/iframeMissingTitleFix.js' );
		IframeMissingTitleFix = module.default;
	} );

	test( 'adds fallback title to iframe without title', () => {
		document.body.innerHTML = '<iframe src="https://example.com/embed"></iframe>';

		IframeMissingTitleFix();

		const iframe = document.querySelector( 'iframe' );
		expect( iframe.getAttribute( 'title' ) ).toBe( 'Embedded content from example.com' );
	} );

	test( 'keeps existing non-empty title unchanged', () => {
		document.body.innerHTML = '<iframe src="https://example.com/embed" title="Video player"></iframe>';

		IframeMissingTitleFix();

		const iframe = document.querySelector( 'iframe' );
		expect( iframe.getAttribute( 'title' ) ).toBe( 'Video player' );
	} );

	test( 'adds fallback title when iframe has empty title attribute', () => {
		document.body.innerHTML = '<iframe src="https://example.com/embed" title="   "></iframe>';

		IframeMissingTitleFix();

		const iframe = document.querySelector( 'iframe' );
		expect( iframe.getAttribute( 'title' ) ).toBe( 'Embedded content from example.com' );
	} );

	test( 'uses fallback title when iframe has no src', () => {
		document.body.innerHTML = '<iframe></iframe>';

		IframeMissingTitleFix();

		const iframe = document.querySelector( 'iframe' );
		expect( iframe.getAttribute( 'title' ) ).toBe( 'Embedded content' );
	} );
} );
