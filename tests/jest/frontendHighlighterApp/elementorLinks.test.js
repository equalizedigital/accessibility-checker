import '../../../src/frontendHighlighterApp';

jest.mock( 'focus-trap', () => ( {
	createFocusTrap: () => ( { activate: jest.fn() } ),
} ) );

describe( 'highlighter documentation links in Elementor', () => {
	beforeAll( () => {
		jest.useFakeTimers();
		const issues = [ {
			id: 1,
			slug: 'possible_heading',
			rule_title: 'Possible Heading',
			rule_type: 'warning',
			wcag: '1.3.1',
			link: 'https://example.com/help?source=highlighter',
			object: '<p>Heading</p>',
			how_to_fix: '<p>Use a heading element.</p>',
		} ];
		jest.spyOn( window, 'XMLHttpRequest' ).mockImplementation( () => ( {
			open: jest.fn(),
			status: 200,
			responseText: JSON.stringify( { success: true, data: JSON.stringify( { issues, fixes: {} } ) } ),
			send() {
				this.onload();
			},
		} ) );
		window.edacFrontendHighlighterApp = {};
		window.dispatchEvent( new Event( 'DOMContentLoaded' ) );
	} );

	afterAll( () => {
		jest.restoreAllMocks();
		jest.clearAllTimers();
		jest.useRealTimers();
		delete window.elementorFrontend;
		delete window.edacFrontendHighlighterApp;
		document.body.innerHTML = '';
		history.replaceState( null, '', '/' );
	} );

	test.each( [
		[ 'Free editor', false, true ],
		[ 'Pro editor', true, true ],
		[ 'frontend', false, false ],
	] )( 'adds the navigation exemption only in Elementor editor mode: %s', async ( label, isPro, isEditMode ) => {
		window.elementorFrontend = { isEditMode: () => isEditMode };
		window.edacFrontendHighlighterApp = { isPro };
		document.getElementById( 'edac-highlight-panel-toggle' ).click();
		await Promise.resolve(); // Allow the mocked AJAX response to render the issue.

		const links = document.querySelectorAll( '.edac-highlight-panel-description-reference' );
		expect( links ).toHaveLength( 2 );
		expect( links[ 1 ].textContent ).toContain( isPro ? 'More Detailed Documentation' : 'How to Fix' );
		links.forEach( ( link ) => {
			// Elementor cancels clicks whose target matches this closest() selector, including nested icons.
			expect( link.querySelector( 'span' ).closest( 'a:not(.elementor-clickable)' ) === null ).toBe( isEditMode );
		} );
	} );
} );
