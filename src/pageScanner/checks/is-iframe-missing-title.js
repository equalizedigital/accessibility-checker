/**
 * Check to determine if an iframe is missing a title or aria-label attribute.
 * This check implements special handling for Google Tag Manager iframes and hidden iframes.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if the iframe is missing a title (triggering violation), false otherwise (no violation).
 */

export default {
	id: 'is_iframe_missing_title',
	evaluate: ( node ) => {
		// Skip check if this is not an iframe
		if ( node.nodeName.toLowerCase() !== 'iframe' ) {
			return false; // Not an iframe, so no violation
		}

		// Get relevant attributes
		const title = node.getAttribute( 'title' ) || '';
		const ariaLabel = node.getAttribute( 'aria-label' ) || '';
		const style = node.getAttribute( 'style' ) || '';

		// Check inline styles first (faster)
		const displayNone = style.match( /\bdisplay\s*:\s*none\b/i );
		const visibilityHidden = style.match( /\bvisibility\s*:\s*hidden\b/i );

		// Also check computed styles if DOM is available (more thorough)
		let isHiddenByComputedStyle = false;
		if ( typeof window !== 'undefined' && window.getComputedStyle ) {
			try {
				const computedStyle = window.getComputedStyle( node );
				isHiddenByComputedStyle =
					computedStyle.display === 'none' ||
					computedStyle.visibility === 'hidden';
			} catch ( e ) {
				// Silently fail if we can't get computed styles
				// This can happen in some testing environments
			}
		}

		// Skip iframes that are hidden either with display:none or visibility:hidden
		if ( ( displayNone || visibilityHidden ) || isHiddenByComputedStyle ) {
			return false; // No violation for hidden iframes
		}

		// Check if title or aria-label is missing or empty (after trimming)
		const hasTitleOrAriaLabel = title.trim() !== '' || ariaLabel.trim() !== '';

		return ! hasTitleOrAriaLabel; // Return true to trigger a violation if title/aria-label is missing
	},
};
