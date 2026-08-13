import '../../../src/frontendHighlighterApp/components/HighlightMenu.js';

const ALL_ITEMS = [
	{ id: 'move', label: 'Move to Left', hidden: false },
	{ id: 'dock', label: 'Dock Panel', hidden: false },
	{ id: 'rescan', label: 'Rescan This Page', hidden: false },
	{ id: 'clear-issues', label: 'Clear Issues', hidden: false },
	{ id: 'disable-styles', label: 'Disable Styles', ariaLabel: 'Disable Page Styles', hidden: false },
];

const mountMenu = ( items = ALL_ITEMS ) => {
	const el = document.createElement( 'edac-highlight-menu' );
	document.body.appendChild( el );
	el.items = items;
	return el;
};

describe( 'edac-highlight-menu', () => {
	afterEach( () => {
		document.body.innerHTML = '';
	} );

	test( 'registers the custom element', () => {
		expect( customElements.get( 'edac-highlight-menu' ) ).toBeDefined();
	} );

	test( 'renders one menuitem per visible item, skipping hidden ones for visibility but keeping them in the DOM', () => {
		const el = mountMenu( [
			...ALL_ITEMS.slice( 0, 3 ),
			{ id: 'clear-issues', label: 'Clear Issues', hidden: true },
			{ id: 'disable-styles', label: 'Disable Styles', hidden: false },
		] );

		const items = el.shadowRoot.querySelectorAll( '[role="menuitem"]' );
		expect( items ).toHaveLength( 5 );

		const hiddenLi = el.shadowRoot.querySelector( '[data-item-id="clear-issues"]' ).closest( 'li' );
		expect( hiddenLi.hidden ).toBe( true );
	} );

	test( 'opening/closing toggles menu visibility and the trigger aria-expanded', () => {
		const el = mountMenu();
		const button = el.shadowRoot.querySelector( '.edac-highlight-menu-button' );
		const menu = el.shadowRoot.querySelector( '.edac-highlight-menu' );

		expect( el.open ).toBe( false );
		expect( menu.hidden ).toBe( true );

		button.click();
		expect( el.open ).toBe( true );
		expect( menu.hidden ).toBe( false );
		expect( button.getAttribute( 'aria-expanded' ) ).toBe( 'true' );

		button.click();
		expect( el.open ).toBe( false );
		expect( button.getAttribute( 'aria-expanded' ) ).toBe( 'false' );
	} );

	test( 'clicking a menu item dispatches its mapped event and closes the menu', () => {
		const el = mountMenu();
		el.openMenu();

		const handler = jest.fn();
		document.addEventListener( 'edac-toggle-dock', handler );

		el.shadowRoot.querySelector( '[data-item-id="dock"]' ).click();

		expect( handler ).toHaveBeenCalledTimes( 1 );
		expect( el.open ).toBe( false );

		document.removeEventListener( 'edac-toggle-dock', handler );
	} );

	test( 'each action id maps to its own distinct event', () => {
		const el = mountMenu();
		const seen = [];
		[ 'edac-move', 'edac-toggle-dock', 'edac-rescan', 'edac-clear-issues', 'edac-toggle-page-styles' ]
			.forEach( ( name ) => document.addEventListener( name, () => seen.push( name ) ) );

		[ 'move', 'dock', 'rescan', 'clear-issues', 'disable-styles' ].forEach( ( id ) => {
			el.openMenu();
			el.shadowRoot.querySelector( `[data-item-id="${ id }"]` ).click();
		} );

		expect( seen ).toEqual( [ 'edac-move', 'edac-toggle-dock', 'edac-rescan', 'edac-clear-issues', 'edac-toggle-page-styles' ] );
	} );

	test( 'Escape closes the menu and returns focus to the trigger button', () => {
		const el = mountMenu();
		el.openMenu();

		el.menu.dispatchEvent( new KeyboardEvent( 'keydown', { key: 'Escape', bubbles: true } ) );

		expect( el.open ).toBe( false );
		expect( el.shadowRoot.activeElement ).toBe( el.menuButton );
	} );

	test( 'clicking outside the component closes the menu', () => {
		const el = mountMenu();
		el.openMenu();

		document.body.dispatchEvent( new MouseEvent( 'click', { bubbles: true, composed: true } ) );

		expect( el.open ).toBe( false );
	} );

	test( 'setting items re-renders labels reactively', () => {
		const el = mountMenu();
		el.items = [
			{ id: 'disable-styles', label: 'Enable Styles', ariaLabel: 'Enable Page Styles', hidden: false },
		];

		const button = el.shadowRoot.querySelector( '[data-item-id="disable-styles"]' );
		expect( button.querySelector( 'span' ).textContent ).toBe( 'Enable Styles' );
		expect( button.getAttribute( 'aria-label' ) ).toBe( 'Enable Page Styles' );
	} );
} );
