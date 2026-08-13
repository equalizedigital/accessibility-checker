/**
 * <edac-highlight-panel>
 *
 * Shell for the floating issue-review dialog: header (title, the
 * edac-highlight-menu child, close button), content area (empty state or
 * a single persistent edac-highlight-issue-view child), and footer
 * (summary counts, prev/next, pagination). Ported from
 * addHighlightPanel()/panelOpen()/panelClose()/initDrag() and friends in
 * index.js.
 *
 * Self-positioning via :host, same decoupling as edac-highlight-trigger —
 * no longer depends on the old #edac-highlight-panel wrapper div.
 *
 * Menu item labels that depend on this panel's own state (move/reset
 * position, dock/undock, disable/enable styles) are computed here rather
 * than passed in as strings, so there's one source of truth for what the
 * menu should say. The controller only supplies raw booleans (docked,
 * stylesDisabled, userCanEdit) and translated string fragments via `i18n`.
 *
 * Escape / the close button do NOT close the panel directly — they
 * dispatch edac-panel-close and let the controller decide (it also has to
 * remove tooltip buttons and selected-element highlighting elsewhere on
 * the page, which this component has no business touching). The
 * controller closes the panel by setting `open = false`.
 */

import { createFocusTrap } from 'focus-trap';
import './HighlightMenu.js';
import './HighlightIssueView.js';

const DRAG_THRESHOLD = 4;

class HighlightPanel extends HTMLElement {
	static get observedAttributes() {
		return [ 'open', 'docked', 'position' ];
	}

	constructor() {
		super();
		this.attachShadow( { mode: 'open' } );
		this._issues = [];
		this._currentIndex = null;
		this._status = null;
		this._fix = null;
		this._isPro = false;
		this._userCanFix = false;
		this._userCanEdit = false;
		this._stylesDisabled = false;
		this._i18n = {};
		this._dragged = false;
	}

	connectedCallback() {
		if ( ! this.shadowRoot.firstChild ) {
			this.render();
		}
		this.closeButton.addEventListener( 'click', this._onCloseClick );
		this.prevButton.addEventListener( 'click', this._onPrevClick );
		this.nextButton.addEventListener( 'click', this._onNextClick );
		this.menu.addEventListener( 'edac-move', this._onMenuMove );
		this._initDrag();
		this._initFocusTrap();
		this._syncMenuItems();
		this._syncContent();
	}

	disconnectedCallback() {
		this.closeButton.removeEventListener( 'click', this._onCloseClick );
		this.prevButton.removeEventListener( 'click', this._onPrevClick );
		this.nextButton.removeEventListener( 'click', this._onNextClick );
		this.menu.removeEventListener( 'edac-move', this._onMenuMove );
	}

	attributeChangedCallback( name, oldValue, newValue ) {
		if ( oldValue === newValue || ! this.shadowRoot.firstChild ) {
			return;
		}
		if ( name === 'open' ) {
			this._onOpenChanged();
		} else if ( name === 'docked' || name === 'position' ) {
			this._syncMenuItems();
		}
	}

