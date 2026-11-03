/**
 * Dismissing and reopening from the highlighter panel when the list of issues changes underneath it:
 * a global reopen brings back instances the list had hidden, the user can move to another issue while
 * a request is in flight, and an issue dismissed while selected stays reachable when issues reload.
 */

jest.mock( 'focus-trap', () => ( {
	createFocusTrap: () => ( { activate: jest.fn(), deactivate: jest.fn(), pause: jest.fn(), unpause: jest.fn(), active: true } ),
} ) );

const flush = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

// Opening the panel schedules a 100ms timer that moves focus to its close button.
const panelSettled = () => new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

const makeIssue = ( id, overrides = {} ) => ( {
	id: String( id ),
	slug: 'img_alt_missing',
	rule_title: `Rule ${ id }`,
	rule_type: 'error',
	base_rule_type: 'error',
	ignored: '0',
	ignre_global: 0,
	link: 'https://example.com/help',
	object: `<img id="img-${ id }" src="${ id }.png">`,
	selector: `#img-${ id }`,
	...overrides,
} );

describe( 'highlighter dismiss when the issue list changes', () => {
	let serverIssues;

	const loadHighlighter = ( { issues, search = '', canDismissGlobal = '' } ) => {
		jest.resetModules();
		document.body.innerHTML = issues.map( ( i ) => `<img id="img-${ i.id }" src="${ i.id }.png">` ).join( '' );
		history.replaceState( null, '', '/' + search );

		serverIssues = issues;
		jest.spyOn( window, 'XMLHttpRequest' ).mockImplementation( () => ( {
			open: jest.fn(),
			status: 200,
			responseText: '',
			send() {
				this.responseText = JSON.stringify( { success: true, data: JSON.stringify( { issues: serverIssues, fixes: {} } ) } );
				this.onload();
			},
		} ) );

		window.edacFrontendHighlighterApp = {
			ajaxurl: '/admin-ajax.php',
			postID: 1,
			nonce: 'nonce',
			restUrl: '/wp-json/accessibility-checker/v1',
			restNonce: 'nonce',
			canDismiss: '1',
			canDismissGlobal,
			dismissReasons: { accessible: { label: 'Confirmed accessible', description: '' } },
		};
		Element.prototype.scrollIntoView = jest.fn();
		window.ResizeObserver = class {
			observe() {}
			unobserve() {}
			disconnect() {}
		};

		require( '../../../src/frontendHighlighterApp' );
		window.dispatchEvent( new Event( 'DOMContentLoaded' ) );
	};

	afterEach( () => {
		jest.restoreAllMocks();
		delete window.fetch;
		delete window.edacFrontendHighlighterApp;
		document.body.innerHTML = '';
		history.replaceState( null, '', '/' );
	} );

	const openPanel = async () => {
		document.getElementById( 'edac-highlight-panel-toggle' ).click();
		await flush();
		await panelSettled();
	};

	const summary = () => document.querySelector( '.edac-highlight-panel-controls-summary' ).textContent;
	const shownRule = () => document.querySelector( '.edac-highlight-panel-description-title' ).textContent;

	test( 'a global reopen brings back instances the list had hidden', async () => {
		// Two instances share a snippet and are globally dismissed. The list hides dismissed issues
		// unless one is deep linked, so only the deep linked one is on screen.
		const dismissed = ( id ) => makeIssue( id, { rule_type: 'ignored', ignored: '1', ignre_global: 1 } );
		loadHighlighter( { issues: [ dismissed( 1 ), dismissed( 2 ) ], search: '?edac=1', canDismissGlobal: '1' } );
		// A deep link opens the panel by itself on load.
		await flush();
		await panelSettled();

		expect( document.querySelectorAll( '.edac-highlight-btn' ) ).toHaveLength( 1 );

		// The server reopens every instance, so the next load returns both as open.
		serverIssues = [ makeIssue( 1 ), makeIssue( 2 ) ];
		window.fetch = jest.fn().mockResolvedValue( { ok: true, json: () => Promise.resolve( { success: true } ) } );
		document.querySelector( '.edac-highlight-dismiss-reopen' ).click();
		await flush();
		await flush();

		expect( JSON.parse( window.fetch.mock.calls[ 0 ][ 1 ].body ) ).toMatchObject( { action: 'undismiss', largeBatch: true } );
		expect( document.querySelectorAll( '.edac-highlight-btn' ) ).toHaveLength( 2 );
		expect( summary() ).toContain( '2 issues found' );
		expect( document.querySelector( '.edac-highlight-panel-description-dismiss-toggle' ) ).not.toBeNull();
	} );

	test( 'a request that finishes after the user moved on does not pull them back', async () => {
		loadHighlighter( { issues: [ makeIssue( 1 ), makeIssue( 2 ) ] } );
		await openPanel();
		expect( shownRule() ).toContain( 'Rule 1' );

		// Dismiss issue 1, but hold the response.
		let finish;
		window.fetch = jest.fn().mockReturnValue( new Promise( ( resolve ) => {
			finish = () => resolve( { ok: true, json: () => Promise.resolve( { success: true } ) } );
		} ) );
		document.querySelector( '.edac-highlight-panel-description-dismiss-toggle' ).click();
		document.querySelector( '.edac-highlight-dismiss-form' ).dispatchEvent( new Event( 'submit', { cancelable: true } ) );
		await flush();

		// Meanwhile the user goes to the next issue.
		document.getElementById( 'edac-highlight-next' ).click();
		await flush();
		expect( shownRule() ).toContain( 'Rule 2' );
		const focusBefore = document.activeElement;

		finish();
		await flush();
		await flush();

		// Still on issue 2, focus left where the user put it, but the dismissal is counted.
		expect( shownRule() ).toContain( 'Rule 2' );
		expect( document.activeElement ).toBe( focusBefore );
		expect( summary() ).toContain( '1 Dismissed' );
	} );

	test( 'a failure after the user moved on is shown in the panel status and announced, not lost', async () => {
		loadHighlighter( { issues: [ makeIssue( 1 ), makeIssue( 2 ) ] } );
		await openPanel();

		let fail;
		window.fetch = jest.fn().mockReturnValue( new Promise( ( resolve ) => {
			fail = () => resolve( { ok: false, json: () => Promise.resolve( { message: 'Nope' } ) } );
		} ) );
		document.querySelector( '.edac-highlight-panel-description-dismiss-toggle' ).click();
		document.querySelector( '.edac-highlight-dismiss-form' ).dispatchEvent( new Event( 'submit', { cancelable: true } ) );
		await flush();

		document.getElementById( 'edac-highlight-next' ).click();
		await flush();
		expect( shownRule() ).toContain( 'Rule 2' );

		fail();
		await flush();
		await flush();

		// announce() sets the live region's text after a short delay.
		await new Promise( ( resolve ) => setTimeout( resolve, 80 ) );

		const status = document.querySelector( '.edac-highlight-panel-controls-summary' );
		expect( status.textContent ).toBe( 'Could not dismiss "Rule 1": Nope' );
		expect( status.classList.contains( 'edac-error' ) ).toBe( true );
		expect( document.getElementById( 'edac-highlight-announcer' ).textContent ).toContain( 'Could not dismiss "Rule 1": Nope' );
		// Issue 2's own controls do not carry issue 1's failure.
		expect( document.querySelector( '.edac-highlight-dismiss-error' ).textContent ).toBe( '' );
		expect( shownRule() ).toContain( 'Rule 2' );
	} );

	test( 'the counts replace a failure message and its error styling', async () => {
		loadHighlighter( { issues: [ makeIssue( 1 ), makeIssue( 2 ) ] } );
		await openPanel();

		const status = document.querySelector( '.edac-highlight-panel-controls-summary' );
		status.textContent = 'Could not dismiss "Rule 1": Nope';
		status.classList.add( 'edac-error' );

		// Moving on and dismissing something refreshes the counts.
		window.fetch = jest.fn().mockResolvedValue( { ok: true, json: () => Promise.resolve( { success: true } ) } );
		document.querySelector( '.edac-highlight-panel-description-dismiss-toggle' ).click();
		document.querySelector( '.edac-highlight-dismiss-form' ).dispatchEvent( new Event( 'submit', { cancelable: true } ) );
		await flush();

		expect( status.classList.contains( 'edac-error' ) ).toBe( false );
		expect( status.textContent ).toContain( 'issue' );
	} );

	test( 'an issue dismissed while selected is still there, with its reopen control, when issues load again', async () => {
		loadHighlighter( { issues: [ makeIssue( 1 ), makeIssue( 2 ) ] } );
		await openPanel();

		window.fetch = jest.fn().mockResolvedValue( { ok: true, json: () => Promise.resolve( { success: true } ) } );
		document.querySelector( '.edac-highlight-panel-description-dismiss-toggle' ).click();
		document.querySelector( '.edac-highlight-dismiss-form' ).dispatchEvent( new Event( 'submit', { cancelable: true } ) );
		await flush();
		expect( document.querySelector( '.edac-highlight-dismiss-reopen' ) ).not.toBeNull();

		// The server now reports issue 1 as dismissed, which the list normally hides.
		serverIssues = [ makeIssue( 1, { rule_type: 'ignored', ignored: '1' } ), makeIssue( 2 ) ];
		document.getElementById( 'edac-highlight-panel-controls-close' ).click();
		await flush();
		await openPanel();

		expect( shownRule() ).toContain( 'Rule 1' );
		expect( document.querySelector( '.edac-highlight-dismiss-reopen' ) ).not.toBeNull();
	} );
} );
