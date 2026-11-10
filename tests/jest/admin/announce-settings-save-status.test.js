import { announceSettingsSaveStatus } from '../../../src/admin/settings/announce-settings-save-status';

describe( 'announceSettingsSaveStatus', () => {
	let a11y;
	let readyStateSpy;

	beforeEach( () => {
		jest.useFakeTimers();
		document.body.innerHTML = '<div class="edac-settings"></div>';
		a11y = { speak: jest.fn() };
		readyStateSpy = jest.spyOn( document, 'readyState', 'get' ).mockReturnValue( 'interactive' );
	} );

	afterEach( () => {
		readyStateSpy.mockRestore();
		jest.useRealTimers();
	} );

	test( 'focuses a successful save without also announcing it after load', () => {
		document.querySelector( '.edac-settings' ).innerHTML = `
			<div id="setting-error-settings_updated" class="notice notice-success settings-error">
				<p><strong>Settings saved.</strong></p>
			</div>
		`;

		announceSettingsSaveStatus( a11y );

		window.dispatchEvent( new Event( 'load' ) );
		expect( a11y.speak ).not.toHaveBeenCalled();

		jest.advanceTimersByTime( 1000 );
		expect( document.activeElement ).toBe( document.querySelector( '.notice-success p' ) );
		expect( document.activeElement.getAttribute( 'tabindex' ) ).toBe( '-1' );
		expect( a11y.speak ).not.toHaveBeenCalled();
	} );

	test( 'announces a save error assertively', () => {
		document.querySelector( '.edac-settings' ).innerHTML = `
			<div id="setting-error-settings_updated" class="notice notice-error settings-error">
				<p><strong>Settings save failed.</strong></p>
			</div>
		`;

		announceSettingsSaveStatus( a11y );

		window.dispatchEvent( new Event( 'load' ) );
		jest.advanceTimersByTime( 1000 );
		expect( a11y.speak ).toHaveBeenCalledWith( 'Settings save failed.', 'assertive' );
	} );

	test( 'focuses success when the page has already finished loading', () => {
		readyStateSpy.mockReturnValue( 'complete' );
		document.querySelector( '.edac-settings' ).innerHTML = `
			<div id="setting-error-settings_updated" class="notice notice-success settings-error">
				<p><strong>Settings saved.</strong></p>
			</div>
		`;

		announceSettingsSaveStatus( a11y );

		jest.advanceTimersByTime( 1000 );
		expect( document.activeElement ).toBe( document.querySelector( '.notice-success p' ) );
		expect( document.activeElement.getAttribute( 'tabindex' ) ).toBe( '-1' );
		expect( a11y.speak ).not.toHaveBeenCalled();
	} );

	test( 'does not announce when the settings notice is missing', () => {
		announceSettingsSaveStatus( a11y );

		window.dispatchEvent( new Event( 'load' ) );
		jest.runAllTimers();
		expect( a11y.speak ).not.toHaveBeenCalled();
	} );

	test( 'does not announce an empty settings notice', () => {
		document.querySelector( '.edac-settings' ).innerHTML = '<div id="setting-error-settings_updated"></div>';

		announceSettingsSaveStatus( a11y );

		window.dispatchEvent( new Event( 'load' ) );
		jest.runAllTimers();
		expect( a11y.speak ).not.toHaveBeenCalled();
	} );

	test( 'does not announce when the accessibility utility is unavailable', () => {
		document.querySelector( '.edac-settings' ).innerHTML = `
			<div id="setting-error-settings_updated">
				<p><strong>Settings saved.</strong></p>
			</div>
		`;

		announceSettingsSaveStatus();

		window.dispatchEvent( new Event( 'load' ) );
		jest.runAllTimers();
		expect( a11y.speak ).not.toHaveBeenCalled();
	} );
	test( 'focuses success without WordPress speech utilities', () => {
		document.querySelector( '.edac-settings' ).innerHTML = '<div id="setting-error-settings_updated" class="notice-success"><p>Settings saved.</p></div>';
		announceSettingsSaveStatus();
		window.dispatchEvent( new Event( 'load' ) );
		jest.runAllTimers();
		expect( document.activeElement.textContent ).toBe( 'Settings saved.' );
		expect( document.activeElement.tagName ).toBe( 'P' );
	} );

	test( 'preserves existing focus and announces only the message, excluding dismiss controls', () => {
		document.querySelector( '.edac-settings' ).innerHTML = '<div id="setting-error-settings_updated" class="notice-success"><p>Settings saved.</p><button>Dismiss this notice.</button></div>';
		const button = document.querySelector( 'button' );
		button.focus();
		announceSettingsSaveStatus( a11y );
		window.dispatchEvent( new Event( 'load' ) );
		jest.runAllTimers();
		expect( document.activeElement ).toBe( button );
		expect( a11y.speak ).toHaveBeenCalledTimes( 1 );
		expect( a11y.speak ).toHaveBeenCalledWith( 'Settings saved.', 'polite' );
	} );

	test( 'does not handle notices on unrelated admin pages', () => {
		document.body.innerHTML = '<div id="setting-error-settings_updated" class="notice-success"><p>Settings saved.</p></div>';
		announceSettingsSaveStatus( a11y );
		window.dispatchEvent( new Event( 'load' ) );
		jest.runAllTimers();
		expect( document.activeElement ).toBe( document.body );
		expect( a11y.speak ).not.toHaveBeenCalled();
	} );

	test( 'does not steal focus moved while the page was loading', () => {
		document.querySelector( '.edac-settings' ).innerHTML = '<div id="setting-error-settings_updated" class="notice-success"><p>Settings saved.</p></div><button>Continue</button>';
		announceSettingsSaveStatus( a11y );
		const button = document.querySelector( 'button' );
		button.focus();
		window.dispatchEvent( new Event( 'load' ) );
		jest.runAllTimers();
		expect( document.activeElement ).toBe( button );
		expect( a11y.speak ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'does not focus or announce a notice dismissed before page load', () => {
		document.querySelector( '.edac-settings' ).innerHTML = '<div id="setting-error-settings_updated" class="notice-success"><p>Settings saved.</p></div>';
		announceSettingsSaveStatus( a11y );
		document.getElementById( 'setting-error-settings_updated' ).remove();
		window.dispatchEvent( new Event( 'load' ) );
		jest.runAllTimers();
		expect( document.activeElement ).toBe( document.body );
		expect( a11y.speak ).not.toHaveBeenCalled();
	} );
} );
