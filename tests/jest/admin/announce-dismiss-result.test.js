import { announceDismissResult } from '../../../src/admin/details/announce-dismiss-result';

describe( 'announceDismissResult', () => {
	let a11y;

	beforeEach( () => {
		a11y = { speak: jest.fn() };
	} );

	test( 'announces a dismissed issue politely', () => {
		announceDismissResult( a11y, 'dismiss', true );

		expect( a11y.speak ).toHaveBeenCalledTimes( 1 );
		expect( a11y.speak ).toHaveBeenCalledWith( 'Successfully dismissed issue', 'polite' );
	} );

	test( 'announces a reopened issue politely', () => {
		announceDismissResult( a11y, 'undismiss', true );

		expect( a11y.speak ).toHaveBeenCalledWith( 'Successfully undismissed issue', 'polite' );
	} );

	test( 'announces a failed dismiss assertively', () => {
		announceDismissResult( a11y, 'dismiss', false );

		expect( a11y.speak ).toHaveBeenCalledWith( 'Failed to dismiss issue', 'assertive' );
	} );

	test( 'announces a failed reopen with the reopen wording, not the dismiss wording', () => {
		announceDismissResult( a11y, 'undismiss', false );

		expect( a11y.speak ).toHaveBeenCalledWith( 'Failed to undismiss issue', 'assertive' );
	} );

	test( 'uses the server message for a failure when there is one', () => {
		announceDismissResult( a11y, 'dismiss', false, 'Sorry, you are not allowed to dismiss issues.' );

		expect( a11y.speak ).toHaveBeenCalledWith( 'Sorry, you are not allowed to dismiss issues.', 'assertive' );
	} );

	test.each( [ undefined, null, '', '   ' ] )( 'falls back to the default failure text for a missing message (%p)', ( message ) => {
		announceDismissResult( a11y, 'dismiss', false, message );

		expect( a11y.speak ).toHaveBeenCalledWith( 'Failed to dismiss issue', 'assertive' );
	} );

	test( 'ignores a server message on success', () => {
		announceDismissResult( a11y, 'dismiss', true, 'ignored' );

		expect( a11y.speak ).toHaveBeenCalledWith( 'Successfully dismissed issue', 'polite' );
	} );

	test( 'does not throw when the accessibility utility is unavailable', () => {
		expect( () => announceDismissResult( undefined, 'dismiss', true ) ).not.toThrow();
		expect( () => announceDismissResult( {}, 'dismiss', false ) ).not.toThrow();
	} );
} );
