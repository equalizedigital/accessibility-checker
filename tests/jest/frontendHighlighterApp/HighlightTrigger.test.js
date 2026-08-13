import '../../../src/frontendHighlighterApp/components/HighlightTrigger.js';

describe( 'edac-highlight-trigger', () => {
	afterEach( () => {
		document.body.innerHTML = '';
	} );

	test( 'registers the custom element', () => {
		expect( customElements.get( 'edac-highlight-trigger' ) ).toBeDefined();
	} );

	test( 'renders a shadow DOM button with the given aria-label', () => {
		const el = document.createElement( 'edac-highlight-trigger' );
		el.setAttribute( 'aria-label', 'Accessibility Checker Tools' );
		document.body.appendChild( el );

		const button = el.shadowRoot.querySelector( 'button' );
		expect( button ).not.toBeNull();
		expect( button.getAttribute( 'aria-label' ) ).toBe( 'Accessibility Checker Tools' );
	} );

	test( 'dispatches edac-toggle-panel on click, bubbling out of shadow DOM', () => {
		const el = document.createElement( 'edac-highlight-trigger' );
		document.body.appendChild( el );

		const handler = jest.fn();
		document.addEventListener( 'edac-toggle-panel', handler );

		el.shadowRoot.querySelector( 'button' ).click();

		expect( handler ).toHaveBeenCalledTimes( 1 );

		document.removeEventListener( 'edac-toggle-panel', handler );
	} );

	test( 'open property toggles the open attribute and hides the host when open', () => {
		const el = document.createElement( 'edac-highlight-trigger' );
		document.body.appendChild( el );

		expect( el.open ).toBe( false );
		expect( el.style.display ).toBe( 'block' );

		el.open = true;
		expect( el.hasAttribute( 'open' ) ).toBe( true );
		expect( el.style.display ).toBe( 'none' );

		el.open = false;
		expect( el.hasAttribute( 'open' ) ).toBe( false );
		expect( el.style.display ).toBe( 'block' );
	} );

	test( 'focus() delegates to the internal button', () => {
		const el = document.createElement( 'edac-highlight-trigger' );
		document.body.appendChild( el );

		el.focus();
		expect( el.shadowRoot.activeElement ).toBe( el.shadowRoot.querySelector( 'button' ) );
	} );
} );