	render() {
		const template = document.createElement( 'template' );
		template.innerHTML = `
			<style>
				:host {
					all: initial;
					display: none;
					position: fixed;
					z-index: 2147483647;
					width: auto;
					max-width: 400px;
					bottom: 15px;
					font-family: sans-serif;
					font-weight: 400;
				}

				:host([open]) {
					display: block;
				}

				:host(:not([position="left"])) {
					right: 15px;
				}

				:host([position="left"]) {
					left: 15px;
				}

				:host([docked]) {
					bottom: 0;
					right: 0;
					left: 0;
					max-width: none;
					width: auto;
				}

				.edac-sr-only {
					position: absolute;
					width: 1px;
					height: 1px;
					padding: 0;
					margin: -1px;
					overflow: hidden;
					clip-path: inset(50%);
					white-space: nowrap;
					border: 0;
				}

				.edac-highlight-panel-controls {
					color: #1e1e1e;
					background-color: #fff;
					display: flex;
					flex-direction: column;
					max-height: calc(100vh - 80px);
					box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35);
					border-radius: 10px;
					overflow: hidden;
					font-size: 14px;
					line-height: 22px;
					-webkit-font-smoothing: antialiased;
					-moz-osx-font-smoothing: grayscale;
					width: 400px;
				}

				:host([docked]) .edac-highlight-panel-controls {
					position: fixed;
					top: var(--edac-adminbar-height, 0);
					bottom: 0;
					height: calc(100vh - var(--edac-adminbar-height, 0px));
					max-height: calc(100vh - var(--edac-adminbar-height, 0px));
					width: min(400px, 100vw);
					min-width: 0;
					max-width: 100vw;
					border-radius: 0;
					box-shadow: none;
				}

				.edac-highlight-panel-controls-header {
					display: flex;
					align-items: center;
					justify-content: space-between;
					background-color: var(--wp-admin-theme-color, #3273aa);
					padding: 8px 12px;
					gap: 8px;
					cursor: grab;
				}

				:host([docked]) .edac-highlight-panel-controls-header {
					cursor: default;
				}

				.edac-highlight-panel-controls-title {
					display: flex;
					align-items: center;
					gap: 8px;
					font-size: 14px;
					font-weight: 700;
					flex: 1;
					color: #fff;
				}

				.edac-highlight-panel-controls-header-actions {
					display: flex;
					align-items: center;
					gap: 4px;
					flex-shrink: 0;
				}

				.edac-highlight-panel-controls-close {
					all: unset;
					display: flex;
					align-items: center;
					justify-content: center;
					width: 28px;
					height: 28px;
					color: #fff;
					font-size: 18px;
					cursor: pointer;
					border-radius: 2px;
					border: 1px solid transparent;
					box-sizing: border-box;
				}

				.edac-highlight-panel-controls-close:hover,
				.edac-highlight-panel-controls-close:focus {
					border-color: #fff;
				}

				.edac-highlight-panel-controls-content {
					flex: 1;
					overflow-y: auto;
					padding: 15px;
					border-bottom: solid 1px #e2e4e7;
				}

				.edac-highlight-panel-controls-content-empty {
					margin: 0;
					padding: 8px 12px;
					border-left: 4px solid #f1c500;
					background-color: rgba(241, 197, 0, 0.15);
					color: #1e1e1e;
					font-size: 13px;
					line-height: 1.5;
				}

				.edac-highlight-panel-controls-footer {
					flex-shrink: 0;
					border-top: solid 1px #e2e4e7;
				}

				.edac-highlight-panel-controls-summary {
					display: block;
					padding: 12px 15px 0;
				}

				.edac-highlight-summary-total {
					display: block;
					font-weight: 700;
					font-size: 15px;
				}

				.edac-highlight-summary-breakdown {
					display: block;
					font-size: 12px;
					opacity: 0.85;
					margin-top: 2px;
				}

				.edac-highlight-panel-controls-buttons {
					display: flex;
					align-items: center;
					padding: 10px 15px 15px;
					gap: 8px;
				}

				.edac-highlight-panel-controls-buttons button {
					all: unset;
					box-sizing: border-box;
					text-decoration: none;
					color: var(--wp-admin-theme-color, #3273aa);
					background-color: transparent;
					border: 1px solid var(--wp-admin-theme-color, #3273aa);
					padding: 8px 13px;
					display: inline-block;
					flex-shrink: 0;
					width: 80px;
					text-align: center;
					border-radius: 2px;
					cursor: pointer;
				}

				.edac-highlight-panel-controls-buttons button:disabled {
					display: none;
				}

				.edac-highlight-panel-controls-pagination {
					flex: 1;
					text-align: center;
					font-size: 13px;
					color: #555;
				}
			</style>
			<div class="edac-highlight-panel-controls" tabindex="0" role="dialog" aria-labelledby="edac-highlight-panel-controls-title">
				<div class="edac-highlight-panel-controls-header">
					<div id="edac-highlight-panel-controls-title" class="edac-highlight-panel-controls-title" role="heading" aria-level="2"></div>
					<div class="edac-highlight-panel-controls-header-actions">
						<edac-highlight-menu></edac-highlight-menu>
						<button class="edac-highlight-panel-controls-close">&times;</button>
					</div>
				</div>
				<div class="edac-highlight-panel-controls-content">
					<div class="edac-highlight-panel-controls-content-empty"></div>
					<edac-highlight-issue-view style="display:none"></edac-highlight-issue-view>
				</div>
				<div class="edac-highlight-panel-controls-footer">
					<div class="edac-highlight-panel-controls-summary"></div>
					<div class="edac-highlight-panel-controls-buttons">
						<button class="edac-highlight-prev" disabled><span aria-hidden="true">&larr; </span><span class="edac-highlight-prev-label"></span></button>
						<span class="edac-highlight-panel-controls-pagination" aria-live="polite"></span>
						<button class="edac-highlight-next" disabled><span class="edac-highlight-next-label"></span><span aria-hidden="true"> &rarr;</span></button>
					</div>
				</div>
			</div>
			<div class="edac-sr-only" role="status" aria-live="polite" aria-atomic="true"></div>
		`;

		this.shadowRoot.appendChild( template.content.cloneNode( true ) );
		this.controls = this.shadowRoot.querySelector( '.edac-highlight-panel-controls' );
		this.header = this.shadowRoot.querySelector( '.edac-highlight-panel-controls-header' );
		this.titleEl = this.shadowRoot.querySelector( '.edac-highlight-panel-controls-title' );
		this.menu = this.shadowRoot.querySelector( 'edac-highlight-menu' );
		this.closeButton = this.shadowRoot.querySelector( '.edac-highlight-panel-controls-close' );
		this.emptyStateEl = this.shadowRoot.querySelector( '.edac-highlight-panel-controls-content-empty' );
		this.issueView = this.shadowRoot.querySelector( 'edac-highlight-issue-view' );
		this.summaryEl = this.shadowRoot.querySelector( '.edac-highlight-panel-controls-summary' );
		this.prevButton = this.shadowRoot.querySelector( '.edac-highlight-prev' );
		this.nextButton = this.shadowRoot.querySelector( '.edac-highlight-next' );
		this.paginationEl = this.shadowRoot.querySelector( '.edac-highlight-panel-controls-pagination' );
		this.announcerEl = this.shadowRoot.querySelector( '.edac-sr-only[role="status"]' );
	}

