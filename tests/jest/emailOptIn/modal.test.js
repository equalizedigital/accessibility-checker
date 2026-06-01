/**
 * Tests for email opt-in modal initialization
 */

import { initOptInModal } from '../../../src/emailOptIn/modal';

jest.mock(
	'focus-trap',
	() => ( {
		createFocusTrap: jest.fn(),
	} ),
	{ virtual: true }
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
} );
