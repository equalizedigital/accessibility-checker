/**
 * Check for valid use of aria-hidden="true"
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if the aria-hidden usage is valid, false otherwise.
 */

import { isScreenReaderOnly } from '../helpers/helpers';

export default {
	id: 'aria_hidden_valid_usage',
	evaluate: ( node ) => {
		// Check if element is hidden with CSS
		const computedStyle = window.getComputedStyle( node );
		if ( computedStyle.display === 'none' || computedStyle.visibility === 'hidden' ) {
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

		// A sibling with screen reader only text (detected by CSS) gives the content an accessible name.
		// The sibling must still be exposed to assistive technology, and its name can come from image alt text.
		// Siblings share the same ancestors, so the screen reader only result for them is kept between siblings.
		const screenReaderOnlyCache = new Map();
		for ( const sibling of parentNode.children ) {
			if ( sibling === node || sibling.closest( '[aria-hidden="true"]' ) || ! isScreenReaderOnly( sibling, screenReaderOnlyCache ) ) {
				continue;
			}

			const siblingStyle = window.getComputedStyle( sibling );
			if ( siblingStyle.display === 'none' || siblingStyle.visibility === 'hidden' ) {
				continue;
			}

			if (
				sibling.textContent.trim() ||
				sibling.matches( 'img[alt]:not([alt=""])' ) ||
				sibling.querySelector( 'img[alt]:not([alt=""])' )
			) {
				return true;
			}
		}

		return false;
	},
};
