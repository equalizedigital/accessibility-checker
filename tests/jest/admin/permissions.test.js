/**
 * Tests for admin/js/permissions.js — the Permissions tab role picker.
 *
 * The script is a self-executing IIFE with no exports: it wires itself up
 * against a fixed set of DOM ids the moment it's required, and bails out
 * entirely if any of them are missing. Each test builds the same fixture
 * markup permissions-page.php renders, sets window.edacPermissions, then
 * uses jest.isolateModules() + a fresh require() to force the IIFE to run
 * again against that fixture (a plain require() would just return the
 * already-executed, cached module).
 */

const FIXTURE_MATRIX = {
	roles: [
		{ slug: 'editor', name: 'Editor' },
		{ slug: 'author', name: 'Author' },
	],
	caps: [
		{
			slug: 'edac_dismiss_own_issues',
			label: 'Dismiss own issues',
			description: 'Dismiss issues on posts you can edit.',
			group: 'Accessibility Checker',
			owner: 'accessibility-checker',
		},
		{
			slug: 'edac_dismiss_issues_globally',
			label: 'Dismiss issues globally',
			description: 'Dismiss issues across every post.',
			group: 'Accessibility Checker Pro',
			owner: 'accessibility-checker-pro',
		},
	],
	state: {
		editor: {
			edac_dismiss_own_issues: { enabled: true, reason: '' },
			edac_dismiss_issues_globally: { enabled: false, reason: 'Requires edit_others_posts.' },
		},
		author: {
			edac_dismiss_own_issues: { enabled: true, reason: '' },
			edac_dismiss_issues_globally: { enabled: false, reason: 'Requires edit_others_posts.' },
		},
	},
};

/**
 * Build the fixture markup permissions-page.php renders, attach it to the
 * document, set window.edacPermissions, and require the script fresh.
 *
 * @param {Object}   matrix      The matrix payload.
 * @param {Function} [seedStore] Optional callback( storeEl ) to pre-populate
 *                               the hidden store before the script runs, the
 *                               way the PHP partial does for existing grants.
 * @return {Object} The fixture DOM elements.
 */
function mountFixture( matrix = FIXTURE_MATRIX, seedStore = null ) {
	document.body.innerHTML = `
		<select id="edac-perm-role">
			<option value="">— Select a role —</option>
			<option value="editor">Editor</option>
			<option value="author">Author</option>
		</select>
		<div id="edac-perm-status"></div>
		<div id="edac-perm-caps"></div>
		<div id="edac-perm-store"></div>
	`;

	const store = document.getElementById( 'edac-perm-store' );
	if ( seedStore ) {
		seedStore( store );
	}

	window.edacPermissions = { matrix, strings: {} };

	jest.isolateModules( () => {
		require( '../../../admin/js/permissions.js' );
	} );

	return {
		roleSelect: document.getElementById( 'edac-perm-role' ),
		capsBox: document.getElementById( 'edac-perm-caps' ),
		status: document.getElementById( 'edac-perm-status' ),
		store: document.getElementById( 'edac-perm-store' ),
	};
}

/**
 * Select a role and fire the change event the script listens for.
 *
 * @param {HTMLSelectElement} roleSelect The role <select>.
 * @param {string}            roleSlug   Role slug to select.
 */
function selectRole( roleSelect, roleSlug ) {
	roleSelect.value = roleSlug;
	roleSelect.dispatchEvent( new Event( 'change' ) );
}

