/**
 * Check for valid use of aria-hidden="true"
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if the aria-hidden usage is valid, false otherwise.
 */

// Common screen reader text classes
const srClasses = [
	'screen-reader-text', 'sr-only', 'show-for-sr', 'visuallyhidden',
	'visually-hidden', 'hidden-visually', 'invisible',
	'accessibly-hidden', 'hide', 'hidden',
];

export default {
	id: 'aria_hidden_valid_usage',
	evaluate: ( node ) => {
		// Check if element is hidden with CSS
		const computedStyle = window.getComputedStyle( node );
		if ( computedStyle.display === 'none' || computedStyle.visibility === 'hidden' ) {
			return true;
		}

		// Check for valid element properties
		if ( node.classList.contains( 'wp-block-spacer' ) ) {
			return true;
		}

		const role = node.getAttribute( 'role' );
		if ( role?.split( /\s+/ ).includes( 'presentation' ) ) {
			return true;
		}

		const parentNode = node.parentElement;
		if ( ! parentNode ) {
			return false;
		}

		// Check if parent is hidden with CSS
		const parentStyle = window.getComputedStyle( parentNode );
		if ( parentStyle.display === 'none' || parentStyle.visibility === 'hidden' ) {
			return true;
		}

		// Check if parent is button/anchor with accessible content
		if ( [ 'button', 'a' ].includes( parentNode.tagName.toLowerCase() ) ) {
			// Parent has non-empty aria-label

			if (
				(
					parentNode.hasAttribute( 'aria-label' ) &&
					parentNode.getAttribute( 'aria-label' ).trim()
				) ||
				(
					parentNode.hasAttribute( 'aria-labelledby' ) &&
					document.getElementById( parentNode.getAttribute( 'aria-labelledby' ) )
				)
			) {
				return true;
			}

			// Check for visible text (excluding the aria-hidden element)
			for ( const child of parentNode.childNodes ) {
				if ( child === node ) {
					continue;
				}

				// Direct text node
				if ( child.nodeType === Node.TEXT_NODE &&
                                        child.textContent.trim() ) {
					return true;
				}

				// Text within an element node
				if ( child.nodeType === Node.ELEMENT_NODE &&
                                        ! child.hasAttribute( 'aria-hidden' ) ) {
					const style = window.getComputedStyle( child );
					if ( style.display !== 'none' &&
                                                style.visibility !== 'hidden' &&
                                                child.textContent.trim() ) {
						return true;
					}
				}
			}
		}

		// Check siblings for screen reader text classes
		const siblings = Array.from( parentNode.children );
		for ( const sibling of siblings ) {
			if ( sibling !== node ) {
				for ( const srClass of srClasses ) {
					if ( sibling.classList.contains( srClass ) ||
						sibling.className.toLowerCase().includes( srClass ) ) {
						return true;
					}
				}
			}
		}

		return false;
	},
};