	// ---- properties (down) ----

	set issues( value ) {
		this._issues = value || [];
		this._syncContent();
	}

	get issues() {
		return this._issues;
	}

	set currentIndex( value ) {
		this._currentIndex = value;
		this._syncContent();
	}

	get currentIndex() {
		return this._currentIndex;
	}

	set status( value ) {
		this._status = value;
		if ( this.issueView ) {
			this.issueView.status = value;
		}
	}

	set fix( value ) {
		this._fix = value;
		if ( this.issueView ) {
			this.issueView.fix = value;
		}
	}

	set isPro( value ) {
		this._isPro = !! value;
		if ( this.issueView ) {
			this.issueView.isPro = this._isPro;
		}
	}

	set userCanFix( value ) {
		this._userCanFix = !! value;
		if ( this.issueView ) {
			this.issueView.userCanFix = this._userCanFix;
		}
	}

	set userCanEdit( value ) {
		this._userCanEdit = !! value;
		this._syncMenuItems();
	}

	get stylesDisabled() {
		return this._stylesDisabled;
	}

	set stylesDisabled( value ) {
		this._stylesDisabled = !! value;
		this._syncMenuItems();
	}

	set i18n( value ) {
		this._i18n = value || {};
		if ( this.issueView ) {
			this.issueView.i18n = this._i18n;
		}
		this._syncMenuItems();
		this._syncContent();
	}

	/**
	 * @param {Function} fn (count, key) => locale-correct pluralized string,
	 *                      e.g. via WP's _n(). key is one of 'issueFound',
	 *                      'problem', 'needsReview', 'dismissed'.
	 */
	set pluralize( fn ) {
		this._pluralize = fn;
		this._syncSummary();
	}

