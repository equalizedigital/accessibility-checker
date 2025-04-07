/**
 * Check for valid use of aria-hidden="true"
 *
 * Elements with aria-hidden="true" should only be used when:
 * - They have specific classes or roles (like wp-block-spacer or presentation)
 * - They are inside a button/link with an aria-label
 * - They are inside a button/link that has screen reader text
 * - They are inside a button/link with visible text
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if the aria-hidden usage is valid, false otherwise.
 */
export default {
	id: 'aria_hidden_valid_usage',
	evaluate: ( node ) => {
		// Check for valid classes
		if ( node.classList.contains( 'wp-block-spacer' ) ) {
			return true;
		}

		// Check for valid roles
		const role = node.getAttribute( 'role' );
		if ( role && role.split( /\s+/ ).includes( 'presentation' ) ) {
			return true;
		}

		// Check parent node for button or anchor
		const parentNode = node.parentElement;
		if ( parentNode &&
			( parentNode.tagName.toLowerCase() === 'button' ||
				parentNode.tagName.toLowerCase() === 'a' ) ) {
			// Parent has non-empty aria-label
			if ( parentNode.hasAttribute( 'aria-label' ) &&
				parentNode.getAttribute( 'aria-label' ).trim() ) {
				return true;
			}

			// Check if parent has visible text content (excluding the aria-hidden element)
			let visibleText = '';
			for ( const childNode of parentNode.childNodes ) {
				if ( childNode !== node && childNode.nodeType === Node.TEXT_NODE ) {
					visibleText += childNode.textContent;
				}
			}
			if ( visibleText.trim() ) {
				return true;
			}

			// Check siblings
			const siblings = Array.from( parentNode.children );

			// Only the element itself exists
			if ( siblings.length <= 1 ) {
				return false;
			}

			// Common screen reader text classes
			const srClasses = [
				'screen-reader-text', 'sr-only', 'show-for-sr', 'visuallyhidden',
				'visually-hidden', 'hidden-visually', 'invisible',
				'accessibly-hidden', 'hide', 'hidden',
			];

			// Check if siblings have screen reader text classes
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

		// If none of the valid conditions are met
		return false;
	},
};
