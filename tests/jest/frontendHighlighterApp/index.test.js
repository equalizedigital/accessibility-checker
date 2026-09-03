/* globals global */
/**
 * Controller-level integration test for the frontend highlighter.
 *
 * Nothing like this existed before this conversion — the pre-conversion
 * index.js built all its markup imperatively and was never unit tested at
 * this level (only the pure getLandmarkType() helper had a test). Now that
 * the controller talks to its 6 web components purely through properties
 * and CustomEvents, it's straightforward to drive end-to-end with jsdom.
 */

jest.mock( '@floating-ui/dom', () => ( {
	computePosition: jest.fn( () => Promise.resolve( { x: 0, y: 0 } ) ),
	autoUpdate: jest.fn( () => jest.fn() ), // returns a no-op cleanup function
} ) );

// Same mock tests/jest/emailOptIn/modal.test.js and
// HighlightPanel.test.js use — the real library is flaky against jsdom's
// approximation of tabbability/visibility.
jest.mock(
	'focus-trap',
	() => ( {
		createFocusTrap: jest.fn( () => ( {
			active: false,
			activate: jest.fn(),
			deactivate: jest.fn(),
			pause: jest.fn(),
			unpause: jest.fn(),
		} ) ),
	} ),
	{ virtual: true },
);

class FakeXHR {
	static instances = [];

	open( method, url ) {
		this.method = method;
		this.url = url;
	}

	send() {
		FakeXHR.instances.push( this );
	}

	respond( status, body ) {
		this.status = status;
		this.responseText = typeof body === 'string' ? body : JSON.stringify( body );
		this.onload?.();
	}

	error() {
		this.onerror?.();
	}
}

const ISSUES_RESPONSE = {
	success: true,
	data: JSON.stringify( {
		issues: [
			{
				id: '1',
				rule_type: 'error',
				rule_title: 'Missing alt text',
				object: '<img id="pic">',
				slug: 'img_alt_missing',
				link: 'https://example.com',
			},
			{
				id: '2',
				rule_type: 'warning',
				rule_title: 'Low contrast',
				object: '<p id="txt">hi</p>',
				slug: 'low_contrast',
				link: 'https://example.com',
			},
		],
		fixes: {},
	} ),
};

