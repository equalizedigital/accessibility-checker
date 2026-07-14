/**
 * Severity helper utilities
 */

import { __ } from '@wordpress/i18n';

/**
 * Convert numeric severity to text label
 *
 * @param {number|string} severity - Severity value (1-4 or string).
 * @return {string} Severity label.
 */
export const getSeverityLabel = ( severity ) => {
	// If already a string, return it
	if ( typeof severity === 'string' ) {
		return severity;
	}

	// Convert numeric severity to label
	const severityMap = {
		1: __( 'Critical', 'accessibility-checker' ),
		2: __( 'High', 'accessibility-checker' ),
		3: __( 'Medium', 'accessibility-checker' ),
		4: __( 'Low', 'accessibility-checker' ),
	};

	return severityMap[ severity ] || '';
};
