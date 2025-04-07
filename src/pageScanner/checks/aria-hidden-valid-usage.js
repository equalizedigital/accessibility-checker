/**
 * Check for valid use of aria-hidden="true"
 *
 * Elements with aria-hidden="true" should only be used when:
 * - They are hidden via CSS (display:none, visibility:hidden)
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
		// Check if element is already visually hidden with CSS
		const computedStyle = window.getComputedStyle( node );
		if ( computedStyle.display === 'none' || computedStyle.visibility === 'hidden' ) {
			return true;
		}

		// Check if parent element is hidden with CSS
		const parentNode = node.parentElement;
		if ( parentNode ) {
			const parentStyle = window.getComputedStyle( parentNode );
			if ( parentStyle.display === 'none' || parentStyle.visibility === 'hidden' ) {
				return true;
			}
		}

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
		if ( parentNode &&
			( parentNode.tagName.toLowerCase() === 'button' ||
				parentNode.tagName.toLowerCase() === 'a' ) ) {
			// Parent has non-empty aria-label
			if ( parentNode.hasAttribute( 'aria-label' ) &&
				parentNode.getAttribute( 'aria-label' ).trim() ) {
				return true;
			}

			// Check if parent has visible text content (excluding the aria-hidden element)
			// Check if parent has visible text content (excluding the aria-hidden element) 
			// including text from child elements
			let visibleText = '';
			// Helper function to collect text content from element and its children
			function collectTextContent(element, excludeNode) {
				let text = '';
				
				// Process childNodes to get text nodes and element nodes
				for (const child of element.childNodes) {
					// Skip the excluded node
					if (child === excludeNode) {
						continue;
					}
					
					// For text nodes, add their content
					if (child.nodeType === Node.TEXT_NODE) {
						text += child.textContent;
					}
					// For element nodes, recursively collect their text
					else if (child.nodeType === Node.ELEMENT_NODE) {
						text += collectTextContent(child, excludeNode);
					}
				}
				
				return text;
			}
			
			visibleText = collectTextContent(parentNode, node);
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

		// Check siblings for screen reader text classes regardless of parent type
		if ( parentNode ) {
			const siblings = Array.from( parentNode.children );

			// Skip if there's only the element itself
			if ( siblings.length > 1 ) {
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
		}

		// If none of the valid conditions are met
		return false;
	},
};
