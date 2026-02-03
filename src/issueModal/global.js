/**
 * Global Issue Modal API helper
 */

/**
 * Open the global issue modal if available.
 *
 * @param {Object} args - Modal arguments (issue, rule, focusSection, onIgnore).
 */
export const openIssueModal = ( args ) => {
	if ( typeof window === 'undefined' ) {
		return;
	}

	window.edacIssueModal?.open?.( args );
};

/**
 * Close the global issue modal if available.
 */
export const closeIssueModal = () => {
	if ( typeof window === 'undefined' ) {
		return;
	}

	window.edacIssueModal?.close?.();
};