	get docked() {
		return this.hasAttribute( 'docked' );
	}

	set docked( value ) {
		if ( value ) {
			this.setAttribute( 'docked', '' );
		} else {
			this.removeAttribute( 'docked' );
		}
	}

	get position() {
		return this.getAttribute( 'position' ) === 'left' ? 'left' : 'right';
	}

	set position( value ) {
		this.setAttribute( 'position', value === 'left' ? 'left' : 'right' );
	}

	get open() {
		return this.hasAttribute( 'open' );
	}

	set open( value ) {
		if ( value ) {
			this.setAttribute( 'open', '' );
		} else {
			this.removeAttribute( 'open' );
		}
	}

	get dragged() {
		return this._dragged;
	}

	_t( key, fallback ) {
		return this._i18n[ key ] || fallback;
	}

	// ---- rendering ----

	_syncContent() {
		if ( ! this.shadowRoot.firstChild ) {
			return;
		}

		this.titleEl.innerHTML = `<span aria-hidden="true">&#9989;</span> ${ this._t( 'panelTitle', 'Accessibility Checker' ) }`;

		const issue = this._currentIndex !== null ? this._issues[ this._currentIndex ] : null;

		if ( issue ) {
			this.emptyStateEl.style.display = 'none';
			this.issueView.style.display = 'block';
			this.issueView.issue = issue;
			this.issueView.status = this._status;
			this.issueView.fix = this._fix;
			this.issueView.isPro = this._isPro;
			this.issueView.userCanFix = this._userCanFix;
			this.issueView.i18n = this._i18n;
		} else {
			this.emptyStateEl.style.display = 'block';
			this.issueView.style.display = 'none';
		}

		this._syncSummary();
		this._syncPagination();
	}

	_syncSummary() {
		const errorCount = this._issues.filter( ( i ) => i.rule_type === 'error' ).length;
		const warningCount = this._issues.filter( ( i ) => i.rule_type === 'warning' ).length;
		const ignoredCount = this._issues.filter( ( i ) => i.ignored === '1' ).length;
		const total = errorCount + warningCount;

		if ( this._issues.length === 0 ) {
			this.summaryEl.innerHTML = '';
			this.emptyStateEl.textContent = this._t( 'loading', 'Loading...' );
			this.prevButton.disabled = true;
			this.nextButton.disabled = true;
			return;
		}

		if ( total === 0 && ignoredCount === 0 ) {
			this.summaryEl.innerHTML = `<span class="edac-highlight-summary-total" role="heading" aria-level="3">${ this._t( 'noIssues', 'No issues detected.' ) }</span>`;
			this.emptyStateEl.textContent = this._t( 'noIssues', 'No issues detected.' );
			this.prevButton.disabled = true;
			this.nextButton.disabled = true;
			return;
		}

		this.prevButton.disabled = false;
		this.nextButton.disabled = false;

		// Pluralization needs real gettext plural rules (not just English
		// singular/plural), which only the controller's _n() can provide —
		// so counting stays here, but formatting the count into a locale-
		// correct string is delegated to a `pluralize` function property.
		const pluralize = this._pluralize || ( ( count, single ) => `${ count } ${ single }` );
		const totalLabel = pluralize( total, 'issueFound' );
		const problemsLabel = pluralize( errorCount, 'problem' );
		const reviewLabel = pluralize( warningCount, 'needsReview' );

		const breakdownParts = [ problemsLabel, reviewLabel ];
		if ( ignoredCount > 0 ) {
			breakdownParts.push( pluralize( ignoredCount, 'dismissed' ) );
		}

		this.summaryEl.innerHTML = `<span class="edac-highlight-summary-total" role="heading" aria-level="3">${ totalLabel }</span><span class="edac-highlight-summary-breakdown">${ breakdownParts.join( ' · ' ) }</span>`;
	}

