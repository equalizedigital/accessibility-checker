/**
 * Dismiss reason helper utilities
 *
 * Dismiss reasons are defined in PHP (Dismiss_Reasons::get_reasons()) and
 * localized to window.edac_sidebar_app.dismissReasons so that the same
 * translatable strings are shared between the sidebar, issue modal, and
 * classic metabox.
 */

/**
 * Dismiss reason data as an object keyed by value.
 *
 * Reads from the PHP-localized data at runtime. Falls back to
 * empty objects so callers never get undefined.
 *
 * @type {Object<string, {label: string, description: string}>}
 */
export const DISMISS_REASONS = window.edac_sidebar_app?.dismissReasons ?? {};

/**
 * Get dismiss reason options for RadioControl
 *
 * Returns an array of dismiss reason options with labels and descriptions
 * that can be used in RadioControl or similar components.
 * Dynamically generated from DISMISS_REASONS to reduce duplication.
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
