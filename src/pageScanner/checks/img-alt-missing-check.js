/**
 * Check if image is missing alt text.
 * Ported from PHP function edac_rule_img_alt_missing
 */

export default {
	id: 'img_alt_missing_check',
	evaluate( node ) {
		// Get the tag name
		const tagName = node.tagName.toLowerCase();

		// Handle role="presentation"
		if ( node.hasAttribute( 'role' ) && node.getAttribute( 'role' ) === 'presentation' ) {
			return true;
		}

		// Handle aria-hidden="true"
		if ( node.hasAttribute( 'aria-hidden' ) && node.getAttribute( 'aria-hidden' ) === 'true' ) {
			return true;
		}

		// Handle aria-labelledby
		if ( node.hasAttribute( 'aria-labelledby' ) ) {
			const labelId = node.getAttribute( 'aria-labelledby' );
			const labelElement = document.getElementById( labelId );
			if ( labelElement && labelElement.textContent.trim() !== '' ) {
				return true;
			}
		}

		// Check if alt attribute exists AND has a value
		const hasAlt = node.hasAttribute( 'alt' ) && node.getAttribute( 'alt' ) !== null;

		// For input[type="image"], we need alt text
		if ( tagName === 'input' && node.getAttribute( 'type' ) === 'image' ) {
			return hasAlt;
		}

		// For images, we need the alt attribute to be defined
		if ( tagName === 'img' ) {
			// Check if image is inside a caption container
			if ( hasValidCaption( node ) ) {
				return true;
			}

			// Check if image is inside a link with context
			if ( hasLinkContext( node ) ) {
				return true;
			}

			// Check if image is inside a button with text content
			if ( hasButtonContext( node ) ) {
				return true;
			}

			return hasAlt;
		}

		return true;
	},
	options: {},
	metadata: {
		impact: 'critical',
		messages: {
			pass: 'Image has alt attribute',
			fail: 'Image does not have an alt attribute',
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
 * Check if the image is inside a link with context
 * @param {Element} node - The DOM element to check.
 * @return {boolean} A boolean indicating if the image is inside a link with context.
 */
const hasLinkContext = ( node ) => {
	// Check if inside an anchor tag with accessible text
	let parent = node.parentNode;
	while ( parent ) {
		if ( parent.tagName && parent.tagName.toLowerCase() === 'a' ) {
			// Check if the anchor has aria-label
			if ( parent.hasAttribute( 'aria-label' ) && parent.getAttribute( 'aria-label' ) !== '' ) {
				return true;
			}

			// Check if the anchor has title
			if ( parent.hasAttribute( 'title' ) && parent.getAttribute( 'title' ) !== '' ) {
				return true;
			}

			// Check if the anchor has meaningful text content
			const nodeContent = Array.from( parent.childNodes )
				.filter( ( child ) => child !== node && child.nodeType === 3 ) // Text nodes only
				.map( ( child ) => child.textContent ) // Don't trim here
				.join( '' );

			if ( nodeContent.length > 0 ) { // Check for any text content
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
			// Get all text nodes in the button
			const textNodes = Array.from( parent.childNodes )
				.filter( ( child ) => child !== node && child.nodeType === 3 );

			// Check if there's any non-whitespace text content
			const textContent = textNodes
				.map( ( child ) => child.textContent )
				.join( '' );

			if ( textContent.trim() !== '' ) {
				return true;
			}
			break;
		}
		parent = parent.parentNode;
	}

	return false;
};
