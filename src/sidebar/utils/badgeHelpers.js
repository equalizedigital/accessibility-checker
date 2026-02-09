/**
 * Badge helper utilities
 */

import { __ } from '@wordpress/i18n';

/**
 * Get badge props for rule type
 *
 * @param {string} ruleType - Rule type ('error', 'warning', 'color_contrast').
 * @return {Object|null} Badge props object with label, type, and optional icon.
 */
export const getRuleTypeBadgeProps = ( ruleType ) => {
	const ruleTypeMap = {
		error: {
			label: __( 'Error', 'accessibility-checker' ),
			type: 'error',
			icon: 'close-circle',
		},
		warning: {
			label: __( 'Warning', 'accessibility-checker' ),
			type: 'warning',
			icon: 'warning',
		},
		color_contrast: {
			label: __( 'Contrast', 'accessibility-checker' ),
			type: 'info',
			icon: 'contrast',
		},
	};

	return ruleTypeMap[ ruleType ] || null;
};

/**
 * Get badge props for severity level
 *
 * @param {number|string} severity - Severity level (1-4 or string label).
 * @return {Object|null} Badge props object with label and type.
 */
export const getSeverityBadgeProps = ( severity ) => {
	// Handle string severity labels
	const severityMap = {
		1: {
			label: __( 'Critical', 'accessibility-checker' ),
			type: 'error',
		},
		2: {
			label: __( 'High', 'accessibility-checker' ),
			type: 'warning',
		},
		3: {
			label: __( 'Medium', 'accessibility-checker' ),
			type: 'info',
		},
		4: {
			label: __( 'Low', 'accessibility-checker' ),
			type: 'success',
		},
		Critical: {
			label: __( 'Critical', 'accessibility-checker' ),
			type: 'error',
		},
		High: {
			label: __( 'High', 'accessibility-checker' ),
			type: 'warning',
		},
		Medium: {
			label: __( 'Medium', 'accessibility-checker' ),
			type: 'info',
		},
		Low: {
			label: __( 'Low', 'accessibility-checker' ),
			type: 'success',
		},
	};

	return severityMap[ severity ] || null;
};