	_syncPagination() {
		this.prevButton.querySelector( '.edac-highlight-prev-label' ).textContent = this._t( 'previous', 'Previous' );
		this.nextButton.querySelector( '.edac-highlight-next-label' ).textContent = this._t( 'next', 'Next' );

		if ( this._currentIndex === null || ! this._issues.length ) {
			this.paginationEl.textContent = '';
			return;
		}

		const visiblePosition = `${ this._currentIndex + 1 } / ${ this._issues.length }`;
		this.paginationEl.textContent = visiblePosition;
	}

	_syncMenuItems() {
		if ( ! this.menu ) {
			return;
		}
		const isRight = this.position !== 'left';
		let moveLabel;
		if ( this._dragged ) {
			moveLabel = this._t( 'resetPosition', 'Reset Position' );
		} else if ( isRight ) {
			moveLabel = this._t( 'moveToLeft', 'Move to Left' );
		} else {
			moveLabel = this._t( 'moveToRight', 'Move to Right' );
		}
		const dockLabel = this.docked
			? this._t( 'undockPanel', 'Undock Panel' )
			: this._t( 'dockPanel', 'Dock Panel' );
		const stylesLabel = this._stylesDisabled
			? this._t( 'enableStyles', 'Enable Styles' )
			: this._t( 'disableStyles', 'Disable Styles' );
		const stylesAriaLabel = this._stylesDisabled
			? this._t( 'enablePageStyles', 'Enable Page Styles' )
			: this._t( 'disablePageStyles', 'Disable Page Styles' );

		this.menu.items = [
			{ id: 'move', label: moveLabel, hidden: false },
			{ id: 'dock', label: dockLabel, hidden: false },
			{ id: 'rescan', label: this._t( 'rescanPage', 'Rescan This Page' ), hidden: ! this._userCanEdit },
			{ id: 'clear-issues', label: this._t( 'clearIssues', 'Clear Issues' ), hidden: ! this._userCanEdit },
			{ id: 'disable-styles', label: stylesLabel, ariaLabel: stylesAriaLabel, hidden: false },
		];
	}

	// ---- drag to move ----

	_initDrag() {
		let startX, startY, startLeft, startTop, isDragging, hasMoved;
		const header = this.header;
		const controls = this.controls;

		const resetControlsPosition = () => {
			controls.style.position = '';
			controls.style.width = '';
			controls.style.left = '';
			controls.style.top = '';
			controls.style.right = '';
			controls.style.bottom = '';
		};
		this._resetControlsPosition = resetControlsPosition;

		header.addEventListener( 'pointerdown', ( e ) => {
			if ( e.target.closest( 'button, a' ) ) {
				return;
			}
			if ( this.docked ) {
				return;
			}

			const rect = controls.getBoundingClientRect();
			startLeft = rect.left;
			startTop = rect.top;
			startX = e.clientX;
			startY = e.clientY;
			isDragging = true;
			hasMoved = false;

			controls.style.width = rect.width + 'px';
			controls.style.position = 'fixed';
			controls.style.left = startLeft + 'px';
			controls.style.top = startTop + 'px';
			controls.style.right = 'auto';
			controls.style.bottom = 'auto';

			header.setPointerCapture?.( e.pointerId );
			document.body.style.userSelect = 'none';
			header.style.cursor = 'grabbing';

			e.preventDefault();
		} );

		header.addEventListener( 'pointermove', ( e ) => {
			if ( ! isDragging ) {
				return;
			}
			const dx = e.clientX - startX;
			const dy = e.clientY - startY;
			if ( ! hasMoved && Math.abs( dx ) <= DRAG_THRESHOLD && Math.abs( dy ) <= DRAG_THRESHOLD ) {
				return;
			}
			hasMoved = true;
			controls.style.left = ( startLeft + dx ) + 'px';
			controls.style.top = ( startTop + dy ) + 'px';
		} );

		header.addEventListener( 'pointerup', ( e ) => {
			if ( ! isDragging ) {
				return;
			}
			isDragging = false;
			header.releasePointerCapture?.( e.pointerId );
			document.body.style.userSelect = '';
			header.style.cursor = 'grab';

			const totalDx = e.clientX - startX;
			const totalDy = e.clientY - startY;
			const wasDrag = hasMoved || Math.abs( totalDx ) > DRAG_THRESHOLD || Math.abs( totalDy ) > DRAG_THRESHOLD;

			if ( wasDrag ) {
				this._dragged = true;
				this._syncMenuItems();
			} else {
				resetControlsPosition();
			}
		} );

		header.addEventListener( 'pointercancel', ( e ) => {
			if ( ! isDragging ) {
				return;
			}
			isDragging = false;
			header.releasePointerCapture?.( e.pointerId );
			document.body.style.userSelect = '';
			header.style.cursor = 'grab';
			if ( ! hasMoved ) {
				resetControlsPosition();
			}
		} );
	}

