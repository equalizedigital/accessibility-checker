/**
 * <edac-fixes-modal>
 *
 * Deliberately light DOM — NO shadow root. Ported from fixesModal.js.
 *
 * This modal's whole job is to temporarily host live, admin-rendered fix
 * settings fields (checkboxes, WP-styled inputs, etc. produced server-side
 * and injected by edac-highlight-issue-view) that depend on the ambient
 * page/admin CSS cascade (see sass/app.scss's .edac-fixes-modal and the
 * shared common/sass/_fix-settings.scss partial, plus @extend rules tying
 * it to .edac-highlight-panel-description--button). Putting this in a
 * shadow root would cut those fields off from that styling. Every other
 * component in this conversion uses shadow DOM; this one intentionally
 * does not.
 *
 * Class names on the rendered markup are kept identical to the original
 * (.edac-fixes-modal, .edac-fixes-modal__content, etc.) so the existing
 * global CSS keeps applying unchanged.
 */

import { __ } from '@wordpress/i18n';
import { saveFixSettings } from '../../common/saveFixSettingsRest';

class HighlightFixesModal extends HTMLElement {
	constructor() {
		super();
		this._focusRestoreTarget = null;
		this._changeEventListeners = [];
	}

	connectedCallback() {
		if ( ! this.querySelector( '.edac-fixes-modal__content' ) ) {
			this.render();
		}
	}

	disconnectedCallback() {
		this._overlay?.remove();
		this._unbindChangeEvents();
	}

