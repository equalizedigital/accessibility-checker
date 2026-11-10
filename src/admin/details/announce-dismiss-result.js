import { __ } from '@wordpress/i18n';

/**
 * Announce the result of dismissing or reopening an issue.
 *
 * The details panel is replaced after a successful request, so a live region
 * inside it can be removed before it is read. wp.a11y.speak writes to regions
 * outside the panel that stay on the page.
 *
 * @param {Object}  a11y              WordPress accessibility utilities.
 * @param {string}  action            The REST action, `dismiss` or `undismiss`.
 * @param {boolean} success           Whether the request succeeded.
 * @param {Object}  [options]         Options.
 * @param {string}  [options.message] Message to use for a failure, such as the server's error.
 * @param {number}  [options.delay]   Milliseconds to wait before announcing. Lets a screen reader finish
 *                                    reading the changes to the focused button first.
 */
export const announceDismissResult = ( a11y, action, success, { message = '', delay = 0 } = {} ) => {
	if ( ! a11y || typeof a11y.speak !== 'function' ) {
		return;
	}

	const isDismiss = 'dismiss' === action;

	if ( success ) {
		const confirmation = isDismiss
			? __( 'Successfully dismissed issue', 'accessibility-checker' )
			: __( 'Successfully undismissed issue', 'accessibility-checker' );

		if ( delay > 0 ) {
			window.setTimeout( () => a11y.speak( confirmation, 'polite' ), delay );
		} else {
			a11y.speak( confirmation, 'polite' );
		}
		return;
	}

	let failure = isDismiss
		? __( 'Failed to dismiss issue', 'accessibility-checker' )
		: __( 'Failed to undismiss issue', 'accessibility-checker' );

	if ( 'string' === typeof message && message.trim() ) {
		failure = message.trim();
	}

	a11y.speak( failure, 'assertive' );
};
