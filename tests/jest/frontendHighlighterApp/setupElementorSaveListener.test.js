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

	/**
	 * Get the `before:save` and `after:save` handlers a call to setupElementorSaveListener
	 * registered via the given `on` mock, matching how Elementor's real saver.on() is called.
	 *
	 * @param {Function} on The `on` jest.fn() mock passed as the mocked saver.
	 */
	function getRegisteredHandlers( on ) {
		const beforeSave = on.mock.calls.find( ( call ) => call[ 0 ] === 'before:save' )[ 1 ];
		const afterSave = on.mock.calls.find( ( call ) => call[ 0 ] === 'after:save' )[ 1 ];
		return { beforeSave, afterSave };
	}

	test( 'attaches before:save and after:save listeners and rescans on a real, non-autosave save', () => {
		const on = jest.fn();
		const off = jest.fn();
		mockWindowParent( { elementor: { saver: { on, off } } } );
		const highlighter = { rescanPage: jest.fn() };

		setupElementorSaveListener( highlighter, { pollIntervalMs: 500, maxAttempts: 20 } );
		jest.advanceTimersByTime( 500 );

		expect( on ).toHaveBeenCalledTimes( 2 );
		expect( on ).toHaveBeenCalledWith( 'before:save', expect.any( Function ) );
		expect( on ).toHaveBeenCalledWith( 'after:save', expect.any( Function ) );

		// Simulate Elementor firing a real save: before:save carries the true
		// requested status in its args, after:save fires right after with the
		// (differently-shaped) AJAX response.
		const { beforeSave, afterSave } = getRegisteredHandlers( on );
		beforeSave( { status: 'draft' } );
		afterSave( { status: 'draft', config: {} } );

		expect( highlighter.rescanPage ).toHaveBeenCalledTimes( 1 );
		// A save-triggered rescan must not force the panel open unprompted.
		expect( highlighter.rescanPage ).toHaveBeenCalledWith( false );

		// Polling should have stopped once attached.
		expect( jest.getTimerCount() ).toBe( 0 );
	} );

	test.each( [
		'draft',
		'publish',
		'private',
		'pending',
		'future',
		'some-custom-status',
	] )( 'rescans when the requested status is %s, not just a fixed set of Elementor-native statuses', ( status ) => {
		const on = jest.fn();
		const off = jest.fn();
		mockWindowParent( { elementor: { saver: { on, off } } } );
		const highlighter = { rescanPage: jest.fn() };

		setupElementorSaveListener( highlighter, { pollIntervalMs: 500, maxAttempts: 20 } );
		jest.advanceTimersByTime( 500 );

		const { beforeSave, afterSave } = getRegisteredHandlers( on );
		beforeSave( { status } );
		// after:save's own payload never carries the requested status (see the
		// autosave test below), so it must not matter what's passed here.
		afterSave( { status: 'draft' } );

		expect( highlighter.rescanPage ).toHaveBeenCalledWith( false );
	} );

	test( 'does not rescan when the requested status was autosave, regardless of after:save\'s own payload', () => {
		const on = jest.fn();
		const off = jest.fn();
		mockWindowParent( { elementor: { saver: { on, off } } } );
		const highlighter = { rescanPage: jest.fn() };

		setupElementorSaveListener( highlighter, { pollIntervalMs: 500, maxAttempts: 20 } );
		jest.advanceTimersByTime( 500 );

		const { beforeSave, afterSave } = getRegisteredHandlers( on );
		// Elementor's real behavior: an autosave's after:save payload reports the
		// resulting post's real post_status (e.g. 'draft'), never 'autosave' —
		// so the guard must key off the captured before:save status, not this.
		beforeSave( { status: 'autosave' } );
		afterSave( { status: 'draft' } );

		expect( highlighter.rescanPage ).not.toHaveBeenCalled();

		// A real save afterwards should still rescan.
		beforeSave( { status: 'publish' } );
		afterSave( { status: 'publish' } );
		expect( highlighter.rescanPage ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'rescans on after:save even without an observed before:save, defaulting to not missing a real save', () => {
		// In practice Elementor always fires before:save immediately before
		// after:save, but if that ever weren't true, defaulting to "rescan"
		// (rather than silently skipping) is the safer failure mode.
		const on = jest.fn();
		const off = jest.fn();
		mockWindowParent( { elementor: { saver: { on, off } } } );
		const highlighter = { rescanPage: jest.fn() };

		setupElementorSaveListener( highlighter, { pollIntervalMs: 500, maxAttempts: 20 } );
		jest.advanceTimersByTime( 500 );

		const { afterSave } = getRegisteredHandlers( on );
		afterSave( { status: 'draft' } );

		expect( highlighter.rescanPage ).toHaveBeenCalledWith( false );
	} );

	test( 'consumes the captured status so a stale one from a prior save cannot leak into the next after:save', () => {
		const on = jest.fn();
		const off = jest.fn();
		mockWindowParent( { elementor: { saver: { on, off } } } );
		const highlighter = { rescanPage: jest.fn() };

		setupElementorSaveListener( highlighter, { pollIntervalMs: 500, maxAttempts: 20 } );
		jest.advanceTimersByTime( 500 );

		const { beforeSave, afterSave } = getRegisteredHandlers( on );

		// An autosave's before:save/after:save pair.
		beforeSave( { status: 'autosave' } );
		afterSave( { status: 'draft' } );
		expect( highlighter.rescanPage ).not.toHaveBeenCalled();

		// A second after:save with no intervening before:save must not reuse
		// the stale 'autosave' status from the previous cycle.
		afterSave( { status: 'draft' } );
		expect( highlighter.rescanPage ).toHaveBeenCalledWith( false );
	} );

	test( 'detaches both before:save and after:save listeners on pagehide so a stale highlighter is never rescanned', () => {
		const on = jest.fn();
		const off = jest.fn();
		mockWindowParent( { elementor: { saver: { on, off } } } );
		const highlighter = { rescanPage: jest.fn() };

		setupElementorSaveListener( highlighter, { pollIntervalMs: 500, maxAttempts: 20 } );
		jest.advanceTimersByTime( 500 );

		const { beforeSave, afterSave } = getRegisteredHandlers( on );

		window.dispatchEvent( new Event( 'pagehide' ) );

		expect( off ).toHaveBeenCalledTimes( 2 );
		expect( off ).toHaveBeenCalledWith( 'before:save', beforeSave );
		expect( off ).toHaveBeenCalledWith( 'after:save', afterSave );
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

		expect( on ).toHaveBeenCalledWith( 'after:save', expect.any( Function ) );
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
