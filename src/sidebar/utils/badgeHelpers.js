/**
 * Badge helper utilities
 */

import { __ } from '@wordpress/i18n';

/**
 * Convert rule type to badge properties (label, type for styling, icon)
 *
 * @param {string} ruleType - The rule type value.
 * @return {Object} Object with label, type, and icon properties.
 */
export const getRuleTypeBadgeProps = ( ruleType ) => {
	if ( ! ruleType ) {
		return null;
	}

	// Normalize the rule type
	const normalizedType = ruleType.toLowerCase().trim();

	// Map rule types to badge properties
	const typeMap = {
		error: {
			label: __( 'Problem', 'accessibility-checker' ),
			type: 'error',
			icon: 'error',
		},
		warning: {
			label: __( 'Needs Review', 'accessibility-checker' ),
			type: 'warning',
			icon: 'warning',
		},
	};

	// Return exact match if available
	if ( typeMap[ normalizedType ] ) {
		return typeMap[ normalizedType ];
	}

	// If no exact match, try to infer from the label itself
	if ( normalizedType.includes( 'error' ) ) {
		return typeMap.error;
	}

	if ( normalizedType.includes( 'warning' ) ) {
		return typeMap.warning;
	}

	// Default return with the original label
	return {
		label: ruleType,
		type: 'info',
		icon: 'info',
	};
};

/**
 * Convert numeric severity to badge properties (label, type for styling, icon)
 *
 * @param {number|string} severity - Severity value (1-4 or string).
 * @return {Object} Object with label, type, and icon properties.
 */
export const getSeverityBadgeProps = ( severity ) => {
	// If already a string, convert to numeric if possible
	let numericSeverity = severity;
	if ( typeof severity === 'string' ) {
		numericSeverity = parseInt( severity, 10 );
	}

	// Map severity levels to badge properties
	const severityMap = {
		1: {
			label: __( 'Critical', 'accessibility-checker' ),
			type: 'severity-critical',
		},
		2: {
			label: __( 'High', 'accessibility-checker' ),
			type: 'severity-high',
		},
		3: {
			label: __( 'Medium', 'accessibility-checker' ),
			type: 'severity-medium',
		},
		4: {
			label: __( 'Low', 'accessibility-checker' ),
			type: 'severity-low',
		},
	};

	return severityMap[ numericSeverity ] || null;
};
