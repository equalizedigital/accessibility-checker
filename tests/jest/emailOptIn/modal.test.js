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
		delete window.tb_remove;
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

	test( 'adds dialog semantics to the Thickbox window once it opens', () => {
		jest.useFakeTimers();

		// The modal only opens once per module instance, so load a fresh copy.
		let freshInitOptInModal;
		let freshCreateFocusTrap;
		jest.isolateModules( () => {
			freshInitOptInModal = require( '../../../src/emailOptIn/modal' ).initOptInModal;
			freshCreateFocusTrap = require( 'focus-trap' ).createFocusTrap;
		} );

		// Stand-ins for the globals core Thickbox and WP admin provide.
		window.tb_show = jest.fn();
		window.tb_remove = jest.fn();
		window.jQuery = jest.fn( () => ( { one: jest.fn() } ) );
		freshCreateFocusTrap.mockReturnValue( { activate: jest.fn(), deactivate: jest.fn() } );

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
		freshInitOptInModal();
		addEventListenerSpy.mock.calls.find( ( call ) => call[ 0 ] === 'load' )[ 1 ]();
		addEventListenerSpy.mock.calls.find( ( call ) => call[ 0 ] === 'mousemove' )[ 1 ]();

		expect( window.tb_show ).toHaveBeenCalled();

		// bindFocusTrap() polls every 250ms for the Thickbox window.
		jest.advanceTimersByTime( 250 );

		const modal = document.getElementById( 'TB_window' );
		expect( modal.getAttribute( 'role' ) ).toBe( 'dialog' );
		expect( modal.getAttribute( 'aria-modal' ) ).toBe( 'true' );
		expect( modal.getAttribute( 'aria-labelledby' ) ).toBe( 'TB_ajaxWindowTitle' );
		expect( freshCreateFocusTrap ).toHaveBeenCalledWith( modal );
	} );

	test( 'makes the background inert while open and restores it on close', () => {
		jest.useFakeTimers();

		// The modal only opens once per module instance, so load a fresh copy.
		let freshInitOptInModal;
		let freshCreateFocusTrap;
		jest.isolateModules( () => {
			freshInitOptInModal = require( '../../../src/emailOptIn/modal' ).initOptInModal;
			freshCreateFocusTrap = require( 'focus-trap' ).createFocusTrap;
		} );

		let unloadHandler;
		const focusTrap = { activate: jest.fn(), deactivate: jest.fn() };
		window.tb_show = jest.fn();
		window.tb_remove = jest.fn();
		window.jQuery = jest.fn( () => ( {
			one: jest.fn( ( event, handler ) => {
				unloadHandler = handler;
			} ),
		} ) );
		window.fetch = jest.fn( () => Promise.resolve( { json: () => ( {} ) } ) );
		freshCreateFocusTrap.mockReturnValue( focusTrap );

		document.body.innerHTML = `
			<div id="wpwrap"><a href="#">Background link</a></div>
			<div id="already-inert" inert></div>
			<div id="TB_overlay"></div>
			<div id="TB_window">
				<div id="TB_title">
					<div id="TB_ajaxWindowTitle">Accessibility Checker</div>
					<button type="button" id="TB_closeWindowButton"><span class="tb-close-icon"></span></button>
				</div>
				<div id="TB_ajaxContent"></div>
			</div>`;

		const addEventListenerSpy = jest.spyOn( window, 'addEventListener' );
		freshInitOptInModal();
		addEventListenerSpy.mock.calls.find( ( call ) => call[ 0 ] === 'load' )[ 1 ]();
		addEventListenerSpy.mock.calls.find( ( call ) => call[ 0 ] === 'mousemove' )[ 1 ]();
		jest.advanceTimersByTime( 250 );

		const wpwrap = document.getElementById( 'wpwrap' );
		const alreadyInert = document.getElementById( 'already-inert' );

		expect( wpwrap.hasAttribute( 'inert' ) ).toBe( true );
		expect( document.getElementById( 'TB_window' ).hasAttribute( 'inert' ) ).toBe( false );
		expect( document.getElementById( 'TB_overlay' ).hasAttribute( 'inert' ) ).toBe( false );

		// Background must be restored before the trap returns focus to it.
		focusTrap.deactivate.mockImplementation( () => {
			expect( wpwrap.hasAttribute( 'inert' ) ).toBe( false );
		} );
		unloadHandler();

		expect( focusTrap.deactivate ).toHaveBeenCalled();
		expect( wpwrap.hasAttribute( 'inert' ) ).toBe( false );
		expect( alreadyInert.hasAttribute( 'inert' ) ).toBe( true );

		delete window.fetch;
	} );
} );
