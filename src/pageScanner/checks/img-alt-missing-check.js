/**
 * Check if image is missing alt text.
 * Ported from PHP function edac_rule_img_alt_missing
 *
 * This check evaluates whether an image element has appropriate alternative text.
 * It considers various scenarios:
 * - Images with role="presentation" or aria-hidden="true" (decorative)
 * - Images with aria-labelledby references
 * - Images in figure/figcaption contexts
 * - Images in links or buttons with text
 *
 * @see https://www.w3.org/WAI/tutorials/images/ for best practices on image alternatives
 */

export default {
	id: 'img_alt_missing_check',
	evaluate( node ) {
		// Get the tag name
		const tagName = node.tagName.toLowerCase();

		// Handle role="presentation"
		if ( node.hasAttribute( 'role' ) && node.getAttribute( 'role' ) === 'presentation' ) {
			// Images with role="presentation" are intentionally hidden from screen readers,
			// so missing alt text is acceptable in this case
			return false;
		}

		// Handle aria-hidden="true"
		if ( node.hasAttribute( 'aria-hidden' ) && node.getAttribute( 'aria-hidden' ) === 'true' ) {
			// Images with aria-hidden="true" are not exposed to assistive technologies,
			// so missing alt text is acceptable in this case
			return false;
		}

		// Handle aria-labelledby
		if ( node.hasAttribute( 'aria-labelledby' ) ) {
			const labelId = node.getAttribute( 'aria-labelledby' );
			const labelElement = document.getElementById( labelId );
			if ( labelElement && labelElement.textContent.trim() !== '' ) {
				return false;
			}
		}

		// Check if alt attribute exists AND has a value
		const hasAlt = node.hasAttribute( 'alt' ) && node.getAttribute( 'alt' ) !== null;

		// For input[type="image"], we need alt text
		if ( tagName === 'input' && node.getAttribute( 'type' ) === 'image' ) {
			// Return true (flag issue) if alt attribute is missing
			return ! hasAlt;
		}

		// For images, we need the alt attribute to be defined
		if ( tagName === 'img' ) {
			// Check if image is inside a caption container
			if ( hasValidCaption( node ) ) {
				return false;
			}

			// Check if image is inside a link with context
			if ( hasLinkContext( node ) ) {
				return false;
			}

			// Check if image is inside a button with text content
			if ( hasButtonContext( node ) ) {
				return false;
			}

			// Return true (flag issue) if alt attribute is missing
			return ! hasAlt;
		}

		// If we reach here we haven't found anything that says the alt is missing.
		return false;
	},
	options: {},
	metadata: {
		impact: 'critical',
		messages: {
			pass: 'Image has an alt attribute',
			fail: 'Image is missing an alt attribute',
		},
	},
};

/**
 * Check if the image is inside a figure or div with caption
 * @param {Element} node - The DOM element to check.
 * @return {boolean} A boolean indicating if the image is inside a caption container.
 */
const hasValidCaption = ( node ) => {
	// Check for HTML5 figure with figcaption
	let parent = node.parentNode;
	while ( parent ) {
		if ( parent.tagName && parent.tagName.toLowerCase() === 'figure' ) {
			const figcaptions = parent.querySelectorAll( 'figcaption' );
			if ( figcaptions.length > 0 && figcaptions[ 0 ].textContent.trim() !== '' ) {
				return true;
			}
			break;
		}
		parent = parent.parentNode;
	}

	// Check for div with wp-caption class
	parent = node.parentNode;
	while ( parent ) {
		if ( parent.tagName &&
			parent.tagName.toLowerCase() === 'div' &&
			parent.classList.contains( 'wp-caption' ) ) {
			// Check if the div has meaningful text content
			if ( parent.textContent && parent.textContent.trim().length > 5 ) {
				return true;
			}
			break;
		}
		parent = parent.parentNode;
	}

	return false;
};

/**
 * Helper to check if a node contains meaningful text content (excluding a specific child node)
 * @param {Element} parent      - The parent element to check
 * @param {Element} excludeNode - The node to exclude from text content check
 * @return {boolean} Whether the parent contains meaningful text content
 */
const hasTextContent = ( parent, excludeNode ) => {
	// Get all text nodes in the parent excluding the specified node
	const textNodes = Array.from( parent.childNodes )
		.filter( ( child ) => child !== excludeNode && child.nodeType === 3 );

	// Check if there's any non-whitespace text content
	const nodeContent = textNodes
		.map( ( child ) => child.textContent )
		.join( '' );

	return nodeContent.trim() !== '';
};

/**
 * Check if the image is inside a link with context
 * @param {Element} node - The DOM element to check.
 * @return {boolean} A boolean indicating if the image is inside a link with context.
 */
const hasLinkContext = ( node ) => {
	// Check if inside an anchor tag with accessible text
	let parent = node.parentNode;
	while ( parent ) {
		if ( parent.tagName && parent.tagName.toLowerCase() === 'a' ) {
			// Check if the anchor has non-empty aria-label
			if ( parent.hasAttribute( 'aria-label' ) && parent.getAttribute( 'aria-label' ).trim() !== '' ) {
				return true;
			}

			// Check if the anchor has non-empty title
			if ( parent.hasAttribute( 'title' ) && parent.getAttribute( 'title' ).trim() !== '' ) {
				return true;
			}

			// Check if the anchor has meaningful text content
			if ( hasTextContent( parent, node ) ) {
				return true;
			}

			break;
		}
		parent = parent.parentNode;
	}

	return false;
};

/**
 * Check if the image is inside a button with text content
 * @param {Element} node - The DOM element to check.
 * @return {boolean} A boolean indicating if the image is inside a button with text content.
 */
const hasButtonContext = ( node ) => {
	let parent = node.parentNode;
	while ( parent ) {
		if ( parent.tagName && parent.tagName.toLowerCase() === 'button' ) {
			if ( hasTextContent( parent, node ) ) {
				return true;
			}
			break;
		}
		parent = parent.parentNode;
	}

	return false;
};

