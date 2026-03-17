/**
 * WCAG helper utilities
 */

/**
 * Determines whether a WCAG number should be displayed to users.
 *
 * Numbers starting with '0' are internal sorting values used to order
 * non-standard issue types (e.g. 0.1 = Best Practice, 0.2 = Non-WCAG
 * Accessibility Issue, 0.3 = Manual Testing Needed). These are not real
 * WCAG criteria numbers and should never be shown on the front end as
 * they may cause confusion.
 *
 * @param {string|number} wcagNumber - The WCAG number to check.
 * @return {boolean} True if the number should be displayed; false otherwise.
 */
export const shouldDisplayWcagNumber = ( wcagNumber ) => {
	return !! wcagNumber && ! String( wcagNumber ).startsWith( '0' );
};