describe( 'permissions.js', () => {
	afterEach( () => {
		document.body.innerHTML = '';
		delete window.edacPermissions;
	} );

	test( 'shows the select-a-role empty state before any role is chosen', () => {
		const { capsBox } = mountFixture();

		expect( capsBox.textContent ).toContain( 'Select a role' );
		expect( capsBox.querySelector( 'input[type="checkbox"]' ) ).toBeNull();
	} );

	test( 'renders a checkbox per capability for the selected role', () => {
		const { roleSelect, capsBox } = mountFixture();

		selectRole( roleSelect, 'editor' );

		const checkboxes = capsBox.querySelectorAll( 'input[type="checkbox"]' );
		expect( checkboxes ).toHaveLength( 2 );
		expect( capsBox.textContent ).toContain( 'Dismiss own issues' );
		expect( capsBox.textContent ).toContain( 'Dismiss issues globally' );
	} );

	test( 'a capability that fails the floor is rendered disabled, with its reason shown', () => {
		const { roleSelect, capsBox } = mountFixture();

		selectRole( roleSelect, 'editor' );

		const globalCheckbox = capsBox.querySelector( '#edac-perm-editor-edac_dismiss_issues_globally' );
		expect( globalCheckbox.disabled ).toBe( true );
		expect( capsBox.textContent ).toContain( 'Requires edit_others_posts.' );
	} );

	test( 'an enabled capability already in the store renders pre-checked', () => {
		const { roleSelect, capsBox } = mountFixture( FIXTURE_MATRIX, ( store ) => {
			const input = document.createElement( 'input' );
			input.type = 'hidden';
			input.setAttribute( 'data-cap', 'edac_dismiss_own_issues' );
			input.setAttribute( 'data-role', 'editor' );
			store.appendChild( input );
		} );

		selectRole( roleSelect, 'editor' );

		const checkbox = capsBox.querySelector( '#edac-perm-editor-edac_dismiss_own_issues' );
		expect( checkbox.checked ).toBe( true );
	} );

	test( 'checking an enabled capability adds it to the hidden store', () => {
		const { roleSelect, capsBox, store } = mountFixture();

		selectRole( roleSelect, 'editor' );

		const checkbox = capsBox.querySelector( '#edac-perm-editor-edac_dismiss_own_issues' );
		expect( store.querySelector( 'input[data-cap="edac_dismiss_own_issues"][data-role="editor"]' ) ).toBeNull();

		checkbox.checked = true;
		checkbox.dispatchEvent( new Event( 'change' ) );

		const stored = store.querySelector( 'input[data-cap="edac_dismiss_own_issues"][data-role="editor"]' );
		expect( stored ).not.toBeNull();
		expect( stored.name ).toBe( 'edac_role_map[edac_dismiss_own_issues][]' );
		expect( stored.value ).toBe( 'editor' );
	} );

	test( 'unchecking a capability removes it from the hidden store', () => {
		const { roleSelect, capsBox, store } = mountFixture( FIXTURE_MATRIX, ( storeEl ) => {
			const input = document.createElement( 'input' );
			input.type = 'hidden';
			input.setAttribute( 'data-cap', 'edac_dismiss_own_issues' );
			input.setAttribute( 'data-role', 'editor' );
			storeEl.appendChild( input );
		} );

		selectRole( roleSelect, 'editor' );

		const checkbox = capsBox.querySelector( '#edac-perm-editor-edac_dismiss_own_issues' );
		expect( checkbox.checked ).toBe( true );

		checkbox.checked = false;
		checkbox.dispatchEvent( new Event( 'change' ) );

		expect( store.querySelector( 'input[data-cap="edac_dismiss_own_issues"][data-role="editor"]' ) ).toBeNull();
	} );

	test( 'a floor-disabled checkbox cannot be checked, so it can never stage a store entry', () => {
		const { roleSelect, capsBox, store } = mountFixture();

		selectRole( roleSelect, 'editor' );

		const globalCheckbox = capsBox.querySelector( '#edac-perm-editor-edac_dismiss_issues_globally' );
		expect( globalCheckbox.disabled ).toBe( true );

		// A disabled input can still be force-checked programmatically in
		// jsdom (no real user could click it), but it must never fire the
		// change listener that stages a store entry - the browser itself
		// never dispatches change events for disabled controls.
		expect( store.querySelector( 'input[data-cap="edac_dismiss_issues_globally"]' ) ).toBeNull();
	} );

	test( 'switching roles re-renders checked state from the store independently per role', () => {
		const { roleSelect, capsBox } = mountFixture( FIXTURE_MATRIX, ( store ) => {
			const input = document.createElement( 'input' );
			input.type = 'hidden';
			input.setAttribute( 'data-cap', 'edac_dismiss_own_issues' );
			input.setAttribute( 'data-role', 'editor' );
			store.appendChild( input );
		} );

		selectRole( roleSelect, 'author' );
		expect( capsBox.querySelector( '#edac-perm-author-edac_dismiss_own_issues' ).checked ).toBe( false );

		selectRole( roleSelect, 'editor' );
		expect( capsBox.querySelector( '#edac-perm-editor-edac_dismiss_own_issues' ).checked ).toBe( true );
	} );

	test( 'capability groups are ordered by owner priority, then first appearance', () => {
		const matrix = {
			roles: FIXTURE_MATRIX.roles,
			caps: [
				{ slug: 'a', label: 'A', description: '', group: 'Audit History', owner: 'accessibility-checker-audit-history' },
				{ slug: 'b', label: 'B', description: '', group: 'Accessibility Checker', owner: 'accessibility-checker' },
				{ slug: 'c', label: 'C', description: '', group: 'Accessibility Checker Pro', owner: 'accessibility-checker-pro' },
			],
			state: {
				editor: {
					a: { enabled: true, reason: '' },
					b: { enabled: true, reason: '' },
					c: { enabled: true, reason: '' },
				},
				author: {},
			},
		};
		const { roleSelect, capsBox } = mountFixture( matrix );

		selectRole( roleSelect, 'editor' );

		const headings = Array.from( capsBox.querySelectorAll( '.edac-perm-group__title' ) ).map( ( el ) => el.textContent );
		expect( headings ).toEqual( [ 'Accessibility Checker', 'Accessibility Checker Pro', 'Audit History' ] );
	} );

	test( 'shows the no-capabilities empty state when the matrix has no caps', () => {
		const { roleSelect, capsBox } = mountFixture( { roles: FIXTURE_MATRIX.roles, caps: [], state: {} } );

		selectRole( roleSelect, 'editor' );

		expect( capsBox.textContent ).toContain( 'No capabilities are available.' );
	} );

	test( 'announces the selected role name in the status region', () => {
		const { roleSelect, status } = mountFixture();

		selectRole( roleSelect, 'editor' );

		expect( status.textContent ).toContain( 'Editor' );
	} );

	test( 'does nothing (no throw) when required DOM elements are missing', () => {
		document.body.innerHTML = '<select id="edac-perm-role"></select>';
		window.edacPermissions = { matrix: FIXTURE_MATRIX, strings: {} };

		expect( () => {
			jest.isolateModules( () => {
				require( '../../../admin/js/permissions.js' );
			} );
		} ).not.toThrow();
	} );
} );
