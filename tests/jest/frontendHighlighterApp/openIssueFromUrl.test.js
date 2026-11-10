import '../../../src/frontendHighlighterApp';

jest.mock( 'focus-trap', () => ( {
	createFocusTrap: () => ( { activate: jest.fn() } ),
} ) );

/**
 * A page loaded with an `edac` URL parameter opens the panel on that specific
 * issue rather than on the first one.
 */
describe( 'highlighter opening an issue from the URL', () => {
	const settle = async () => {
		for ( let i = 0; i < 100; i++ ) {
			await Promise.resolve();
		}
	};

	const issues = [
		{ id: 7, rule_title: 'Image missing alt text', rule_type: 'error', object: '<img src="a.png">' },
		{ id: 8, rule_title: 'Empty link', rule_type: 'error', object: '<a href="#"></a>' },
	];

	beforeAll( async () => {
		jest.useFakeTimers();

		jest.spyOn( window, 'XMLHttpRequest' ).mockImplementation( () => ( {
			open: jest.fn(),
			status: 200,
			responseText: JSON.stringify( { success: true, data: JSON.stringify( { issues, fixes: {} } ) } ),
			send() {
				this.onload();
			},
		} ) );

		window.edacFrontendHighlighterApp = {
			postID: 123,
			restNonce: 'test-nonce',
			restUrl: 'https://example.com/wp-json/accessibility-checker/v1',
			userCanEdit: true,
			loggedIn: true,
		};

		// The parameter is read when the highlighter starts.
		window.history.replaceState( null, '', '/?edac=8' );
		window.dispatchEvent( new Event( 'DOMContentLoaded' ) );
		await settle();
	} );

	afterAll( () => {
		jest.restoreAllMocks();
		jest.clearAllTimers();
		jest.useRealTimers();
		delete window.edacFrontendHighlighterApp;
		window.history.replaceState( null, '', '/' );
		document.body.innerHTML = '';
	} );

	test( 'shows the issue named in the URL instead of the first issue', () => {
		expect( document.getElementById( 'edac-highlight-pagination' ).textContent ).toContain( '2 of 2' );
		expect( document.querySelector( '.edac-highlight-panel-description-title' ).textContent ).toContain( 'Empty link' );
	} );
} );