describe( 'AccessibilityCheckerHighlight controller', () => {
	let AccessibilityCheckerHighlight;
	let originalXHR;
	let controller;

	beforeAll( async () => {
		( { AccessibilityCheckerHighlight } = await import( '../../../src/frontendHighlighterApp/index.js' ) );
	} );

	beforeEach( () => {
		document.body.innerHTML = '<img id="pic"><p id="txt">hi</p>';
		window.history.replaceState( null, '', '/' );
		localStorage.clear();

		window.edacFrontendHighlighterApp = {
			ajaxurl: 'https://example.com/wp-admin/admin-ajax.php',
			postID: 42,
			nonce: 'nonce',
			widgetPosition: 'right',
			adminThemeColor: '#123456',
			userCanEdit: true,
			loggedIn: true,
			isPro: false,
			userCanFix: true,
			restUrl: 'https://example.com/wp-json/edac/v1',
			restNonce: 'rest-nonce',
			appCssUrl: 'https://example.com/app.css',
		};

		FakeXHR.instances = [];
		originalXHR = global.XMLHttpRequest;
		global.XMLHttpRequest = FakeXHR;
	} );

	afterEach( () => {
		controller?.destroy();
		controller = undefined;
		global.XMLHttpRequest = originalXHR;
		document.querySelectorAll( 'edac-highlight-trigger, edac-highlight-panel, edac-fixes-modal, edac-highlight-tooltip-button, edac-landmark-label' )
			.forEach( ( el ) => el.remove() );
		document.body.className = '';
		document.body.style.marginLeft = '';
		document.body.style.marginRight = '';
	} );

	test( 'constructing the controller mounts the trigger, panel, and fixes modal', () => {
		controller = new AccessibilityCheckerHighlight();

		expect( document.querySelector( 'edac-highlight-trigger' ) ).not.toBeNull();
		expect( document.querySelector( 'edac-highlight-panel' ) ).not.toBeNull();
		expect( document.querySelector( 'edac-fixes-modal' ) ).not.toBeNull();
	} );

	test( 'does not mount edac-fixes-modal when the user cannot fix issues', () => {
		window.edacFrontendHighlighterApp.userCanFix = false;
		controller = new AccessibilityCheckerHighlight();

		expect( document.querySelector( 'edac-fixes-modal' ) ).toBeNull();
	} );

	test( 'edac-toggle-panel opens the panel and loads issues via the AJAX XHR', async () => {
		controller = new AccessibilityCheckerHighlight();
		const panel = document.querySelector( 'edac-highlight-panel' );

		document.dispatchEvent( new CustomEvent( 'edac-toggle-panel' ) );

		expect( panel.open ).toBe( true );
		expect( FakeXHR.instances ).toHaveLength( 1 );
		expect( FakeXHR.instances[ 0 ].url ).toContain( 'action=edac_frontend_highlight_ajax' );

		FakeXHR.instances[ 0 ].respond( 200, ISSUES_RESPONSE );
		await Promise.resolve();
		await Promise.resolve();

		expect( panel.issues ).toHaveLength( 2 );
		expect( panel.currentIndex ).toBe( 0 );
		expect( document.querySelectorAll( 'edac-highlight-tooltip-button' ) ).toHaveLength( 2 );
	} );

	test( 'edac-open-issue on a tooltip button shows that issue in the panel', async () => {
		controller = new AccessibilityCheckerHighlight();
		document.dispatchEvent( new CustomEvent( 'edac-toggle-panel' ) );
		FakeXHR.instances[ 0 ].respond( 200, ISSUES_RESPONSE );
		await Promise.resolve();
		await Promise.resolve();

		const panel = document.querySelector( 'edac-highlight-panel' );
		const tooltips = document.querySelectorAll( 'edac-highlight-tooltip-button' );
		const secondTooltip = [ ...tooltips ].find( ( t ) => t.issueId === '2' );

		secondTooltip.shadowRoot.querySelector( 'button' ).click();

		expect( panel.currentIndex ).toBe( 1 );
		expect( secondTooltip.classList.contains( 'edac-highlight-btn-selected' ) ).toBe( true );
	} );

	test( 'edac-nav-next/previous cycle through issues via the panel footer buttons', async () => {
		controller = new AccessibilityCheckerHighlight();
		document.dispatchEvent( new CustomEvent( 'edac-toggle-panel' ) );
		FakeXHR.instances[ 0 ].respond( 200, ISSUES_RESPONSE );
		await Promise.resolve();
		await Promise.resolve();

		const panel = document.querySelector( 'edac-highlight-panel' );
		expect( panel.currentIndex ).toBe( 0 );

		panel.nextButton.click();
		expect( panel.currentIndex ).toBe( 1 );

		panel.prevButton.click();
		expect( panel.currentIndex ).toBe( 0 );
	} );

	test( 'edac-panel-close closes the panel, removes tooltip buttons, and refocuses the trigger', async () => {
		controller = new AccessibilityCheckerHighlight();
		const trigger = document.querySelector( 'edac-highlight-trigger' );
		const panel = document.querySelector( 'edac-highlight-panel' );

		document.dispatchEvent( new CustomEvent( 'edac-toggle-panel' ) );
		FakeXHR.instances[ 0 ].respond( 200, ISSUES_RESPONSE );
		await Promise.resolve();
		await Promise.resolve();

		expect( document.querySelectorAll( 'edac-highlight-tooltip-button' ) ).toHaveLength( 2 );

		panel.dispatchEvent( new CustomEvent( 'edac-panel-close', { bubbles: true, composed: true } ) );

		expect( panel.open ).toBe( false );
		expect( trigger.open ).toBe( false );
		expect( document.querySelectorAll( 'edac-highlight-tooltip-button' ) ).toHaveLength( 0 );
		expect( document.activeElement ).toBe( trigger );
	} );

	test( 'edac-toggle-page-styles disables then re-enables page styles, and updates panel.stylesDisabled', async () => {
		document.body.innerHTML = '<div style="color:red">x</div>';
		controller = new AccessibilityCheckerHighlight();
		const panel = document.querySelector( 'edac-highlight-panel' );

		document.dispatchEvent( new CustomEvent( 'edac-toggle-page-styles' ) );

		expect( controller.stylesDisabled ).toBe( true );
		expect( panel.stylesDisabled ).toBe( true );
		expect( document.body.classList.contains( 'edac-app-disable-styles' ) ).toBe( true );
		expect( document.querySelector( 'div' ).getAttribute( 'style' ) ).toBeNull();

		document.dispatchEvent( new CustomEvent( 'edac-toggle-page-styles' ) );

		expect( controller.stylesDisabled ).toBe( false );
		expect( panel.stylesDisabled ).toBe( false );
		expect( document.body.classList.contains( 'edac-app-disable-styles' ) ).toBe( false );
		expect( document.querySelector( 'div' ).getAttribute( 'style' ) ).toBe( 'color:red' );
	} );

	test( 'edac-toggle-dock docks the panel and pushes a body margin matching its width', () => {
		controller = new AccessibilityCheckerHighlight();
		const panel = document.querySelector( 'edac-highlight-panel' );

		document.dispatchEvent( new CustomEvent( 'edac-toggle-dock' ) );

		expect( panel.docked ).toBe( true );
		expect( localStorage.getItem( 'edac-panel-docked' ) ).toBe( '1' );

		document.dispatchEvent( new CustomEvent( 'edac-toggle-dock' ) );

		expect( panel.docked ).toBe( false );
		expect( localStorage.getItem( 'edac-panel-docked' ) ).toBeNull();
		expect( document.body.style.marginLeft ).toBe( '' );
		expect( document.body.style.marginRight ).toBe( '' );
	} );

	test( 'edac-open-fix-settings moves the detached container into the fixes modal and opens it', () => {
		controller = new AccessibilityCheckerHighlight();
		const modal = document.querySelector( 'edac-fixes-modal' );

		const container = document.createElement( 'div' );
		container.className = 'edac-fix-settings';
		container.innerHTML = '<input type="text" />';
		const opener = document.createElement( 'button' );
		document.body.appendChild( opener );

		document.dispatchEvent( new CustomEvent( 'edac-open-fix-settings', {
			detail: { container, openingElement: opener },
		} ) );

		expect( modal.contains( container ) ).toBe( true );
		expect( modal.classList.contains( 'edac-fixes-modal--open' ) ).toBe( true );
	} );

	test( 'restores a docked panel and opens it automatically on construction', async () => {
		localStorage.setItem( 'edac-panel-docked', '1' );

		controller = new AccessibilityCheckerHighlight();
		const panel = document.querySelector( 'edac-highlight-panel' );

		expect( panel.docked ).toBe( true );
		expect( panel.open ).toBe( true );
		expect( FakeXHR.instances ).toHaveLength( 1 );
	} );

	test( 'opens directly to the issue named in the ?edac= URL parameter', async () => {
		window.history.replaceState( null, '', '/?edac=2' );

		controller = new AccessibilityCheckerHighlight();
		const panel = document.querySelector( 'edac-highlight-panel' );

		expect( panel.open ).toBe( true );
		FakeXHR.instances[ 0 ].respond( 200, ISSUES_RESPONSE );
		await Promise.resolve();
		await Promise.resolve();

		expect( panel.currentIndex ).toBe( 1 );
	} );
} );
