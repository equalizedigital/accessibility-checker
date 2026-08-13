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

import '../../../src/frontendHighlighterApp/components/HighlightPanel.js';

const issues = [
	{ id: '1', rule_title: 'Missing alt text', rule_type: 'error', object: '<img>' },
	{ id: '2', rule_title: 'Low contrast', rule_type: 'warning', object: '<p>x</p>' },
];

const mountPanel = () => {
	const el = document.createElement( 'edac-highlight-panel' );
	document.body.appendChild( el );
	return el;
};

describe( 'edac-highlight-panel', () => {
	afterEach( () => {
		document.body.innerHTML = '';
	} );

	test( 'registers the custom element and its child components', () => {
		expect( customElements.get( 'edac-highlight-panel' ) ).toBeDefined();
		expect( customElements.get( 'edac-highlight-menu' ) ).toBeDefined();
		expect( customElements.get( 'edac-highlight-issue-view' ) ).toBeDefined();
	} );

	test( 'is hidden until the open attribute is set', () => {
		const el = mountPanel();
		expect( el.hasAttribute( 'open' ) ).toBe( false );
		el.open = true;
		expect( el.hasAttribute( 'open' ) ).toBe( true );
	} );

	test( 'shows the empty state when there is no current issue, and the issue view when there is', () => {
		const el = mountPanel();
		el.issues = issues;

		expect( el.emptyStateEl.style.display ).toBe( 'block' );
		expect( el.issueView.style.display ).toBe( 'none' );

		el.currentIndex = 0;
		expect( el.emptyStateEl.style.display ).toBe( 'none' );
		expect( el.issueView.style.display ).toBe( 'block' );
		expect( el.issueView.issue ).toBe( issues[ 0 ] );
	} );

	test( 'summary counts errors/warnings and disables nav when there are no issues', () => {
		const el = mountPanel();
		el.issues = [];
		expect( el.prevButton.disabled ).toBe( true );
		expect( el.nextButton.disabled ).toBe( true );

		el.issues = issues;
		expect( el.prevButton.disabled ).toBe( false );
		expect( el.nextButton.disabled ).toBe( false );
		expect( el.summaryEl.textContent ).toContain( '2' );
	} );

	test( 'pagination text reflects currentIndex / total', () => {
		const el = mountPanel();
		el.issues = issues;
		el.currentIndex = 1;
		expect( el.paginationEl.textContent ).toBe( '2 / 2' );
	} );

	test( 'clicking prev/next and close dispatch the corresponding events', () => {
		const el = mountPanel();
		el.issues = issues; // prev/next are disabled (and inert to .click()) until there are issues.
		const events = [];
		[ 'edac-nav-previous', 'edac-nav-next', 'edac-panel-close' ].forEach( ( name ) =>
			document.addEventListener( name, () => events.push( name ) ),
		);

		el.prevButton.click();
		el.nextButton.click();
		el.closeButton.click();

		expect( events ).toEqual( [ 'edac-nav-previous', 'edac-nav-next', 'edac-panel-close' ] );
	} );

	test( 'menu items reflect docked/stylesDisabled/userCanEdit state', () => {
		const el = mountPanel();
		el.userCanEdit = true;
		el.stylesDisabled = false;

		let items = el.menu.items;
		expect( items.find( ( i ) => i.id === 'rescan' ).hidden ).toBe( false );
		expect( items.find( ( i ) => i.id === 'disable-styles' ).label ).toBe( 'Disable Styles' );

		el.stylesDisabled = true;
		items = el.menu.items;
		expect( items.find( ( i ) => i.id === 'disable-styles' ).label ).toBe( 'Enable Styles' );

		el.docked = true;
		items = el.menu.items;
		expect( items.find( ( i ) => i.id === 'dock' ).label ).toBe( 'Undock Panel' );
	} );

	test( 'clicking the move menu item toggles position and updates the move label, without a prior drag', () => {
		const el = mountPanel();
		expect( el.position ).toBe( 'right' );

		el.menu.openMenu();
		el.menu.shadowRoot.querySelector( '[data-item-id="move"]' ).click();

		expect( el.position ).toBe( 'left' );
		expect( el.menu.items.find( ( i ) => i.id === 'move' ).label ).toBe( 'Move to Right' );
	} );

	test( 'edac-panel-position-changed is dispatched with the new position on move', () => {
		const el = mountPanel();
		const handler = jest.fn();
		document.addEventListener( 'edac-panel-position-changed', handler );

		el.menu.openMenu();
		el.menu.shadowRoot.querySelector( '[data-item-id="move"]' ).click();

		expect( handler ).toHaveBeenCalledTimes( 1 );
		expect( handler.mock.calls[ 0 ][ 0 ].detail ).toEqual( { position: 'left', docked: false } );

		document.removeEventListener( 'edac-panel-position-changed', handler );
	} );

	test( 'a drag (simulated by setting _dragged) causes the next move click to reset rather than flip sides', () => {
		const el = mountPanel();
		el.position = 'left';
		el._dragged = true;
		el._syncMenuItems();

		expect( el.menu.items.find( ( i ) => i.id === 'move' ).label ).toBe( 'Reset Position' );

		el.menu.openMenu();
		el.menu.shadowRoot.querySelector( '[data-item-id="move"]' ).click();

		expect( el.dragged ).toBe( false );
		// Position side is unchanged by a reset (only the drag offset is cleared).
		expect( el.position ).toBe( 'left' );
	} );

	test( 'edac-open-fix-settings from the nested issue view bubbles all the way out to document', () => {
		const el = mountPanel();
		el.issues = issues;
		el.currentIndex = 0;
		el.userCanFix = true;
		el.fix = { fields: '<input />', group1: { group_name: 'Alt Text' } };

		const handler = jest.fn();
		document.addEventListener( 'edac-open-fix-settings', handler );

		el.issueView.shadowRoot.querySelector( '.edac-fix-settings--button--open' ).click();

		expect( handler ).toHaveBeenCalledTimes( 1 );

		document.removeEventListener( 'edac-open-fix-settings', handler );
	} );
} );
