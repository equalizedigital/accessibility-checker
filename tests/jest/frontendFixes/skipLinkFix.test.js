/**
 * Tests for Skip Link Fix
 */

describe( 'Skip Link Fix', () => {
	let SkipLinkFix;

	beforeEach( async () => {
		// Reset DOM
		document.body.innerHTML = '<template id="skip-link-template"><a class="edac-skip-link--content" href="#main">Skip</a></template>';
		document.head.innerHTML = '';

		// Clear module cache to get fresh import
		jest.resetModules();

		// Import the module
		const module = await import( '../../../src/frontendFixes/Fixes/skipLinkFix.js' );
		SkipLinkFix = module.default;
	} );

	afterEach( () => {
		delete window.edac_frontend_fixes;
	} );

	test( 'does not throw when frontend fixes data is missing', () => {
		delete window.edac_frontend_fixes;
		expect( () => SkipLinkFix() ).not.toThrow();
	} );

	test( 'does not throw when skip link targets are empty', () => {
		window.edac_frontend_fixes = { skip_link: { targets: [] } };
		expect( () => SkipLinkFix() ).not.toThrow();
	} );
} );