	render() {
		this.id = this.id || 'edac-fixes-modal';
		this.classList.add( 'edac-fixes-modal' );
		this.setAttribute( 'role', 'dialog' );
		this.setAttribute( 'aria-modal', 'false' );
		this.setAttribute( 'aria-labelledby', 'edac-fixes-modal-title' );
		this.innerHTML = `
			<div class="edac-fixes-modal__content">
				<div class="edac-fixes-modal__header">
					<h2 id="edac-fixes-modal-title"></h2>
					<button class="edac-fixes-modal__close" aria-label="${ __( 'Close fixes modal', 'accessibility-checker' ) }">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
				<div class="edac-fixes-modal__body"></div>
			</div>
		`;

		this._overlay = document.createElement( 'div' );
		this._overlay.classList.add( 'edac-fixes-modal__overlay' );
		this._overlay.setAttribute( 'aria-hidden', 'true' );
		this._overlay.setAttribute( 'tabindex', '-1' );
		( this.parentNode || document.body ).insertBefore( this._overlay, this );

		if ( window.edacFrontendHighlighterApp?.adminThemeColor ) {
			this.style.setProperty( '--wp-admin-theme-color', window.edacFrontendHighlighterApp.adminThemeColor );
		}

		this.querySelector( '.edac-fixes-modal__close' ).addEventListener( 'click', () => this.close() );

		document.addEventListener( 'keydown', ( event ) => {
			if ( 'Escape' === event.key && document.activeElement?.closest( '.edac-fixes-modal' ) ) {
				this.close();
			}
		} );

		this.addEventListener( 'keydown', ( event ) => {
			if ( 'Tab' !== event.key ) {
				return;
			}
			const focusableElements = this.querySelectorAll( 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])' );
			const firstFocusableElement = focusableElements[ 0 ];
			const lastFocusableElement = focusableElements[ focusableElements.length - 1 ];
			if ( event.shiftKey && document.activeElement === firstFocusableElement ) {
				event.preventDefault();
				lastFocusableElement.focus();
			} else if ( ! event.shiftKey && document.activeElement === lastFocusableElement ) {
				event.preventDefault();
				firstFocusableElement.focus();
			}
		} );
	}

	/**
	 * @param {HTMLElement} openingElement Element to restore focus to on close.
	 */
	open( openingElement ) {
		this.classList.add( 'edac-fixes-modal--open' );
		this.setAttribute( 'aria-hidden', 'false' );
		this.setAttribute( 'aria-modal', 'true' );

		const bodyChildren = Array.from( document.body.children );
		bodyChildren.forEach( ( child ) => {
			if ( child === this || child === this._overlay ) {
				return;
			}
			if ( child.getAttribute( 'aria-hidden' ) !== 'true' ) {
				child.setAttribute( 'aria-hidden', 'true' );
				child.setAttribute( 'data-hidden-by-modal', 'true' );
			}
		} );

		document.body.classList.add( 'edac-fixes-modal--open' );
		this._focusRestoreTarget = openingElement;

		const firstFocusableElementInContent = this.querySelector( '.edac-fixes-modal__body' )
			?.querySelector( 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])' );
		if ( firstFocusableElementInContent ) {
			setTimeout( () => {
				firstFocusableElementInContent.focus();
			}, 100 );
		}
	}

	close() {
		this.classList.remove( 'edac-fixes-modal--open' );
		this.setAttribute( 'aria-hidden', 'true' );
		this.setAttribute( 'aria-modal', 'false' );

		const fieldsElement = this.querySelector( '.edac-fix-settings--clone--wrapper' )?.children[ 0 ];
		const originPlaceholder = document.querySelector( '.edac-fix-settings--origin-placeholder' );
		if ( fieldsElement && originPlaceholder ) {
			originPlaceholder.replaceWith( fieldsElement );
		}

		const bodyChildren = Array.from( document.body.children );
		bodyChildren.forEach( ( child ) => {
			if ( child.getAttribute( 'data-hidden-by-modal' ) === 'true' ) {
				child.removeAttribute( 'aria-hidden' );
				child.removeAttribute( 'data-hidden-by-modal' );
			}
		} );
		document.body.classList.remove( 'edac-fixes-modal--open' );
		this._unbindChangeEvents();

		this.dispatchEvent( new CustomEvent( 'edac-fixes-modal-closed', { bubbles: true } ) );

		if ( this._focusRestoreTarget ) {
			const target = this._focusRestoreTarget;
			this._focusRestoreTarget = null;
			setTimeout( () => target.focus(), 0 );
		}
	}

	/**
	 * @param {string}      content       Intro HTML shown above the fields.
	 * @param {HTMLElement} fieldsElement The detached .edac-fix-settings node to host.
	 */
	fill( content = '', fieldsElement = null ) {
		if ( ! fieldsElement ) {
			fieldsElement = document.createElement( 'p' );
			fieldsElement.innerText = __( 'There are no settings to display.', 'accessibility-checker' );
		}
		const fieldsWrapper = document.createElement( 'div' );
		fieldsWrapper.classList.add( 'edac-fix-settings--clone--wrapper' );
		fieldsWrapper.appendChild( fieldsElement );

		let fancyName = fieldsWrapper.querySelector( '[data-fancy-name]' )?.getAttribute( 'data-fancy-name' ) || '';
		if ( fancyName === '' ) {
			fancyName = fieldsWrapper.querySelector( '[data-group-name]' )?.getAttribute( 'data-group-name' ) || '';
		}

		const modalTitle = this.querySelector( '#edac-fixes-modal-title' );
		const modalBody = this.querySelector( '.edac-fixes-modal__body' );

		modalTitle.innerText = fancyName;
		modalBody.innerHTML = content;
		modalBody.appendChild( fieldsWrapper );

		modalBody.querySelectorAll( 'input, select, textarea' ).forEach( ( field ) => {
			const changeListener = () => {
				this.dispatchEvent( new CustomEvent( 'edac-fix-settings-change', { bubbles: true } ) );
			};
			field.addEventListener( 'change', changeListener );
			this._changeEventListeners.push( { field, changeListener } );
		} );

		const saveButton = modalBody.querySelector( '.edac-fix-settings--button--save' );
		saveButton?.addEventListener( 'click', () => {
			saveFixSettings( modalBody.querySelector( '.edac-fix-settings--fields' ) );
		} );

		this.addEventListener( 'edac-fix-settings-change', () => {
			const noticeSlot = this.querySelector( '[aria-live]' );
			if ( noticeSlot ) {
				noticeSlot.innerText = '';
			}
		} );
	}

	_unbindChangeEvents() {
		this._changeEventListeners.forEach( ( { field, changeListener } ) => {
			field.removeEventListener( 'change', changeListener );
		} );
		this._changeEventListeners = [];
	}
}

if ( ! customElements.get( 'edac-fixes-modal' ) ) {
	customElements.define( 'edac-fixes-modal', HighlightFixesModal );
}

export { HighlightFixesModal };
