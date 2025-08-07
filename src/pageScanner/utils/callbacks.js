/**
 * Callback registry for pre-scan rule filtering and modification
 *
 * This module provides a system for registering callbacks that can filter
 * or modify the rules, checks, and options before the accessibility scan runs.
 */

// Registry for different types of callbacks
const callbacks = {
	// Callbacks for filtering/modifying rules array
	filterRules: [],
	// Callbacks for filtering/modifying checks array
	filterChecks: [],
	// Callbacks for modifying run options
	filterRunOptions: [],
	// Callbacks for modifying config options
	filterConfigOptions: [],
};

/**
 * Register a callback to filter or modify the rules array before scanning
 *
 * @param {Function} callback Function that receives (rules) and returns modified rules array
 */
export function addRulesFilter( callback ) {
	if ( typeof callback === 'function' ) {
		callbacks.filterRules.push( callback );
	}
}

/**
 * Register a callback to filter or modify the checks array before scanning
 *
 * @param {Function} callback Function that receives (checks) and returns modified checks array
 */
export function addChecksFilter( callback ) {
	if ( typeof callback === 'function' ) {
		callbacks.filterChecks.push( callback );
	}
}

/**
 * Register a callback to filter or modify the run options before scanning
 *
 * @param {Function} callback Function that receives (runOptions) and returns modified run options
 */
export function addRunOptionsFilter( callback ) {
	if ( typeof callback === 'function' ) {
		callbacks.filterRunOptions.push( callback );
	}
}

/**
 * Register a callback to filter or modify the config options before scanning
 *
 * @param {Function} callback Function that receives (configOptions) and returns modified config options
 */
export function addConfigOptionsFilter( callback ) {
	if ( typeof callback === 'function' ) {
		callbacks.filterConfigOptions.push( callback );
	}
}

/**
 * Apply all registered rules filters to the rules array
 *
 * @param {Array} rules Initial rules array
 * @return {Array} Filtered rules array
 */
export function applyRulesFilters( rules ) {
	return callbacks.filterRules.reduce( ( currentRules, callback ) => {
		try {
			const filtered = callback( currentRules );
			return Array.isArray( filtered ) ? filtered : currentRules;
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.warn( 'Error in rules filter callback:', error );
			return currentRules;
		}
	}, rules );
}

/**
 * Apply all registered checks filters to the checks array
 *
 * @param {Array} checks Initial checks array
 * @return {Array} Filtered checks array
 */
export function applyChecksFilters( checks ) {
	return callbacks.filterChecks.reduce( ( currentChecks, callback ) => {
		try {
			const filtered = callback( currentChecks );
			return Array.isArray( filtered ) ? filtered : currentChecks;
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.warn( 'Error in checks filter callback:', error );
			return currentChecks;
		}
	}, checks );
}

/**
 * Apply all registered run options filters to the run options
 *
 * @param {Object} runOptions Initial run options
 * @return {Object} Filtered run options
 */
export function applyRunOptionsFilters( runOptions ) {
	return callbacks.filterRunOptions.reduce( ( currentOptions, callback ) => {
		try {
			const filtered = callback( currentOptions );
			return filtered && typeof filtered === 'object' ? filtered : currentOptions;
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.warn( 'Error in run options filter callback:', error );
			return currentOptions;
		}
	}, runOptions );
}

/**
 * Apply all registered config options filters to the config options
 *
 * @param {Object} configOptions Initial config options
 * @return {Object} Filtered config options
 */
export function applyConfigOptionsFilters( configOptions ) {
	return callbacks.filterConfigOptions.reduce( ( currentOptions, callback ) => {
		try {
			const filtered = callback( currentOptions );
			return filtered && typeof filtered === 'object' ? filtered : currentOptions;
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.warn( 'Error in config options filter callback:', error );
			return currentOptions;
		}
	}, configOptions );
}

/**
 * Clear all registered callbacks (useful for testing)
 */
export function clearAllCallbacks() {
	callbacks.filterRules.length = 0;
	callbacks.filterChecks.length = 0;
	callbacks.filterRunOptions.length = 0;
	callbacks.filterConfigOptions.length = 0;
}

/**
 * Get count of registered callbacks by type (useful for testing)
 *
 * @return {Object} Object with counts for each callback type
 */
export function getCallbackCounts() {
	return {
		rules: callbacks.filterRules.length,
		checks: callbacks.filterChecks.length,
		runOptions: callbacks.filterRunOptions.length,
		configOptions: callbacks.filterConfigOptions.length,
	};
}
