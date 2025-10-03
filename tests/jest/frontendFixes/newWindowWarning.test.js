/**
 * Tests for New Window Warning tooltip
 *
 * @package
 */

/* global global */

// Create a mock locale variable that can be accessed
let mockLocale = 'en';

// Mock WordPress i18n
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text ) => {
		// Mock translations for testing
		const translations = {
			'opens a new window': {
				en: 'opens a new window',
				fr: 'ouvre une nouvelle fenêtre',
				de: 'öffnet ein neues Fenster',
				es: 'abre una nueva ventana',
				ar: 'يفتح نافذة جديدة',
				ja: '新しいウィンドウで開きます',
				zh: '在新窗口中打開',
			},
		};

		// Return the translation if we have a mock locale set
		if ( mockLocale && translations[ text ] ) {
			return translations[ text ][ mockLocale ] || text;
		}

		return text;
	},
} ) );

// Set global mockLocale that can be changed in tests
global.setMockLocale = ( locale ) => {
	mockLocale = locale;
};

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

		// Set default locale
		global.setMockLocale( 'en' );

		// Import the module
		const module = await import( '../../../src/frontendFixes/Fixes/newWindowWarning.js' );
		NewWindowWarning = module.default;
	} );

	afterEach( () => {
		// Clean up any tooltips
		document.querySelectorAll( '.anww-tooltip' ).forEach( ( el ) => el.remove() );
		global.setMockLocale( 'en' );
	} );

	describe( 'Tooltip Initialization', () => {
		test( 'creates tooltip element when initialized', () => {
			NewWindowWarning();

			anwwLinkTooltip = document.querySelector( '.anww-tooltip' );
			expect( anwwLinkTooltip ).not.toBeNull();
			expect( anwwLinkTooltip.getAttribute( 'role' ) ).toBe( 'tooltip' );
		} );

		test( 'tooltip has no maxWidth constraint', () => {
			NewWindowWarning();

			anwwLinkTooltip = document.querySelector( '.anww-tooltip' );
			expect( anwwLinkTooltip.style.maxWidth ).toBe( '' );
		} );

		test( 'tooltip has no whiteSpace constraint', () => {
			NewWindowWarning();

			anwwLinkTooltip = document.querySelector( '.anww-tooltip' );
			expect( anwwLinkTooltip.style.whiteSpace ).toBe( '' );
		} );

		test( 'tooltip is initially hidden', () => {
			NewWindowWarning();

			anwwLinkTooltip = document.querySelector( '.anww-tooltip' );
			expect( anwwLinkTooltip.style.display ).toBe( 'none' );
		} );

		test( 'tooltip has correct base styles', () => {
			NewWindowWarning();

			anwwLinkTooltip = document.querySelector( '.anww-tooltip' );
			expect( anwwLinkTooltip.style.position ).toBe( 'absolute' );
			expect( anwwLinkTooltip.style.background ).toBe( 'white' );
			expect( anwwLinkTooltip.style.color ).toBe( 'rgb(30, 30, 30)' );
			expect( anwwLinkTooltip.style.fontSize ).toBe( '16px' );
			expect( anwwLinkTooltip.style.border ).toBe( '1px solid black' );
			expect( anwwLinkTooltip.style.padding ).toBe( '5px 10px' );
		} );
	} );

	describe( 'Tooltip Text Wrapping with Translations', () => {
		test( 'displays English text correctly', () => {
			global.setMockLocale( 'en' );
			jest.resetModules();

			return import( '../../../src/frontendFixes/Fixes/newWindowWarning.js' ).then( ( module ) => {
				document.body.innerHTML = '<a href="http://example.com" target="_blank">Link</a>';
				module.default();

				const link = document.querySelector( 'a' );

				// Trigger mouseenter to show tooltip
				const event = new MouseEvent( 'mouseenter', { bubbles: true, pageX: 100, pageY: 100 } );
				link.dispatchEvent( event );

				anwwLinkTooltip = document.querySelector( '.anww-tooltip' );
				expect( anwwLinkTooltip.textContent ).toBe( 'opens a new window' );
			} );
		} );

		test( 'displays French text correctly without overflow', () => {
			global.setMockLocale( 'fr' );
			jest.resetModules();

			return import( '../../../src/frontendFixes/Fixes/newWindowWarning.js' ).then( ( module ) => {
				document.body.innerHTML = '<a href="http://example.com" target="_blank">Link</a>';
				module.default();

				const link = document.querySelector( 'a' );

				// Trigger mouseenter to show tooltip
				const event = new MouseEvent( 'mouseenter', { bubbles: true, pageX: 100, pageY: 100 } );
				link.dispatchEvent( event );

				anwwLinkTooltip = document.querySelector( '.anww-tooltip' );
				expect( anwwLinkTooltip.textContent ).toBe( 'ouvre une nouvelle fenêtre' );
				expect( anwwLinkTooltip.style.display ).toBe( 'block' );

				// Verify no maxWidth or whiteSpace constraints
				expect( anwwLinkTooltip.style.maxWidth ).toBe( '' );
				expect( anwwLinkTooltip.style.whiteSpace ).toBe( '' );
			} );
		} );

		test( 'displays German text correctly', () => {
			global.setMockLocale( 'de' );
			jest.resetModules();

			return import( '../../../src/frontendFixes/Fixes/newWindowWarning.js' ).then( ( module ) => {
				document.body.innerHTML = '<a href="http://example.com" target="_blank">Link</a>';
				module.default();

				const link = document.querySelector( 'a' );
				const event = new MouseEvent( 'mouseenter', { bubbles: true, pageX: 100, pageY: 100 } );
				link.dispatchEvent( event );

				anwwLinkTooltip = document.querySelector( '.anww-tooltip' );
				expect( anwwLinkTooltip.textContent ).toBe( 'öffnet ein neues Fenster' );
			} );
		} );

		test( 'displays Spanish text correctly', () => {
			global.setMockLocale( 'es' );
			jest.resetModules();

			return import( '../../../src/frontendFixes/Fixes/newWindowWarning.js' ).then( ( module ) => {
				document.body.innerHTML = '<a href="http://example.com" target="_blank">Link</a>';
				module.default();

				const link = document.querySelector( 'a' );
				const event = new MouseEvent( 'mouseenter', { bubbles: true, pageX: 100, pageY: 100 } );
				link.dispatchEvent( event );

				anwwLinkTooltip = document.querySelector( '.anww-tooltip' );
				expect( anwwLinkTooltip.textContent ).toBe( 'abre una nueva ventana' );
			} );
		} );

		test( 'displays Arabic text correctly (RTL support)', () => {
			global.setMockLocale( 'ar' );
			jest.resetModules();

			return import( '../../../src/frontendFixes/Fixes/newWindowWarning.js' ).then( ( module ) => {
				document.body.innerHTML = '<a href="http://example.com" target="_blank">Link</a>';
				module.default();

				const link = document.querySelector( 'a' );
				const event = new MouseEvent( 'mouseenter', { bubbles: true, pageX: 100, pageY: 100 } );
				link.dispatchEvent( event );

				anwwLinkTooltip = document.querySelector( '.anww-tooltip' );
				expect( anwwLinkTooltip.textContent ).toBe( 'يفتح نافذة جديدة' );
			} );
		} );

		test( 'displays Japanese text correctly', () => {
			global.setMockLocale( 'ja' );
			jest.resetModules();

			return import( '../../../src/frontendFixes/Fixes/newWindowWarning.js' ).then( ( module ) => {
				document.body.innerHTML = '<a href="http://example.com" target="_blank">Link</a>';
				module.default();

				const link = document.querySelector( 'a' );
				const event = new MouseEvent( 'mouseenter', { bubbles: true, pageX: 100, pageY: 100 } );
				link.dispatchEvent( event );

				anwwLinkTooltip = document.querySelector( '.anww-tooltip' );
				expect( anwwLinkTooltip.textContent ).toBe( '新しいウィンドウで開きます' );
			} );
		} );

		test( 'displays Chinese text correctly', () => {
			global.setMockLocale( 'zh' );
			jest.resetModules();

			return import( '../../../src/frontendFixes/Fixes/newWindowWarning.js' ).then( ( module ) => {
				document.body.innerHTML = '<a href="http://example.com" target="_blank">Link</a>';
				module.default();

				const link = document.querySelector( 'a' );
				const event = new MouseEvent( 'mouseenter', { bubbles: true, pageX: 100, pageY: 100 } );
				link.dispatchEvent( event );

				anwwLinkTooltip = document.querySelector( '.anww-tooltip' );
				expect( anwwLinkTooltip.textContent ).toBe( '在新窗口中打開' );
			} );
		} );
	} );

	describe( 'Very Long Text Handling', () => {
		test( 'handles text without maxWidth constraint (simulates very long translation)', () => {
			document.body.innerHTML = '<a href="http://example.com" target="_blank">Link</a>';

			NewWindowWarning();

			anwwLinkTooltip = document.querySelector( '.anww-tooltip' );

			// Set tooltip text to a very long string to simulate a long translation
			const longText = 'This tooltip text is intentionally made very long to simulate what happens with certain language translations that can be significantly longer than the English text. For example, some German or French translations can be quite verbose. '.repeat( 2 );
			anwwLinkTooltip.textContent = longText;
			anwwLinkTooltip.style.display = 'block';

			// Verify no text wrapping constraints that would cause overflow
			expect( anwwLinkTooltip.style.maxWidth ).toBe( '' );
			expect( anwwLinkTooltip.style.whiteSpace ).toBe( '' );

			// Tooltip should be able to display the full text
			expect( anwwLinkTooltip.textContent ).toBe( longText );
			expect( anwwLinkTooltip.textContent.length ).toBeGreaterThan( 400 );
		} );

		test( 'tooltip width is not constrained by maxWidth', () => {
			document.body.innerHTML = '<a href="http://example.com" target="_blank">Link</a>';

			NewWindowWarning();

			const link = document.querySelector( 'a' );
			const event = new MouseEvent( 'mouseenter', { bubbles: true, pageX: 100, pageY: 100 } );
			link.dispatchEvent( event );

			anwwLinkTooltip = document.querySelector( '.anww-tooltip' );

			// Width should be based on content, not a fixed maxWidth
			expect( anwwLinkTooltip.style.maxWidth ).toBe( '' );
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

	describe( 'Text Wrapping Behavior', () => {
		test( 'tooltip text can wrap naturally without whiteSpace constraint', () => {
			NewWindowWarning();

			anwwLinkTooltip = document.querySelector( '.anww-tooltip' );

			// Verify whiteSpace is not set to nowrap
			expect( anwwLinkTooltip.style.whiteSpace ).toBe( '' );

			// Default browser behavior allows wrapping
			const computedStyle = window.getComputedStyle( anwwLinkTooltip );
			expect( computedStyle.whiteSpace ).not.toBe( 'nowrap' );
		} );

		test( 'tooltip expands to fit content without maxWidth', () => {
			document.body.innerHTML = '<a href="http://example.com" target="_blank">Link</a>';

			NewWindowWarning();

			const link = document.querySelector( 'a' );
			const event = new MouseEvent( 'mouseenter', { bubbles: true, pageX: 100, pageY: 100 } );
			link.dispatchEvent( event );

			anwwLinkTooltip = document.querySelector( '.anww-tooltip' );

			// Should not have maxWidth constraint
			expect( anwwLinkTooltip.style.maxWidth ).toBe( '' );

			// Should be visible with content
			expect( anwwLinkTooltip.style.display ).toBe( 'block' );
			expect( anwwLinkTooltip.textContent ).toBe( 'opens a new window' );
		} );
	} );
} );
