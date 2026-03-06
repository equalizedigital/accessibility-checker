/**
 * Tests for meta viewport scalable fix.
 */

describe( 'Meta Viewport Scalable Fix', () => {
	let MetaViewportScalableFix;

	beforeEach( async () => {
		document.head.innerHTML = '';
		document.body.innerHTML = '';
		jest.resetModules();

		const module = await import( '../../../src/frontendFixes/Fixes/metaViewportScalableFix.js' );
		MetaViewportScalableFix = module.default;
	} );

	test( 'does nothing when frontend fixes data is missing', () => {
		delete window.edac_frontend_fixes;

		expect( () => MetaViewportScalableFix() ).not.toThrow();
		expect( document.querySelector( 'meta[name="viewport"]' ) ).toBeNull();
	} );

	test( 'replaces non-scalable viewport tag when enabled', () => {
		window.edac_frontend_fixes = {
			meta_viewport_scalable: {
				enabled: true,
			},
		};

		const existingMeta = document.createElement( 'meta' );
		existingMeta.name = 'viewport';
		existingMeta.content = 'width=device-width, initial-scale=1, user-scalable=no';
		document.head.appendChild( existingMeta );

		MetaViewportScalableFix();

		const metas = document.querySelectorAll( 'meta[name="viewport"]' );
		expect( metas ).toHaveLength( 1 );
		expect( metas[ 0 ].getAttribute( 'content' ) ).toBe( 'width=device-width, initial-scale=1' );
	} );
} );
