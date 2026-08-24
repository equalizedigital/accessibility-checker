/**
 * Tests for email opt-in modal initialization
 */

import { initOptInModal } from '../../../src/emailOptIn/modal';
import { createFocusTrap } from 'focus-trap';

jest.mock(
	'focus-trap',
	() => ( {
		createFocusTrap: jest.fn(),
	} ),
	{ virtual: true },
);

describe( 'email opt-in modal init', () => {
	beforeEach( () => {
		jest.restoreAllMocks();
		window.onload = null;
	} );

	test( 'does not overwrite existing window.onload handler', () => {
		const existingOnload = jest.fn();
		window.onload = existingOnload;
		const addEventListenerSpy = jest.spyOn( window, 'addEventListener' );

		initOptInModal();

		expect( window.onload ).toBe( existingOnload );
		expect( addEventListenerSpy ).toHaveBeenCalledWith( 'load', expect.any( Function ) );
	} );

	test( 'registers mousemove and scroll listeners after load', () => {
		const addEventListenerSpy = jest.spyOn( window, 'addEventListener' );

		initOptInModal();

		const loadCall = addEventListenerSpy.mock.calls.find( ( call ) => call[ 0 ] === 'load' );
		expect( loadCall ).toBeDefined();

		const loadHandler = loadCall[ 1 ];
		loadHandler();

		expect( addEventListenerSpy ).toHaveBeenCalledWith( 'mousemove', expect.any( Function ), { once: true } );
		expect( addEventListenerSpy ).toHaveBeenCalledWith( 'scroll', expect.any( Function ), { once: true } );
	} );

	test( 'adds dialog semantics to the Thickbox window once it opens', () => {
		jest.useFakeTimers();

		// Stand-ins for the globals core Thickbox and WP admin provide.
		window.tb_show = jest.fn();
		window.tb_remove = jest.fn();
		window.jQuery = jest.fn( () => ( { one: jest.fn() } ) );
		createFocusTrap.mockReturnValue( { activate: jest.fn(), deactivate: jest.fn() } );

		// Minimal version of the markup tb_show() builds.
		document.body.innerHTML = `
			<div id="TB_window">
				<div id="TB_title">
					<div id="TB_ajaxWindowTitle">Accessibility Checker</div>
					<button type="button" id="TB_closeWindowButton"><span class="tb-close-icon"></span></button>
				</div>
				<div id="TB_ajaxContent"></div>
			</div>`;

		const addEventListenerSpy = jest.spyOn( window, 'addEventListener' );
		initOptInModal();
		addEventListenerSpy.mock.calls.find( ( call ) => call[ 0 ] === 'load' )[ 1 ]();
		addEventListenerSpy.mock.calls.find( ( call ) => call[ 0 ] === 'mousemove' )[ 1 ]();

		expect( window.tb_show ).toHaveBeenCalled();

		// bindFocusTrap() polls every 250ms for the Thickbox window.
		jest.advanceTimersByTime( 250 );

		const modal = document.getElementById( 'TB_window' );
		expect( modal.getAttribute( 'role' ) ).toBe( 'dialog' );
		expect( modal.getAttribute( 'aria-modal' ) ).toBe( 'true' );
		expect( modal.getAttribute( 'aria-labelledby' ) ).toBe( 'TB_ajaxWindowTitle' );
		expect( createFocusTrap ).toHaveBeenCalledWith( modal );

		jest.useRealTimers();
	} );
} );
