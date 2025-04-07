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
		// Check if element or parent is hidden with CSS
		const computedStyle = window.getComputedStyle( node );
		if ( computedStyle.display === 'none' || computedStyle.visibility === 'hidden' ) {
			return true;
		}

		const parentNode = node.parentElement;
		if ( ! parentNode ) {
			return false;
		}

		const parentStyle = window.getComputedStyle( parentNode );
		if ( parentStyle.display === 'none' || parentStyle.visibility === 'hidden' ) {
			return true;
		}

		// Check for valid classes or roles
		if ( node.classList.contains( 'wp-block-spacer' ) ) {
			return true;
		}

		const role = node.getAttribute( 'role' );
		if ( role && role.split( /\s+/ ).includes( 'presentation' ) ) {
			return true;
		}

		// Check if parent is button/anchor with accessible content
		const isButtonOrLink = [ 'button', 'a' ].includes( parentNode.tagName.toLowerCase() );

		if ( isButtonOrLink ) {
			// Parent has non-empty aria-label
			if ( parentNode.hasAttribute( 'aria-label' ) &&
				parentNode.getAttribute( 'aria-label' ).trim() ) {
				return true;
			}

			// Check for visible text (excluding the aria-hidden element)
			for ( const childNode of parentNode.childNodes ) {
				if ( childNode !== node &&
					childNode.nodeType === Node.TEXT_NODE &&
					childNode.textContent.trim() ) {
					return true;
				}
			}
		}

		// Check siblings for screen reader text classes
		const siblings = Array.from( parentNode.children );
		if ( siblings.length > 1 ) {
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
		}

		return false;
	},
};
