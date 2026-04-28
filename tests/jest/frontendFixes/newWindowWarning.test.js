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
		document.querySelectorAll( '.edac-nww-tooltip' ).forEach( ( el ) => el.remove() );
	} );

	describe( 'Tooltip Initialization', () => {
		test( 'creates tooltip element when initialized', () => {
			NewWindowWarning();

			anwwLinkTooltip = document.querySelector( '.edac-nww-tooltip' );
			expect( anwwLinkTooltip ).not.toBeNull();
			expect( anwwLinkTooltip.getAttribute( 'role' ) ).toBe( 'tooltip' );
		} );

		test( 'tooltip is initially hidden', () => {
			NewWindowWarning();

			anwwLinkTooltip = document.querySelector( '.edac-nww-tooltip' );
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

			anwwLinkTooltip = document.querySelector( '.edac-nww-tooltip' );

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

			anwwLinkTooltip = document.querySelector( '.edac-nww-tooltip' );

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
			anwwLinkTooltip = document.querySelector( '.edac-nww-tooltip' );
			expect( anwwLinkTooltip.style.display ).toBe( 'none' );

			const event = new MouseEvent( 'mouseenter', { bubbles: true, pageX: 100, pageY: 100 } );
			link.dispatchEvent( event );

			expect( anwwLinkTooltip.style.display ).toBe( 'block' );
		} );

		test( 'hides tooltip on mouseleave after delay', ( done ) => {
			document.body.innerHTML = '<a href="http://example.com" target="_blank">Link</a>';

			NewWindowWarning();

			const link = document.querySelector( 'a' );
			anwwLinkTooltip = document.querySelector( '.edac-nww-tooltip' );

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

	describe( 'Modifier Classes', () => {
		test( 'does not add icon when link is inside .anww-no-icon container', () => {
			document.body.innerHTML = '<div class="anww-no-icon"><a href="http://example.com" target="_blank">External Link</a></div>';

			NewWindowWarning();

			const link = document.querySelector( 'a' );

			// Should still update aria-label
			expect( link.getAttribute( 'aria-label' ) ).toContain( 'opens a new window' );

			// Should NOT have external link icon
			expect( link.querySelector( '.edac-nww-external-link-icon' ) ).toBeNull();

			// Should still be marked as processed
			expect( link.getAttribute( 'data-nww-processed' ) ).toBe( 'true' );
		} );

		test( 'does not add tooltip when link is inside .anww-no-tooltip container', () => {
			document.body.innerHTML = '<div class="anww-no-tooltip"><a href="http://example.com" target="_blank">External Link</a></div>';

			NewWindowWarning();

			const link = document.querySelector( 'a' );
			anwwLinkTooltip = document.querySelector( '.edac-nww-tooltip' );

			// Should still update aria-label
			expect( link.getAttribute( 'aria-label' ) ).toContain( 'opens a new window' );

			// Should still have icon
			expect( link.querySelector( '.edac-nww-external-link-icon' ) ).not.toBeNull();

			// Tooltip should remain hidden when link is hovered
			const event = new MouseEvent( 'mouseenter', { bubbles: true, pageX: 100, pageY: 100 } );
			link.dispatchEvent( event );

			expect( anwwLinkTooltip.style.display ).toBe( 'none' );
		} );

		test( 'does not add icon or tooltip when link is inside both modifier containers', () => {
			document.body.innerHTML = '<div class="anww-no-icon anww-no-tooltip"><a href="http://example.com" target="_blank">External Link</a></div>';

			NewWindowWarning();

			const link = document.querySelector( 'a' );
			anwwLinkTooltip = document.querySelector( '.edac-nww-tooltip' );

			// Should still update aria-label
			expect( link.getAttribute( 'aria-label' ) ).toContain( 'opens a new window' );

			// Should NOT have external link icon
			expect( link.querySelector( '.edac-nww-external-link-icon' ) ).toBeNull();

			// Tooltip should remain hidden
			const event = new MouseEvent( 'mouseenter', { bubbles: true, pageX: 100, pageY: 100 } );
			link.dispatchEvent( event );

			expect( anwwLinkTooltip.style.display ).toBe( 'none' );
		} );

		test( 'applies modifier classes to links using window.open onclick', () => {
			document.body.innerHTML = '<div class="anww-no-icon"><a href="#" onclick="window.open(\'http://example.com\', \'_blank\')">Link</a></div>';

			NewWindowWarning();

			const link = document.querySelector( 'a' );

			// Should still update aria-label
			expect( link.getAttribute( 'aria-label' ) ).toContain( 'opens a new window' );

			// Should NOT have external link icon due to modifier class
			expect( link.querySelector( '.edac-nww-external-link-icon' ) ).toBeNull();
		} );

		test( 'does not add icon when link is inside .edac-nww-no-icon container', () => {
			document.body.innerHTML = '<div class="edac-nww-no-icon"><a href="http://example.com" target="_blank">External Link</a></div>';

			NewWindowWarning();

			const link = document.querySelector( 'a' );

			// Should still update aria-label
			expect( link.getAttribute( 'aria-label' ) ).toContain( 'opens a new window' );

			// Should NOT have external link icon
			expect( link.querySelector( '.edac-nww-external-link-icon' ) ).toBeNull();

			// Should still be marked as processed
			expect( link.getAttribute( 'data-nww-processed' ) ).toBe( 'true' );
		} );

		test( 'does not add tooltip when link is inside .edac-nww-no-tooltip container', () => {
			document.body.innerHTML = '<div class="edac-nww-no-tooltip"><a href="http://example.com" target="_blank">External Link</a></div>';

			NewWindowWarning();

			const link = document.querySelector( 'a' );
			anwwLinkTooltip = document.querySelector( '.edac-nww-tooltip' );

			// Should still update aria-label
			expect( link.getAttribute( 'aria-label' ) ).toContain( 'opens a new window' );

			// Should still have icon
			expect( link.querySelector( '.edac-nww-external-link-icon' ) ).not.toBeNull();

			// Tooltip should remain hidden when link is hovered
			const event = new MouseEvent( 'mouseenter', { bubbles: true, pageX: 100, pageY: 100 } );
			link.dispatchEvent( event );

			expect( anwwLinkTooltip.style.display ).toBe( 'none' );
		} );

		test( 'does not add icon or tooltip when link is inside edac-nww- prefixed modifier containers', () => {
			document.body.innerHTML = '<div class="edac-nww-no-icon edac-nww-no-tooltip"><a href="http://example.com" target="_blank">External Link</a></div>';

			NewWindowWarning();

			const link = document.querySelector( 'a' );
			anwwLinkTooltip = document.querySelector( '.edac-nww-tooltip' );

			// Should still update aria-label
			expect( link.getAttribute( 'aria-label' ) ).toContain( 'opens a new window' );

			// Should NOT have external link icon
			expect( link.querySelector( '.edac-nww-external-link-icon' ) ).toBeNull();

			// Tooltip should remain hidden
			const event = new MouseEvent( 'mouseenter', { bubbles: true, pageX: 100, pageY: 100 } );
			link.dispatchEvent( event );

			expect( anwwLinkTooltip.style.display ).toBe( 'none' );
		} );

		test( 'applies edac-nww-no-icon modifier to links using window.open onclick', () => {
			document.body.innerHTML = '<div class="edac-nww-no-icon"><a href="#" onclick="window.open(\'http://example.com\', \'_blank\')">Link</a></div>';

			NewWindowWarning();

			const link = document.querySelector( 'a' );

			// Should still update aria-label
			expect( link.getAttribute( 'aria-label' ) ).toContain( 'opens a new window' );

			// Should NOT have external link icon due to modifier class
			expect( link.querySelector( '.edac-nww-external-link-icon' ) ).toBeNull();
		} );

		test( 'does not update aria-label when link is inside .anww-no-aria-label container', () => {
			document.body.innerHTML = '<div class="anww-no-aria-label"><a href="http://example.com" target="_blank">External Link</a></div>';

			NewWindowWarning();

			const link = document.querySelector( 'a' );

			// Should NOT have aria-label updated
			expect( link.getAttribute( 'aria-label' ) ).toBeNull();

			// Should still have external link icon
			expect( link.querySelector( '.edac-nww-external-link-icon' ) ).not.toBeNull();

			// Should still be marked as processed
			expect( link.getAttribute( 'data-nww-processed' ) ).toBe( 'true' );
		} );

		test( 'does not update aria-label when link is inside .edac-nww-no-aria-label container', () => {
			document.body.innerHTML = '<div class="edac-nww-no-aria-label"><a href="http://example.com" target="_blank">External Link</a></div>';

			NewWindowWarning();

			const link = document.querySelector( 'a' );

			// Should NOT have aria-label updated
			expect( link.getAttribute( 'aria-label' ) ).toBeNull();

			// Should still have external link icon
			expect( link.querySelector( '.edac-nww-external-link-icon' ) ).not.toBeNull();

			// Should still be marked as processed
			expect( link.getAttribute( 'data-nww-processed' ) ).toBe( 'true' );
		} );

		test( 'does not update aria-label for window.open links inside .edac-nww-no-aria-label container', () => {
			document.body.innerHTML = '<div class="edac-nww-no-aria-label"><a href="#" onclick="window.open(\'http://example.com\', \'_blank\')">Link</a></div>';

			NewWindowWarning();

			const link = document.querySelector( 'a' );

			// Should NOT have aria-label updated
			expect( link.getAttribute( 'aria-label' ) ).toBeNull();

			// Should still have external link icon
			expect( link.querySelector( '.edac-nww-external-link-icon' ) ).not.toBeNull();
		} );

		test( 'disables all NWW features when link is inside .anww-disabled container', () => {
			document.body.innerHTML = '<div class="anww-disabled"><a href="http://example.com" target="_blank">External Link</a></div>';

			NewWindowWarning();

			const link = document.querySelector( 'a' );
			const anwwLinkTooltip = document.querySelector( '.edac-nww-tooltip' );

			// Should NOT have aria-label updated
			expect( link.getAttribute( 'aria-label' ) ).toBeNull();

			// Should NOT have external link icon
			expect( link.querySelector( '.edac-nww-external-link-icon' ) ).toBeNull();

			// Tooltip should remain hidden when link is hovered
			const event = new MouseEvent( 'mouseenter', { bubbles: true, pageX: 100, pageY: 100 } );
			link.dispatchEvent( event );
			expect( anwwLinkTooltip.style.display ).toBe( 'none' );

			// Should still be marked as processed
			expect( link.getAttribute( 'data-nww-processed' ) ).toBe( 'true' );
		} );

		test( 'disables all NWW features when link is inside .edac-nww-disabled container', () => {
			document.body.innerHTML = '<div class="edac-nww-disabled"><a href="http://example.com" target="_blank">External Link</a></div>';

			NewWindowWarning();

			const link = document.querySelector( 'a' );
			const anwwLinkTooltip = document.querySelector( '.edac-nww-tooltip' );

			// Should NOT have aria-label updated
			expect( link.getAttribute( 'aria-label' ) ).toBeNull();

			// Should NOT have external link icon
			expect( link.querySelector( '.edac-nww-external-link-icon' ) ).toBeNull();

			// Tooltip should remain hidden when link is hovered
			const event = new MouseEvent( 'mouseenter', { bubbles: true, pageX: 100, pageY: 100 } );
			link.dispatchEvent( event );
			expect( anwwLinkTooltip.style.display ).toBe( 'none' );

			// Should still be marked as processed
			expect( link.getAttribute( 'data-nww-processed' ) ).toBe( 'true' );
		} );

		test( 'disables all NWW features for window.open links inside .edac-nww-disabled container', () => {
			document.body.innerHTML = '<div class="edac-nww-disabled"><a href="#" onclick="window.open(\'http://example.com\', \'_blank\')">Link</a></div>';

			NewWindowWarning();

			const link = document.querySelector( 'a' );

			// Should NOT have aria-label updated
			expect( link.getAttribute( 'aria-label' ) ).toBeNull();

			// Should NOT have external link icon
			expect( link.querySelector( '.edac-nww-external-link-icon' ) ).toBeNull();

			// Should still be marked as processed
			expect( link.getAttribute( 'data-nww-processed' ) ).toBe( 'true' );
		} );
	} );
} );
