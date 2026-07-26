import { announceSettingsSaveStatus } from '../../../src/admin/settings/announce-settings-save-status';

describe( 'announceSettingsSaveStatus', () => {
	let a11y;
	let readyStateSpy;

	beforeEach( () => {
		jest.useFakeTimers();
		document.body.innerHTML = '';
		a11y = { speak: jest.fn() };
		readyStateSpy = jest.spyOn( document, 'readyState', 'get' ).mockReturnValue( 'interactive' );
	} );

	afterEach( () => {
		readyStateSpy.mockRestore();
		jest.useRealTimers();
	} );

	test( 'announces a successful save politely after the page finishes loading', () => {
		document.body.innerHTML = `
			<div id="setting-error-settings_updated" class="notice notice-success settings-error">
				<p><strong>Settings saved.</strong></p>
			</div>
		`;

		announceSettingsSaveStatus( a11y );

		window.dispatchEvent( new Event( 'load' ) );
		expect( a11y.speak ).not.toHaveBeenCalled();

		jest.advanceTimersByTime( 1000 );
		expect( a11y.speak ).toHaveBeenCalledWith( 'Settings saved.', 'polite' );
	} );

	test( 'announces a save error assertively', () => {
		document.body.innerHTML = `
			<div id="setting-error-settings_updated" class="notice notice-error settings-error">
				<p><strong>Settings save failed.</strong></p>
			</div>
		`;

		announceSettingsSaveStatus( a11y );

		window.dispatchEvent( new Event( 'load' ) );
		jest.advanceTimersByTime( 1000 );
		expect( a11y.speak ).toHaveBeenCalledWith( 'Settings save failed.', 'assertive' );
	} );

	test( 'announces when the page has already finished loading', () => {
		readyStateSpy.mockReturnValue( 'complete' );
		document.body.innerHTML = `
			<div id="setting-error-settings_updated" class="notice notice-success settings-error">
				<p><strong>Settings saved.</strong></p>
			</div>
		`;

		announceSettingsSaveStatus( a11y );

		jest.advanceTimersByTime( 1000 );
		expect( a11y.speak ).toHaveBeenCalledWith( 'Settings saved.', 'polite' );
	} );

	test( 'does not announce when the settings notice is missing', () => {
		announceSettingsSaveStatus( a11y );

		window.dispatchEvent( new Event( 'load' ) );
		jest.runAllTimers();
		expect( a11y.speak ).not.toHaveBeenCalled();
	} );

	test( 'does not announce an empty settings notice', () => {
		document.body.innerHTML = '<div id="setting-error-settings_updated"></div>';

		announceSettingsSaveStatus( a11y );

		window.dispatchEvent( new Event( 'load' ) );
		jest.runAllTimers();
		expect( a11y.speak ).not.toHaveBeenCalled();
	} );

	test( 'does not announce when the accessibility utility is unavailable', () => {
		document.body.innerHTML = `
			<div id="setting-error-settings_updated">
				<p><strong>Settings saved.</strong></p>
			</div>
		`;

		announceSettingsSaveStatus();

		window.dispatchEvent( new Event( 'load' ) );
		jest.runAllTimers();
		expect( a11y.speak ).not.toHaveBeenCalled();
	} );
} );
