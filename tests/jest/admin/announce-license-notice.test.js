import { announceLicenseNotice } from '../../../src/admin/settings/announce-settings-save-status';

describe( 'announceLicenseNotice', () => {
	let a11y;
	let readyStateSpy;

	const setUrl = ( query ) => {
		window.history.replaceState( null, '', '/wp-admin/admin.php' + query );
	};

	const renderNotice = ( text = 'This license key is not active for this site.' ) => {
		document.body.innerHTML = `<div id="edac-license-notice" class="notice notice-error"><p>${ text }</p></div>`;
	};

	beforeEach( () => {
		jest.useFakeTimers();
		document.body.innerHTML = '';
		a11y = { speak: jest.fn() };
		readyStateSpy = jest.spyOn( document, 'readyState', 'get' ).mockReturnValue( 'interactive' );
		setUrl( '?page=accessibility_checker_settings&tab=accessibility-reports&edac_license_submitted=1' );
	} );

	afterEach( () => {
		readyStateSpy.mockRestore();
		jest.useRealTimers();
	} );

	test( 'announces the license error assertively after a submit', () => {
		renderNotice();

		announceLicenseNotice( a11y );

		window.dispatchEvent( new Event( 'load' ) );
		expect( a11y.speak ).not.toHaveBeenCalled();

		jest.advanceTimersByTime( 1000 );
		expect( a11y.speak ).toHaveBeenCalledTimes( 1 );
		expect( a11y.speak ).toHaveBeenCalledWith( 'This license key is not active for this site.', 'assertive' );
	} );

	test( 'announces when the page has already finished loading', () => {
		readyStateSpy.mockReturnValue( 'complete' );
		renderNotice();

		announceLicenseNotice( a11y );

		jest.advanceTimersByTime( 1000 );
		expect( a11y.speak ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'removes the submit flag from the URL and keeps other params', () => {
		renderNotice();

		announceLicenseNotice( a11y );

		expect( window.location.search ).toBe( '?page=accessibility_checker_settings&tab=accessibility-reports' );
	} );

	test( 'does not announce a stored error on a normal page load', () => {
		setUrl( '?page=accessibility_checker_settings&tab=accessibility-reports' );
		renderNotice();

		announceLicenseNotice( a11y );

		window.dispatchEvent( new Event( 'load' ) );
		jest.runAllTimers();
		expect( a11y.speak ).not.toHaveBeenCalled();
	} );

	test( 'does not announce when the submit had no error', () => {
		announceLicenseNotice( a11y );

		window.dispatchEvent( new Event( 'load' ) );
		jest.runAllTimers();
		expect( a11y.speak ).not.toHaveBeenCalled();
		expect( window.location.search ).not.toContain( 'edac_license_submitted' );
	} );

	test( 'does not announce an empty notice', () => {
		renderNotice( '' );

		announceLicenseNotice( a11y );

		window.dispatchEvent( new Event( 'load' ) );
		jest.runAllTimers();
		expect( a11y.speak ).not.toHaveBeenCalled();
	} );

	test( 'does not fail when the accessibility utility is unavailable', () => {
		renderNotice();

		expect( () => {
			announceLicenseNotice();
			window.dispatchEvent( new Event( 'load' ) );
			jest.runAllTimers();
		} ).not.toThrow();
	} );

	test( 'does not announce a notice dismissed before the delay ends', () => {
		renderNotice();

		announceLicenseNotice( a11y );
		window.dispatchEvent( new Event( 'load' ) );
		document.getElementById( 'edac-license-notice' ).remove();
		jest.runAllTimers();
		expect( a11y.speak ).not.toHaveBeenCalled();
	} );
} );
