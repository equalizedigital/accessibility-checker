/**
 * <edac-highlight-menu>
 *
 * Self-contained "more options" ellipsis menu: the trigger button and the
 * dropdown list of actions (move, dock, rescan, clear issues, disable/enable
 * page styles) both live in this component's shadow tree, matching the
 * current .edac-highlight-menu-container markup which held both as siblings.
 *
 * The controller drives item text/visibility via the `items` property
 * (array of { id, label, ariaLabel, hidden }) rather than reaching into
 * per-button DOM nodes, since ids/labels change at runtime (e.g. "Disable
 * Styles" <-> "Enable Styles", "Move to Left" <-> "Reset Position").
 *
 * Each item id maps to a fixed icon + a specific CustomEvent name — the
 * event vocabulary the controller already expects to wire up in the
 * broader refactor (edac-move, edac-toggle-dock, edac-rescan,
 * edac-clear-issues, edac-toggle-page-styles).
 */

const ICONS = {
	scan: "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E",
	refresh: "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M23 4v6h-6'/%3E%3Cpath d='M1 20v-6h6'/%3E%3Cpath d='M3.51 9a9 9 0 0 1 14.85-3.36L23 10'/%3E%3Cpath d='M20.49 15a9 9 0 0 1-14.85 3.36L1 14'/%3E%3C/svg%3E",
	trash: "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='3 6 5 6 21 6'/%3E%3Cpath d='M19 6l-1 14H6L5 6'/%3E%3Cpath d='M10 11v6'/%3E%3Cpath d='M14 11v6'/%3E%3Cpath d='M9 6V4h6v2'/%3E%3C/svg%3E",
	move: "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 9l-3 3 3 3'/%3E%3Cpath d='M19 9l3 3-3 3'/%3E%3Cpath d='M2 12h20'/%3E%3C/svg%3E",
	styles: "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='3'/%3E%3Cpath d='M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42'/%3E%3C/svg%3E",
	dock: "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='14' y='2' width='8' height='20' rx='2'/%3E%3Cpath d='M2 12h8'/%3E%3Cpath d='M6 8l-4 4 4 4'/%3E%3C/svg%3E",
};

const ITEM_CONFIG = {
	move: { icon: 'move', eventName: 'edac-move' },
	dock: { icon: 'dock', eventName: 'edac-toggle-dock' },
	rescan: { icon: 'refresh', eventName: 'edac-rescan' },
	'clear-issues': { icon: 'trash', eventName: 'edac-clear-issues' },
	'disable-styles': { icon: 'styles', eventName: 'edac-toggle-page-styles' },
};

const DEFAULT_ITEMS = [
	{ id: 'move', label: '', hidden: true },
	{ id: 'dock', label: '', hidden: true },
	{ id: 'rescan', label: '', hidden: true },
	{ id: 'clear-issues', label: '', hidden: true },
	{ id: 'disable-styles', label: '', hidden: true },
];

class HighlightMenu extends HTMLElement {
	constructor() {
		super();
		this.attachShadow( { mode: 'open' } );
		this._items = DEFAULT_ITEMS;
	}

	connectedCallback() {
		if ( ! this.shadowRoot.firstChild ) {
			this.render();
		}
		this.menuButton.addEventListener( 'click', this._onMenuButtonClick );
		this.menu.addEventListener( 'keydown', this._onMenuKeydown );
		this.menu.addEventListener( 'click', this._onMenuItemClick );
		document.addEventListener( 'click', this._onDocumentClick );
		this._renderItems();
	}

	disconnectedCallback() {
		this.menuButton.removeEventListener( 'click', this._onMenuButtonClick );
		this.menu.removeEventListener( 'keydown', this._onMenuKeydown );
		this.menu.removeEventListener( 'click', this._onMenuItemClick );
		document.removeEventListener( 'click', this._onDocumentClick );
	}

