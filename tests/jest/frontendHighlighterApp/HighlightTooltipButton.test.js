import '../../../src/frontendHighlighterApp/components/HighlightTooltipButton.js';

describe( 'edac-highlight-tooltip-button', () => {
	afterEach( () => {
		document.body.innerHTML = '';
	} );

	test( 'registers the custom element', () => {
		expect( customElements.get( 'edac-highlight-tooltip-button' ) ).toBeDefined();
	} );

	test( 'renders a shadow DOM button reflecting rule-type and aria-label', () => {
		const el = document.createElement( 'edac-highlight-tooltip-button' );
		el.setAttribute( 'rule-type', 'warning' );
		el.setAttribute( 'aria-label', 'Open details for Missing alt text, 1 of 3' );
		el.issueId = '42';
		document.body.appendChild( el );

		const button = el.shadowRoot.querySelector( 'button' );
		expect( button ).not.toBeNull();
		expect( button.className ).toBe( 'warning' );
		expect( button.getAttribute( 'aria-label' ) ).toBe( 'Open details for Missing alt text, 1 of 3' );
		expect( el.issueId ).toBe( '42' );
	} );

	test( 'dispatches edac-open-issue with the issue id on click, and the event bubbles/composes out of shadow DOM', () => {
		const el = document.createElement( 'edac-highlight-tooltip-button' );
		el.issueId = '7';
		document.body.appendChild( el );

		const handler = jest.fn();
		document.addEventListener( 'edac-open-issue', handler );

		el.shadowRoot.querySelector( 'button' ).click();

		expect( handler ).toHaveBeenCalledTimes( 1 );
		expect( handler.mock.calls[ 0 ][ 0 ].detail ).toEqual( { issueId: '7' } );

		document.removeEventListener( 'edac-open-issue', handler );
	} );

	test( 'reacts to the edac-highlight-btn-selected host class without a JS property call', () => {
		const el = document.createElement( 'edac-highlight-tooltip-button' );
		document.body.appendChild( el );

		el.classList.add( 'edac-highlight-btn-selected' );
		expect( document.querySelectorAll( '.edac-highlight-btn-selected' ) ).toHaveLength( 1 );

		el.classList.remove( 'edac-highlight-btn-selected' );
		expect( document.querySelectorAll( '.edac-highlight-btn-selected' ) ).toHaveLength( 0 );
	} );

	test( 'focus() delegates to the internal button', () => {
		const el = document.createElement( 'edac-highlight-tooltip-button' );
		document.body.appendChild( el );

		el.focus();
		expect( el.shadowRoot.activeElement ).toBe( el.shadowRoot.querySelector( 'button' ) );
	} );
} );
