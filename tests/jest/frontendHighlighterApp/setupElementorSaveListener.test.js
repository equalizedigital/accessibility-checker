/**
 * Tests for setupElementorSaveListener.
 *
 * Regression coverage for https://github.com/equalizedigital/accessibility-checker/issues/1359 -
 * the front-end highlighter not refreshing after a save made inside the Elementor editor,
 * because Elementor saves bypass `wp.data` entirely.
 */

import { setupElementorSaveListener } from '../../../src/frontendHighlighterApp/setupElementorSaveListener';

/**
 * Replace `window.parent` with a stand-in for the duration of a test.
 *
 * @param {Object|Function} parent An object to use as window.parent, or a function
 *                                 returning one lazily (useful for throwing getters).
 */
function mockWindowParent( parent ) {
	Object.defineProperty( window, 'parent', {
		configurable: true,
		get: typeof parent === 'function' ? parent : () => parent,
	} );
}

describe( 'setupElementorSaveListener', () => {
	const originalParent = window.parent;

	beforeEach( () => {
		jest.useFakeTimers();
	} );

	afterEach( () => {
		jest.useRealTimers();
		Object.defineProperty( window, 'parent', {
			configurable: true,
			value: originalParent,
		} );
	} );

	test( 'does nothing when not inside an iframe', () => {
		mockWindowParent( window );
		const highlighter = { rescanPage: jest.fn() };

		setupElementorSaveListener( highlighter );
		jest.advanceTimersByTime( 10000 );

		expect( jest.getTimerCount() ).toBe( 0 );
	} );

	test( 'attaches to the non-autosave status-scoped save events and rescans when one fires', () => {
		const on = jest.fn();
		const off = jest.fn();
		mockWindowParent( { elementor: { saver: { on, off } } } );
		const highlighter = { rescanPage: jest.fn() };

		setupElementorSaveListener( highlighter, { pollIntervalMs: 500, maxAttempts: 20 } );
		jest.advanceTimersByTime( 500 );

		expect( on ).toHaveBeenCalledWith( 'after:save:draft', expect.any( Function ) );
		expect( on ).toHaveBeenCalledWith( 'after:save:publish', expect.any( Function ) );
		expect( on ).toHaveBeenCalledWith( 'after:save:private', expect.any( Function ) );
		expect( on ).toHaveBeenCalledWith( 'after:save:pending', expect.any( Function ) );
		// Elementor's generic `after:save` payload carries the resulting post's real
		// post_status (e.g. 'draft' even for an autosave), never the requested save
		// type, so it can't be used to tell an autosave apart from a real save.
		expect( on ).not.toHaveBeenCalledWith( 'after:save', expect.any( Function ) );

		// Simulate Elementor firing a real save.
		const afterSaveHandler = on.mock.calls[ 0 ][ 1 ];
		afterSaveHandler();

		expect( highlighter.rescanPage ).toHaveBeenCalledTimes( 1 );
		// A save-triggered rescan must not force the panel open unprompted.
		expect( highlighter.rescanPage ).toHaveBeenCalledWith( false );

		// Polling should have stopped once attached.
		expect( jest.getTimerCount() ).toBe( 0 );
	} );

	test( 'does not rescan on autosave, because no listener is bound to after:save:autosave', () => {
		const on = jest.fn();
		const off = jest.fn();
		mockWindowParent( { elementor: { saver: { on, off } } } );
		const highlighter = { rescanPage: jest.fn() };

		setupElementorSaveListener( highlighter, { pollIntervalMs: 500, maxAttempts: 20 } );
		jest.advanceTimersByTime( 500 );

		expect( on ).not.toHaveBeenCalledWith( 'after:save:autosave', expect.any( Function ) );
		expect( highlighter.rescanPage ).not.toHaveBeenCalled();

		// A real save afterwards should still rescan.
		const publishHandler = on.mock.calls.find( ( call ) => call[ 0 ] === 'after:save:publish' )[ 1 ];
		publishHandler();
		expect( highlighter.rescanPage ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'detaches all status-scoped save listeners on pagehide so a stale highlighter is never rescanned', () => {
		const on = jest.fn();
		const off = jest.fn();
		mockWindowParent( { elementor: { saver: { on, off } } } );
		const highlighter = { rescanPage: jest.fn() };

		setupElementorSaveListener( highlighter, { pollIntervalMs: 500, maxAttempts: 20 } );
		jest.advanceTimersByTime( 500 );

		const afterSaveHandler = on.mock.calls[ 0 ][ 1 ];

		window.dispatchEvent( new Event( 'pagehide' ) );

		expect( off ).toHaveBeenCalledWith( 'after:save:draft', afterSaveHandler );
		expect( off ).toHaveBeenCalledWith( 'after:save:publish', afterSaveHandler );
		expect( off ).toHaveBeenCalledWith( 'after:save:private', afterSaveHandler );
		expect( off ).toHaveBeenCalledWith( 'after:save:pending', afterSaveHandler );
	} );

	test( 'keeps polling until elementor becomes available, then attaches', () => {
		const on = jest.fn();
		const off = jest.fn();
		let ready = false;
		mockWindowParent( () => ( ready ? { elementor: { saver: { on, off } } } : {} ) );
		const highlighter = { rescanPage: jest.fn() };

		setupElementorSaveListener( highlighter, { pollIntervalMs: 500, maxAttempts: 20 } );

		jest.advanceTimersByTime( 1500 );
		expect( on ).not.toHaveBeenCalled();

		ready = true;
		jest.advanceTimersByTime( 500 );

		expect( on ).toHaveBeenCalledWith( 'after:save:publish', expect.any( Function ) );
		expect( jest.getTimerCount() ).toBe( 0 );
	} );

	test( 'stops polling after maxAttempts if elementor never becomes available', () => {
		mockWindowParent( {} );
		const highlighter = { rescanPage: jest.fn() };

		setupElementorSaveListener( highlighter, { pollIntervalMs: 500, maxAttempts: 3 } );
		jest.advanceTimersByTime( 500 * 3 );

		expect( jest.getTimerCount() ).toBe( 0 );

		// Nothing further happens even if more time passes.
		jest.advanceTimersByTime( 500 * 10 );
		expect( highlighter.rescanPage ).not.toHaveBeenCalled();
	} );

	test( 'stops polling without attaching when the parent is cross-origin', () => {
		// A cross-origin parent window reference is itself readable; only reading
		// properties off of it (like `.elementor`) throws a SecurityError.
		const crossOriginParent = {};
		Object.defineProperty( crossOriginParent, 'elementor', {
			get() {
				throw new Error( 'cross-origin access denied' );
			},
		} );
		mockWindowParent( crossOriginParent );
		const highlighter = { rescanPage: jest.fn() };

		setupElementorSaveListener( highlighter, { pollIntervalMs: 500, maxAttempts: 20 } );
		jest.advanceTimersByTime( 500 );

		expect( jest.getTimerCount() ).toBe( 0 );
		expect( highlighter.rescanPage ).not.toHaveBeenCalled();
	} );
} );
