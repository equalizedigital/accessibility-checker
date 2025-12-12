/**
 * Check if a form field has multiple labels or conflicting labeling methods.
 * Detects:
 * 1. Multiple HTML <label> elements (explicit or implicit)
 * 2. HTML labels combined with ARIA labeling attributes
 * 3. Multiple ARIA labeling methods used together
 *
 * This is a "none" check, so it returns TRUE when there IS a problem.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if multiple/conflicting labels exist (FAIL), false otherwise (PASS).
 */

export default {
	id: 'form-field-conflicting-labels',
	evaluate: ( node ) => {
		// Get ARIA labeling attributes
		const hasAriaLabel = node.hasAttribute( 'aria-label' ) && node.getAttribute( 'aria-label' ).trim() !== '';
		const hasAriaLabelledby = node.hasAttribute( 'aria-labelledby' ) && node.getAttribute( 'aria-labelledby' ).trim() !== '';

		// Collect all HTML labels (explicit and implicit)
		const htmlLabels = [];

		// Check for explicit labels using for attribute
		if ( node.id ) {
			try {
				const root = node.getRootNode ? node.getRootNode() : document;
				const escapedId = ( typeof CSS !== 'undefined' && CSS.escape ) ? CSS.escape( node.id ) : node.id.replace( /([\\!"#$%&'()*+,./:;<=>?@[\]^`{|}~])/g, '\\$1' );
				const labels = root.querySelectorAll( `label[for="${ escapedId }"]` );

				// Filter out completely hidden labels (display:none or visibility:hidden)
				for ( let i = 0; i < labels.length; i++ ) {
					const label = labels[ i ];
					const style = window.getComputedStyle( label );
					if ( style.display !== 'none' && style.visibility !== 'hidden' ) {
						htmlLabels.push( label );
					}
				}
			} catch ( e ) {
				// If there's an error with the ID, skip explicit label check
			}
		}

		// Check for implicit labels (wrapped)
		let parent = node.parentElement;
		while ( parent ) {
			if ( parent.nodeName.toLowerCase() === 'label' ) {
				const style = window.getComputedStyle( parent );
				if ( style.display !== 'none' && style.visibility !== 'hidden' ) {
					// Only add if not already in the list (could be both implicit and explicit)
					if ( ! htmlLabels.includes( parent ) ) {
						htmlLabels.push( parent );
					}
				}
				break; // Only check the closest label parent
			}
			parent = parent.parentElement;
		}

		// Check for multiple HTML labels
		if ( htmlLabels.length > 1 ) {
			return true; // FAIL: multiple HTML labels
		}

		// Check for conflicting labeling methods
		const hasHtmlLabel = htmlLabels.length > 0;
		const labelingMethodsCount = [
			hasHtmlLabel,
			hasAriaLabel,
			hasAriaLabelledby,
		].filter( Boolean ).length;

		// Return TRUE (fail) if more than one labeling method is used
		// Return FALSE (pass) if zero or one labeling method is used
		return labelingMethodsCount > 1;
	},
};
