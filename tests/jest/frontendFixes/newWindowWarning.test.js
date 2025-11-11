/**
 * Tests for New Window Warning tooltip
 */

describe( 'New Window Warning Tooltip', () => {
	let NewWindowWarning;
	let anwwLinkTooltip;

	beforeEach( async () => {
		// Reset DOM
		document.body.innerHTML = '';
		document.head.innerHTML = '';

		// Reset window size to a standard viewport
		Object.defineProperty( window, 'innerWidth', {
			writable: true,
			configurable: true,
			value: 1024,
		} );
		Object.defineProperty( window, 'innerHeight', {
			writable: true,
			configurable: true,
			value: 768,
		} );
		Object.defineProperty( window, 'scrollY', {
			writable: true,
			configurable: true,
			value: 0,
		} );
		Object.defineProperty( window, 'scrollX', {
			writable: true,
			configurable: true,
			value: 0,
		} );

		// Clear module cache to get fresh import
		jest.resetModules();

		// Import the module
		const module = await import( '../../../src/frontendFixes/Fixes/newWindowWarning.js' );
		NewWindowWarning = module.default;
	} );

	afterEach( () => {
		// Clean up any tooltips
		document.querySelectorAll( '.anww-tooltip' ).forEach( ( el ) => el.remove() );
	} );

	describe( 'Tooltip Initialization', () => {
		test( 'creates tooltip element when initialized', () => {
			NewWindowWarning();

			anwwLinkTooltip = document.querySelector( '.anww-tooltip' );
			expect( anwwLinkTooltip ).not.toBeNull();
			expect( anwwLinkTooltip.getAttribute( 'role' ) ).toBe( 'tooltip' );
		} );

		test( 'tooltip is initially hidden', () => {
			NewWindowWarning();

			anwwLinkTooltip = document.querySelector( '.anww-tooltip' );
			expect( anwwLinkTooltip.style.display ).toBe( 'none' );
		} );
	} );

	describe( 'Tooltip Positioning and Overflow Handling', () => {
		test( 'tooltip positioning logic works without errors', () => {
			document.body.innerHTML = '<a href="http://example.com" target="_blank">Link</a>';

			NewWindowWarning();

			const link = document.querySelector( 'a' );

			// Trigger mouseenter - positioning logic should run without error
			const event = new MouseEvent( 'mouseenter', { bubbles: true, pageX: 1000, pageY: 100 } );
			link.dispatchEvent( event );

			anwwLinkTooltip = document.querySelector( '.anww-tooltip' );

			// Tooltip should be visible and positioned
			expect( anwwLinkTooltip.style.display ).toBe( 'block' );
			expect( anwwLinkTooltip.style.position ).toBe( 'absolute' );
		} );

		test( 'tooltip can be positioned at various coordinates', () => {
			document.body.innerHTML = '<a href="http://example.com" target="_blank">Link</a>';

			NewWindowWarning();

			const link = document.querySelector( 'a' );

			// Trigger mouseenter
			const event = new MouseEvent( 'mouseenter', { bubbles: true, pageX: 100, pageY: 100 } );
			link.dispatchEvent( event );

			anwwLinkTooltip = document.querySelector( '.anww-tooltip' );

			// Tooltip should be positioned and visible
			expect( anwwLinkTooltip.style.display ).toBe( 'block' );
			expect( anwwLinkTooltip.style.position ).toBe( 'absolute' );
		} );
	} );

	describe( 'Link Processing', () => {
		test( 'processes links with target="_blank"', () => {
			document.body.innerHTML = '<a href="http://example.com" target="_blank">External Link</a>';

			NewWindowWarning();

			const link = document.querySelector( 'a' );

			// Should have aria-label updated
			expect( link.getAttribute( 'aria-label' ) ).toContain( 'opens a new window' );

			// Should have external link icon
			expect( link.querySelector( '.edac-nww-external-link-icon' ) ).not.toBeNull();

			// Should be marked as processed
			expect( link.getAttribute( 'data-nww-processed' ) ).toBe( 'true' );
		} );

		test( 'shows tooltip on mouseenter', () => {
			document.body.innerHTML = '<a href="http://example.com" target="_blank">Link</a>';

			NewWindowWarning();

			const link = document.querySelector( 'a' );
			anwwLinkTooltip = document.querySelector( '.anww-tooltip' );
			expect( anwwLinkTooltip.style.display ).toBe( 'none' );

			const event = new MouseEvent( 'mouseenter', { bubbles: true, pageX: 100, pageY: 100 } );
			link.dispatchEvent( event );

			expect( anwwLinkTooltip.style.display ).toBe( 'block' );
		} );

		test( 'hides tooltip on mouseleave after delay', ( done ) => {
			document.body.innerHTML = '<a href="http://example.com" target="_blank">Link</a>';

			NewWindowWarning();

			const link = document.querySelector( 'a' );
			anwwLinkTooltip = document.querySelector( '.anww-tooltip' );

			const enterEvent = new MouseEvent( 'mouseenter', { bubbles: true, pageX: 100, pageY: 100 } );
			link.dispatchEvent( enterEvent );

			expect( anwwLinkTooltip.style.display ).toBe( 'block' );

			const leaveEvent = new MouseEvent( 'mouseleave', { bubbles: true } );
			link.dispatchEvent( leaveEvent );

			// Should hide after delay (300ms)
			setTimeout( () => {
				expect( anwwLinkTooltip.style.display ).toBe( 'none' );
				done();
			}, 350 );
		} );
	} );
} );
