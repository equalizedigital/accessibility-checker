import '../../../src/frontendHighlighterApp/components/HighlightLandmarkLabel.js';

describe( 'edac-landmark-label', () => {
	afterEach( () => {
		document.body.innerHTML = '';
	} );

	test( 'registers the custom element', () => {
		expect( customElements.get( 'edac-landmark-label' ) ).toBeDefined();
	} );

	test( 'renders the given text in an aria-hidden badge', () => {
		const el = document.createElement( 'edac-landmark-label' );
		el.text = 'Landmark: Navigation';
		document.body.appendChild( el );

		const label = el.shadowRoot.querySelector( '.edac-landmark-label' );
		expect( label.textContent ).toBe( 'Landmark: Navigation' );
		expect( label.getAttribute( 'aria-hidden' ) ).toBe( 'true' );
	} );

	test( 'left/top properties position the host element', () => {
		const el = document.createElement( 'edac-landmark-label' );
		document.body.appendChild( el );

		el.left = 120;
		el.top = 340;

		expect( el.style.left ).toBe( '120px' );
		expect( el.style.top ).toBe( '340px' );
	} );

	test( 'the host is styled non-interactive, matching the original inline pointer-events:none label', () => {
		const el = document.createElement( 'edac-landmark-label' );
		document.body.appendChild( el );

		const styleText = el.shadowRoot.querySelector( 'style' ).textContent;
		expect( styleText ).toContain( 'pointer-events: none' );
	} );
} );
