/**
 * Focus Management Utilities
 *
 * Helpers for managing focus restoration with fallback strategies
 */

/**
 * Attempt to restore focus with fallback strategy
 *
 * @param {Object} options                  - Focus restoration options
 * @param {Object} options.primaryRef       - Primary element ref to focus (e.g., rule button)
 * @param {string} options.fallbackSelector - CSS selector for fallback element (e.g., panel)
 * @param {string} options.context          - Context description for logging
 */
export const restoreFocusWithFallback = ( { primaryRef, fallbackSelector, context = 'element' } ) => {
	// Use requestAnimationFrame to ensure DOM has updated after transitions
	requestAnimationFrame( () => {
		// Try primary focus target
		if ( primaryRef?.current && document.contains( primaryRef.current ) ) {
			primaryRef.current.focus();
			return;
		}

		// Primary target doesn't exist, try fallback
		if ( fallbackSelector ) {
			const fallbackElement = document.querySelector( fallbackSelector );
			if ( fallbackElement ) {
				// Find first focusable element within fallback container
				// Only include visible elements (not hidden by display:none or visibility:hidden)
				const focusableElements = fallbackElement.querySelectorAll(
					'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])',
				);

				// Filter to only visible elements
				for ( const element of focusableElements ) {
					const style = window.getComputedStyle( element );
					const isVisible = style.display !== 'none' &&
						style.visibility !== 'hidden' &&
						element.offsetParent !== null;

					if ( isVisible ) {
						element.focus();
						return;
					}
				}

				// If no focusable elements, try to focus the container itself if it's focusable
				if ( fallbackElement.tabIndex >= 0 ) {
					fallbackElement.focus();
					return;
				}
			}
		}

		// If all else fails, focus restoration failed silently
		// This is expected behavior when elements don't exist
		if ( context ) {
			// Context used for debugging if needed
		}
	} );
};

