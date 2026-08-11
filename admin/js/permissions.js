/**
 * Permissions tab — role picker.
 *
 * Choose a role and toggle the capabilities it can be granted. A capability is
 * only selectable when the role's live WordPress capabilities meet its floor
 * (computed server-side, passed in edacPermissions.matrix). The checked state
 * for every role is kept in the hidden store (#edac-perm-store), which is what
 * the form actually submits, so switching roles never loses unsaved changes.
 *
 * @package
 */
( function() {
	'use strict';

	const config = window.edacPermissions || {};
	const strings = config.strings || {};
	const matrix = config.matrix || { roles: [], caps: [], state: {} };

	const roleSelect = document.getElementById( 'edac-perm-role' );
	const capsBox = document.getElementById( 'edac-perm-caps' );
	const store = document.getElementById( 'edac-perm-store' );

	if ( ! roleSelect || ! capsBox || ! store ) {
		return;
	}

	/**
	 * Whether the hidden store currently holds this capability/role pair.
	 *
	 * @param {string} cap  Capability slug.
	 * @param {string} role Role slug.
	 * @return {boolean} True when the pair is staged.
	 */
	function storeHas( cap, role ) {
		return !! store.querySelector(
			'input[data-cap="' + cap + '"][data-role="' + role + '"]'
		);
	}

	/**
	 * Add a capability/role pair to the hidden store.
	 *
	 * @param {string} cap  Capability slug.
	 * @param {string} role Role slug.
	 */
	function storeAdd( cap, role ) {
		if ( storeHas( cap, role ) ) {
			return;
		}
		const input = document.createElement( 'input' );
		input.type = 'hidden';
		input.name = 'edac_role_map[' + cap + '][]';
		input.value = role;
		input.setAttribute( 'data-cap', cap );
		input.setAttribute( 'data-role', role );
		store.appendChild( input );
	}

	/**
	 * Remove a capability/role pair from the hidden store.
	 *
	 * @param {string} cap  Capability slug.
	 * @param {string} role Role slug.
	 */
	function storeRemove( cap, role ) {
		const input = store.querySelector(
			'input[data-cap="' + cap + '"][data-role="' + role + '"]'
		);
		if ( input ) {
			input.parentNode.removeChild( input );
		}
	}

	/**
	 * Build a paragraph element with a message.
	 *
	 * @param {string} message Text to show.
	 * @return {Element} The paragraph.
	 */
	function messageEl( message ) {
		const p = document.createElement( 'p' );
		p.className = 'edac-description';
		p.textContent = message;
		return p;
	}

	/**
	 * Render the capability checkboxes for the selected role.
	 */
	function renderCaps() {
		const role = roleSelect.value;
		capsBox.innerHTML = '';

		if ( ! role ) {
			capsBox.appendChild( messageEl( strings.selectRole || 'Select a role.' ) );
			return;
		}

		const caps = matrix.caps || [];
		if ( ! caps.length ) {
			capsBox.appendChild( messageEl( strings.noCaps || 'No capabilities are available.' ) );
			return;
		}

		const state = ( matrix.state || {} )[ role ] || {};

		// Group capabilities by their display group.
		const order = [];
		const byGroup = {};
		caps.forEach( function( cap ) {
			if ( ! byGroup[ cap.group ] ) {
				byGroup[ cap.group ] = [];
				order.push( cap.group );
			}
			byGroup[ cap.group ].push( cap );
		} );

		order.forEach( function( group ) {
			const container = document.createElement( 'div' );
			container.className = 'edac-perm-group';

			const heading = document.createElement( 'h3' );
			heading.className = 'edac-perm-group__title';
			heading.textContent = group;
			container.appendChild( heading );

			byGroup[ group ].forEach( function( cap ) {
				const meta = state[ cap.slug ] || { enabled: false, reason: '' };

				const row = document.createElement( 'div' );
				row.className = 'edac-perm-cap' + ( meta.enabled ? '' : ' edac-perm-cap--disabled' );

				const label = document.createElement( 'label' );
				label.className = 'edac-perm-cap__control';

				const checkbox = document.createElement( 'input' );
				checkbox.type = 'checkbox';
				checkbox.checked = storeHas( cap.slug, role );
				checkbox.disabled = ! meta.enabled;
				checkbox.addEventListener( 'change', function() {
					if ( checkbox.checked ) {
						storeAdd( cap.slug, role );
					} else {
						storeRemove( cap.slug, role );
					}
				} );

				const name = document.createElement( 'strong' );
				name.className = 'edac-perm-cap__label';
				name.textContent = ' ' + cap.label;

				label.appendChild( checkbox );
				label.appendChild( name );
				row.appendChild( label );

				if ( cap.description ) {
					const desc = document.createElement( 'span' );
					desc.className = 'edac-description edac-perm-cap__desc';
					desc.textContent = cap.description;
					row.appendChild( desc );
				}

				if ( ! meta.enabled && meta.reason ) {
					const reason = document.createElement( 'span' );
					reason.className = 'edac-perm-cap__reason';
					reason.textContent = meta.reason;
					row.appendChild( reason );
				}

				container.appendChild( row );
			} );

			capsBox.appendChild( container );
		} );
	}

	roleSelect.addEventListener( 'change', renderCaps );
	renderCaps();
}() );