	render() {
		const template = document.createElement( 'template' );
		template.innerHTML = `
			<style>
				:host {
					all: initial;
					position: relative;
					display: flex;
					align-items: center;
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
				}

				button {
					all: unset;
					box-sizing: border-box;
				}

				.edac-highlight-menu-button {
					display: flex;
					align-items: center;
					justify-content: center;
					width: 28px;
					height: 28px;
					color: #fff;
					font-size: 18px;
					cursor: pointer;
					letter-spacing: 1px;
					border-radius: 2px;
					border: 1px solid transparent;
				}

				.edac-highlight-menu-button:hover,
				.edac-highlight-menu-button:focus {
					border-color: #fff;
				}

				.edac-highlight-menu {
					display: block;
					position: absolute;
					top: calc(100% + 6px);
					right: 0;
					background-color: #fff;
					list-style: none;
					margin: 0;
					padding: 6px;
					min-width: 200px;
					z-index: 10;
					box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
					border-radius: 8px;
					border: 1px solid #e2e4e7;
					overflow: hidden;
				}

				.edac-highlight-menu[hidden] {
					display: none;
				}

				.edac-highlight-menu li {
					display: block;
				}

				.edac-highlight-menu li[hidden] {
					display: none;
				}

				.edac-highlight-menu button {
					display: flex;
					align-items: center;
					justify-content: space-between;
					width: 100%;
					color: #1e1e1e;
					padding: 8px 10px;
					cursor: pointer;
					white-space: nowrap;
					border-radius: 4px;
					font-size: 13px;
					gap: 12px;
					outline: 2px solid transparent;
				}

				.edac-highlight-menu button:hover {
					background-color: color-mix(in srgb, var(--wp-admin-theme-color, #3273aa) 6%, transparent);
					color: var(--wp-admin-theme-color, #3273aa);
				}

				.edac-highlight-menu button:focus {
					outline-color: var(--wp-admin-theme-color, #3273aa);
					color: var(--wp-admin-theme-color, #3273aa);
				}

				.edac-menu-icon {
					display: inline-block;
					flex-shrink: 0;
					width: 16px;
					height: 16px;
					background-color: currentcolor;
					mask-repeat: no-repeat;
					mask-size: contain;
					mask-position: center;
				}
			</style>
			<button class="edac-highlight-menu-button" aria-haspopup="menu" aria-expanded="false">&#8943;</button>
			<ul class="edac-highlight-menu" role="menu" hidden></ul>
		`;

		this.shadowRoot.appendChild( template.content.cloneNode( true ) );
		this.menuButton = this.shadowRoot.querySelector( '.edac-highlight-menu-button' );
		this.menu = this.shadowRoot.querySelector( '.edac-highlight-menu' );
	}

	_renderItems() {
		this.menu.innerHTML = '';
		this._items.forEach( ( item ) => {
			const config = ITEM_CONFIG[ item.id ];
			if ( ! config ) {
				return;
			}
			const li = document.createElement( 'li' );
			li.setAttribute( 'role', 'none' );
			li.hidden = !! item.hidden;

			const button = document.createElement( 'button' );
			button.setAttribute( 'role', 'menuitem' );
			button.dataset.itemId = item.id;
			if ( item.ariaLabel ) {
				button.setAttribute( 'aria-label', item.ariaLabel );
			}

			const span = document.createElement( 'span' );
			span.textContent = item.label || '';

			const icon = document.createElement( 'span' );
			icon.className = 'edac-menu-icon';
			icon.setAttribute( 'aria-hidden', 'true' );
			icon.style.maskImage = `url("${ ICONS[ config.icon ] }")`;

			button.append( span, icon );
			li.appendChild( button );
			this.menu.appendChild( li );
		} );
	}

	get items() {
		return this._items;
	}

	set items( value ) {
		this._items = value || DEFAULT_ITEMS;
		if ( this.menu ) {
			this._renderItems();
		}
	}

	get open() {
		return this.menu ? ! this.menu.hidden : false;
	}

	openMenu() {
		this.menu.hidden = false;
		this.menuButton.setAttribute( 'aria-expanded', 'true' );
		this.menu.querySelector( '[role="menuitem"]:not([hidden])' )?.focus();
	}

	closeMenu() {
		this.menu.hidden = true;
		this.menuButton.setAttribute( 'aria-expanded', 'false' );
	}

	_onMenuButtonClick = ( e ) => {
		e.stopPropagation();
		if ( this.open ) {
			this.closeMenu();
		} else {
			this.openMenu();
		}
	};

	_onMenuItemClick = ( e ) => {
		const button = e.target.closest( '[role="menuitem"]' );
		if ( ! button ) {
			return;
		}
		const config = ITEM_CONFIG[ button.dataset.itemId ];
		this.closeMenu();
		if ( config ) {
			this.dispatchEvent( new CustomEvent( config.eventName, {
				bubbles: true,
				composed: true,
			} ) );
		}
	};

	_onMenuKeydown = ( e ) => {
		const items = [ ...this.menu.querySelectorAll( '[role="menuitem"]:not([hidden])' ) ];
		const focused = this.shadowRoot.activeElement;
		const index = items.indexOf( focused );
		if ( e.key === 'ArrowDown' ) {
			e.preventDefault();
			items[ ( index + 1 ) % items.length ]?.focus();
		} else if ( e.key === 'ArrowUp' ) {
			e.preventDefault();
			items[ ( index - 1 + items.length ) % items.length ]?.focus();
		} else if ( e.key === 'Escape' ) {
			this.closeMenu();
			this.menuButton.focus();
		}
	};

	_onDocumentClick = ( e ) => {
		if ( this.open && ! this.contains( e.target ) ) {
			this.closeMenu();
		}
	};
}

if ( ! customElements.get( 'edac-highlight-menu' ) ) {
	customElements.define( 'edac-highlight-menu', HighlightMenu );
}

export { HighlightMenu };
