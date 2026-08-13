jest.mock( '../../../src/common/saveFixSettingsRest', () => ( {
	saveFixSettings: jest.fn(),
} ) );

import '../../../src/frontendHighlighterApp/components/HighlightFixesModal.js';
import { saveFixSettings } from '../../../src/common/saveFixSettingsRest';

const mountModal = () => {
	const el = document.createElement( 'edac-fixes-modal' );
	document.body.appendChild( el );
	return el;
};

describe( 'edac-fixes-modal', () => {
	afterEach( () => {
		document.body.innerHTML = '';
		jest.clearAllMocks();
	} );

	test( 'registers the custom element and renders WITHOUT a shadow root', () => {
		expect( customElements.get( 'edac-fixes-modal' ) ).toBeDefined();
		const el = mountModal();
		expect( el.shadowRoot ).toBeNull();
		expect( el.classList.contains( 'edac-fixes-modal' ) ).toBe( true );
	} );

	test( 'renders content directly in light DOM so page/admin CSS classes still apply', () => {
		const el = mountModal();
		// A field styled by ambient admin CSS (e.g. WP's own checkbox styling)
		// must be a normal light-DOM descendant, not inside a shadow root.
		const fields = document.createElement( 'div' );
		fields.className = 'edac-fix-settings';
		fields.innerHTML = '<input type="checkbox" class="wp-admin-styled-checkbox" />';

		el.fill( '<p>intro</p>', fields );

		const checkbox = document.querySelector( '.wp-admin-styled-checkbox' );
		expect( checkbox ).not.toBeNull();
		expect( el.contains( checkbox ) ).toBe( true );
	} );

	test( 'open() shows the modal, hides other body children from AT, and focuses the first focusable field', () => {
		jest.useFakeTimers();
		const sibling = document.createElement( 'div' );
		document.body.insertBefore( sibling, document.body.firstChild );

		const el = mountModal();
		const fields = document.createElement( 'div' );
		fields.className = 'edac-fix-settings';
		const input = document.createElement( 'input' );
		fields.appendChild( input );
		el.fill( '', fields );

		const opener = document.createElement( 'button' );
		document.body.appendChild( opener );

		el.open( opener );

		expect( el.classList.contains( 'edac-fixes-modal--open' ) ).toBe( true );
		expect( el.getAttribute( 'aria-hidden' ) ).toBe( 'false' );
		expect( sibling.getAttribute( 'aria-hidden' ) ).toBe( 'true' );
		expect( sibling.getAttribute( 'data-hidden-by-modal' ) ).toBe( 'true' );

		jest.advanceTimersByTime( 100 );
		expect( document.activeElement ).toBe( input );

		jest.useRealTimers();
	} );

	test( 'close() restores the fields to their origin placeholder, un-hides siblings, and restores focus', () => {
		jest.useFakeTimers();
		const sibling = document.createElement( 'div' );
		document.body.insertBefore( sibling, document.body.firstChild );

		const el = mountModal();
		const fields = document.createElement( 'div' );
		fields.className = 'edac-fix-settings';
		el.fill( '', fields );

		const placeholder = document.createElement( 'span' );
		placeholder.classList.add( 'edac-fix-settings--origin-placeholder' );
		document.body.appendChild( placeholder );

		const opener = document.createElement( 'button' );
		document.body.appendChild( opener );
		el.open( opener );

		const closeHandler = jest.fn();
		el.addEventListener( 'edac-fixes-modal-closed', closeHandler );

		el.close();

		expect( el.classList.contains( 'edac-fixes-modal--open' ) ).toBe( false );
		expect( sibling.hasAttribute( 'aria-hidden' ) ).toBe( false );
		expect( document.querySelector( '.edac-fix-settings--origin-placeholder' ) ).toBeNull();
		expect( document.body.contains( fields ) ).toBe( true );
		expect( closeHandler ).toHaveBeenCalledTimes( 1 );

		jest.advanceTimersByTime( 0 );
		expect( document.activeElement ).toBe( opener );

		jest.useRealTimers();
	} );

	test( 'clicking the close button closes the modal', () => {
		const el = mountModal();
		el.fill( '', null );
		el.open( document.body );

		el.querySelector( '.edac-fixes-modal__close' ).click();

		expect( el.classList.contains( 'edac-fixes-modal--open' ) ).toBe( false );
	} );

	test( 'clicking the save button calls saveFixSettings with the fields container', () => {
		const el = mountModal();
		const fields = document.createElement( 'div' );
		fields.className = 'edac-fix-settings';
		fields.innerHTML = `
			<div class="edac-fix-settings--fields">
				<button class="edac-fix-settings--button--save">Save</button>
			</div>
		`;
		el.fill( '', fields );

		el.querySelector( '.edac-fix-settings--button--save' ).click();

		expect( saveFixSettings ).toHaveBeenCalledTimes( 1 );
		expect( saveFixSettings.mock.calls[ 0 ][ 0 ].classList.contains( 'edac-fix-settings--fields' ) ).toBe( true );
	} );

	test( 'fill() derives the modal title from data-fancy-name, falling back to data-group-name', () => {
		const el = mountModal();

		const fields = document.createElement( 'div' );
		fields.setAttribute( 'data-group-name', 'Alt Text Group' );
		el.fill( '', fields );
		expect( el.querySelector( '#edac-fixes-modal-title' ).innerText ).toBe( 'Alt Text Group' );

		const fieldsWithFancyName = document.createElement( 'div' );
		fieldsWithFancyName.setAttribute( 'data-fancy-name', 'Fancy Fix' );
		fieldsWithFancyName.setAttribute( 'data-group-name', 'Ignored Group' );
		el.fill( '', fieldsWithFancyName );
		expect( el.querySelector( '#edac-fixes-modal-title' ).innerText ).toBe( 'Fancy Fix' );
	} );
} );
