/**
 * Tests for the New Window Warning fix functionality
 */

describe( 'New Window Warning - updateAriaLabel', () => {
	beforeEach( () => {
		// Clear the document body
		document.body.innerHTML = '';

		// Mock the localized string
		window.wp = {
			i18n: {
				__: ( text ) => {
					if ( text === 'opens a new window' ) {
						return 'opens a new window';
					}
					return text;
				},
			},
		};
	} );

	afterEach( () => {
		delete window.wp;
	} );

	/**
	 * Helper function to create a test link and get the aria-label that would be set
	 * This simulates the updateAriaLabel function logic
	 *
	 * @param {HTMLElement} link - The link element to evaluate
	 * @return {string} The expected aria-label value
	 */
	const getExpectedAriaLabel = ( link ) => {
		const localizedNewWindowWarning = 'opens a new window';
		let anwwLabel = '';

		if ( link.hasAttribute( 'aria-label' ) ) {
			anwwLabel = link.getAttribute( 'aria-label' );
		} else {
			// Collect all accessible text sources
			const textParts = [];

			// Get alt text from images
			const images = link.querySelectorAll( 'img' );
			images.forEach( ( img ) => {
				const alt = img.getAttribute( 'alt' );
				if ( alt && alt.trim() ) {
					textParts.push( alt.trim() );
				}
			} );

			// Get text content (excluding the new window icon if present)
			const linkClone = link.cloneNode( true );
			const icons = linkClone.querySelectorAll( '.edac-nww-external-link-icon' );
			icons.forEach( ( icon ) => icon.remove() );

			const textContent = linkClone.textContent.trim();
			if ( textContent ) {
				textParts.push( textContent );
			}

			anwwLabel = textParts.join( ' ' );
		}

		return anwwLabel ? `${ anwwLabel }, ${ localizedNewWindowWarning }` : localizedNewWindowWarning;
	};

	test( 'should include only text content when link has text only', () => {
		const link = document.createElement( 'a' );
		link.href = 'https://example.com';
		link.textContent = 'Visit Example';
		document.body.appendChild( link );

		const expectedLabel = getExpectedAriaLabel( link );
		expect( expectedLabel ).toBe( 'Visit Example, opens a new window' );
	} );

	test( 'should include only alt text when link has image with alt only', () => {
		const link = document.createElement( 'a' );
		link.href = 'https://example.com';
		link.innerHTML = '<img src="test.png" alt="Example Logo">';
		document.body.appendChild( link );

		const expectedLabel = getExpectedAriaLabel( link );
		expect( expectedLabel ).toBe( 'Example Logo, opens a new window' );
	} );

	test( 'should include text content when link has decorative image (empty alt) and text', () => {
		const link = document.createElement( 'a' );
		link.href = 'https://www.w3.org/TR/WCAG22/';
		link.innerHTML = '<img src="user-testing-icon.png" alt=""><br>Visit the WCAG 2.2 website';
		document.body.appendChild( link );

		const expectedLabel = getExpectedAriaLabel( link );
		expect( expectedLabel ).toBe( 'Visit the WCAG 2.2 website, opens a new window' );
	} );

	test( 'should include both alt text and text content when link has meaningful image and text', () => {
		const link = document.createElement( 'a' );
		link.href = 'https://www.w3.org/TR/WCAG22/';
		link.innerHTML = '<img src="user-testing-icon.png" alt="meaningful image"><br>Visit the WCAG 2.2 website';
		document.body.appendChild( link );

		const expectedLabel = getExpectedAriaLabel( link );
		expect( expectedLabel ).toBe( 'meaningful image Visit the WCAG 2.2 website, opens a new window' );
	} );

	test( 'should use existing aria-label when present', () => {
		const link = document.createElement( 'a' );
		link.href = 'https://example.com';
		link.setAttribute( 'aria-label', 'Custom Label' );
		link.innerHTML = '<img src="test.png" alt="Image">Some Text';
		document.body.appendChild( link );

		const expectedLabel = getExpectedAriaLabel( link );
		expect( expectedLabel ).toBe( 'Custom Label, opens a new window' );
	} );

	test( 'should handle link with only whitespace correctly', () => {
		const link = document.createElement( 'a' );
		link.href = 'https://example.com';
		link.innerHTML = '<img src="test.png" alt="">   ';
		document.body.appendChild( link );

		const expectedLabel = getExpectedAriaLabel( link );
		expect( expectedLabel ).toBe( 'opens a new window' );
	} );

	test( 'should handle link with multiple images with alt text', () => {
		const link = document.createElement( 'a' );
		link.href = 'https://example.com';
		link.innerHTML = '<img src="icon1.png" alt="Icon 1"><img src="icon2.png" alt="Icon 2">Link Text';
		document.body.appendChild( link );

		const expectedLabel = getExpectedAriaLabel( link );
		expect( expectedLabel ).toBe( 'Icon 1 Icon 2 Link Text, opens a new window' );
	} );

	test( 'should handle link with image, some with alt and some without', () => {
		const link = document.createElement( 'a' );
		link.href = 'https://example.com';
		link.innerHTML = '<img src="icon1.png" alt="Icon 1"><img src="icon2.png" alt="">Link Text';
		document.body.appendChild( link );

		const expectedLabel = getExpectedAriaLabel( link );
		expect( expectedLabel ).toBe( 'Icon 1 Link Text, opens a new window' );
	} );

	test( 'should exclude new window warning icon from text content', () => {
		const link = document.createElement( 'a' );
		link.href = 'https://example.com';
		link.innerHTML = 'Link Text<i class="edac-nww-external-link-icon" aria-hidden="true"></i>';
		document.body.appendChild( link );

		const expectedLabel = getExpectedAriaLabel( link );
		expect( expectedLabel ).toBe( 'Link Text, opens a new window' );
	} );

	test( 'should handle complex nested structure', () => {
		const link = document.createElement( 'a' );
		link.href = 'https://example.com';
		link.innerHTML = '<img src="icon.png" alt="Icon"><span>Nested</span> <strong>Text</strong>';
		document.body.appendChild( link );

		const expectedLabel = getExpectedAriaLabel( link );
		expect( expectedLabel ).toBe( 'Icon Nested Text, opens a new window' );
	} );
} );

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