	_onMenuMove = () => {
		if ( this._dragged ) {
			this._dragged = false;
			this._resetControlsPosition();
			this._syncMenuItems();
			this.announce( this._t( 'positionReset', 'Panel position reset.' ) );
			return;
		}

		const wasRight = this.position !== 'left';
		this.position = wasRight ? 'left' : 'right';
		this._syncMenuItems();
		this.announce( wasRight
			? this._t( 'movedLeft', 'Panel moved to the left.' )
			: this._t( 'movedRight', 'Panel moved to the right.' ) );

		this.dispatchEvent( new CustomEvent( 'edac-panel-position-changed', {
			bubbles: true,
			composed: true,
			detail: { position: this.position, docked: this.docked },
		} ) );
	};

	// ---- focus trap ----

	_initFocusTrap() {
		this._focusTrap = createFocusTrap( this.controls, {
			clickOutsideDeactivates: true,
			escapeDeactivates: () => {
				this._requestClose();
			},
			initialFocus: () => this.closeButton,
		} );

		this.controls.addEventListener( 'pointerdown', () => {
			if ( ! this._focusTrap.active ) {
				this._focusTrap.activate( { returnFocusOnDeactivate: false } );
			}
		} );
	}

	_onOpenChanged() {
		if ( this.open ) {
			this.refocus();
		} else if ( this._focusTrap?.active ) {
			this._focusTrap.deactivate();
		}
	}

	/**
	 * (Re)activate the focus trap and move focus to the close button.
	 * Unlike setting `open = true`, this runs even when the panel is
	 * already open — used when a tooltip button opens a specific issue
	 * without the open/closed state itself changing.
	 */
	refocus() {
		this._focusTrap?.activate();
		setTimeout( () => {
			this.closeButton?.focus();
		}, 100 );
	}

	// ---- public API ----

	announce( message ) {
		if ( ! this.announcerEl ) {
			return;
		}
		this.announcerEl.textContent = '';
		setTimeout( () => {
			this.announcerEl.textContent = message;
		}, 50 );
	}

	focusClose() {
		this.closeButton?.focus();
	}

	/**
	 * Sets the footer summary / empty-state text directly, for scan and
	 * network status messages (scanning, save failed, etc.) that don't
	 * come from the issues list itself.
	 *
	 * @param {string}  message
	 * @param {boolean} isError
	 */
	setMessage( message, isError = false ) {
		this.summaryEl.textContent = message;
		this.summaryEl.classList.toggle( 'edac-error', isError );
		this.emptyStateEl.textContent = message;
	}

	pauseFocusTrap() {
		this._focusTrap?.pause();
	}

	resumeFocusTrap() {
		this._focusTrap?.unpause();
	}

	_requestClose() {
		this.dispatchEvent( new CustomEvent( 'edac-panel-close', {
			bubbles: true,
			composed: true,
		} ) );
	}

	_onCloseClick = () => {
		this._requestClose();
	};

	_onPrevClick = () => {
		this.dispatchEvent( new CustomEvent( 'edac-nav-previous', { bubbles: true, composed: true } ) );
	};

	_onNextClick = () => {
		this.dispatchEvent( new CustomEvent( 'edac-nav-next', { bubbles: true, composed: true } ) );
	};
}

if ( ! customElements.get( 'edac-highlight-panel' ) ) {
	customElements.define( 'edac-highlight-panel', HighlightPanel );
}

export { HighlightPanel };
