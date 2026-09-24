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
	const status = document.getElementById( 'edac-perm-status' );
	const store = document.getElementById( 'edac-perm-store' );

	if ( ! roleSelect || ! capsBox || ! status || ! store ) {
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
	 * Build an empty-state element with a title and optional message.
	 *
	 * @param {string} title   Empty-state title.
	 * @param {string} message Supporting text to show.
	 * @return {Element} The empty state.
	 */
	function emptyStateEl( title, message = '' ) {
		const container = document.createElement( 'div' );
		container.className = 'edac-perm-empty';

		const iconWrap = document.createElement( 'span' );
		iconWrap.className = 'edac-perm-empty__icon-wrap';
		iconWrap.setAttribute( 'aria-hidden', 'true' );

		const icon = document.createElement( 'span' );
		icon.className = 'dashicons dashicons-groups edac-perm-empty__icon';
		iconWrap.appendChild( icon );
		container.appendChild( iconWrap );

		const heading = document.createElement( 'h3' );
		heading.className = 'edac-perm-empty__title';
		heading.textContent = title;
		container.appendChild( heading );

		if ( ! message ) {
			return container;
		}

		const p = document.createElement( 'p' );
		p.className = 'edac-perm-empty__message';
		p.textContent = message;
		container.appendChild( p );

		return container;
	}

	/**
	 * Render the capability checkboxes for the selected role.
	 */
	function renderCaps() {
		const role = roleSelect.value;
		capsBox.innerHTML = '';
		status.textContent = '';

		if ( ! role ) {
			capsBox.appendChild( emptyStateEl( strings.selectRoleTitle || 'Select a role', strings.selectRole || 'Select a role to see the capabilities it can be granted.' ) );
			return;
		}

		const caps = matrix.caps || [];
		if ( ! caps.length ) {
			capsBox.appendChild( emptyStateEl( strings.noCaps || 'No capabilities are available.' ) );
			return;
		}

		const state = ( matrix.state || {} )[ role ] || {};

		// Group capabilities by their display group.
		const order = [];
		const groupIndexes = {};
		const groupPriorities = {};
		const ownerPriorities = {
			'accessibility-checker': 0,
			'accessibility-checker-pro': 1,
			'accessibility-checker-audit-history': 2,
		};
		const byGroup = {};
		caps.forEach( function( cap ) {
			if ( ! byGroup[ cap.group ] ) {
				byGroup[ cap.group ] = [];
				groupIndexes[ cap.group ] = order.length;
				groupPriorities[ cap.group ] = Object.prototype.hasOwnProperty.call( ownerPriorities, cap.owner ) ? ownerPriorities[ cap.owner ] : 3;
				order.push( cap.group );
			}
			byGroup[ cap.group ].push( cap );
		} );

		order.sort( function( firstGroup, secondGroup ) {
			return groupPriorities[ firstGroup ] - groupPriorities[ secondGroup ] || groupIndexes[ firstGroup ] - groupIndexes[ secondGroup ];
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
				const describedBy = [];

				const row = document.createElement( 'div' );
				row.className = 'edac-perm-cap' + ( meta.enabled ? '' : ' edac-perm-cap--disabled' );

				const label = document.createElement( 'label' );
				label.className = 'edac-perm-cap__control';

				const checkbox = document.createElement( 'input' );
				checkbox.type = 'checkbox';
				checkbox.id = 'edac-perm-' + role + '-' + cap.slug;
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
				name.textContent = cap.label;

				label.appendChild( checkbox );
				label.appendChild( name );
				row.appendChild( label );

				if ( cap.description ) {
					const desc = document.createElement( 'span' );
					desc.id = checkbox.id + '-description';
					desc.className = 'edac-description edac-perm-cap__desc';
					desc.textContent = cap.description;
					row.appendChild( desc );
					describedBy.push( desc.id );
				}

				if ( ! meta.enabled && meta.reason ) {
					const reason = document.createElement( 'span' );
					reason.id = checkbox.id + '-reason';
					reason.className = 'edac-perm-cap__reason';
					reason.textContent = meta.reason;
					row.appendChild( reason );
					describedBy.push( reason.id );
				}

				if ( describedBy.length ) {
					checkbox.setAttribute( 'aria-describedby', describedBy.join( ' ' ) );
				}

				container.appendChild( row );
			} );

			capsBox.appendChild( container );
		} );

		const selectedRole = roleSelect.options[ roleSelect.selectedIndex ].textContent;
		status.textContent = ( strings.permissionsLoaded || 'Permission settings loaded for %s.' ).replace( '%s', selectedRole );
	}

	roleSelect.addEventListener( 'change', renderCaps );
	renderCaps();
}() );
