/**
 * Dismiss reason helper utilities
 */

import { __ } from '@wordpress/i18n';

/**
 * Dismiss reason data as an object keyed by value
 *
 * Provides quick lookup of dismiss reason data without filtering.
 * Use this when you need to access a specific reason's label or description.
 *
 * @type {Object<string, {label: string, description: string}>}
 */
export const DISMISS_REASONS = {
	false_positive: {
		label: __( 'False positive', 'accessibility-checker' ),
		description: __(
			'The scanner flagged this, but it does not apply to this content.',
			'accessibility-checker',
		),
	},
	remediated: {
		label: __( 'Remediated', 'accessibility-checker' ),
		description: __(
			'The issue has been fixed, but the page has not been rescanned yet.',
			'accessibility-checker',
		),
	},
	accessible: {
		label: __( 'Confirmed accessible', 'accessibility-checker' ),
		description: __(
			'Reviewed and verified to meet accessibility requirements.',
			'accessibility-checker',
		),
	},
};

/**
 * Get dismiss reason options for RadioControl
 *
 * Returns an array of dismiss reason options with labels and descriptions
 * that can be used in RadioControl or similar components.
 * Dynamically generated from DISMISS_REASONS constant to reduce duplication.
 *
 * @return {Array} Array of dismiss reason option objects.
 */
export const getDismissReasonOptions = () => {
	return Object.entries( DISMISS_REASONS ).map( ( [ value, data ] ) => ( {
		value,
		label: data.label,
		description: data.description,
	} ) );
};

/**
 * Get a dismiss reason label by value
 *
 * @param {string} value - The dismiss reason value (false_positive, remediated, accessible).
 * @return {string} The human-readable label for the reason.
 */
export const getDismissReasonLabel = ( value ) => {
	return DISMISS_REASONS[ value ]?.label ?? '';
};

/**
 * Get a dismiss reason description by value
 *
 * @param {string} value - The dismiss reason value (false_positive, remediated, accessible).
 * @return {string} The description for the reason.
 */
export const getDismissReasonDescription = ( value ) => {
	return DISMISS_REASONS[ value ]?.description ?? '';
};
