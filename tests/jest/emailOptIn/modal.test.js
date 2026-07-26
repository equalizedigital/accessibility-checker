/**
 * Tests for email opt-in modal initialization
 */

import { createFocusTrap } from 'focus-trap';
import { initOptInModal } from '../../../src/emailOptIn/modal';

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
		jest.useRealTimers();
		document.body.innerHTML = '';
		window.onload = null;
	} );

	afterEach( () => {
		jest.clearAllTimers();
		jest.useRealTimers();
		delete window.tb_show;
		delete window.jQuery;
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

	test( 'marks the email opt-in ThickBox before activating its focus trap', () => {
		jest.useFakeTimers();

		const modal = document.createElement( 'div' );
		modal.id = 'TB_window';
		modal.innerHTML = '<button class="tb-close-icon">Close</button>';

		const focusTrap = {
			activate: jest.fn(),
			deactivate: jest.fn(),
		};
		createFocusTrap.mockReturnValue( focusTrap );
		window.tb_show = jest.fn( () => document.body.appendChild( modal ) );
		window.jQuery = jest.fn( () => ( { one: jest.fn() } ) );

		const addEventListenerSpy = jest.spyOn( window, 'addEventListener' );
		initOptInModal();

		const loadCall = addEventListenerSpy.mock.calls.find( ( call ) => call[ 0 ] === 'load' );
		loadCall[ 1 ]();
		const mousemoveCall = addEventListenerSpy.mock.calls.find( ( call ) => call[ 0 ] === 'mousemove' );
		mousemoveCall[ 1 ]();

		expect( modal.classList.contains( 'edac-email-opt-in-modal' ) ).toBe( true );

		jest.advanceTimersByTime( 250 );

		expect( createFocusTrap ).toHaveBeenCalledWith( modal );
		expect( focusTrap.activate ).toHaveBeenCalled();
	} );
} );
