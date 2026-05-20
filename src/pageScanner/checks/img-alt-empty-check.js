/**
 * Check for images with empty alt attributes.
 * Based on WCAG 1.1.1: Non-text Content (Level A)
 */

export default {
	id: 'img_alt_empty_check',
	evaluate( node ) {
		// Check if alt attribute is empty string (not missing, but explicitly empty)
		const hasEmptyAlt = node.hasAttribute( 'alt' ) && node.getAttribute( 'alt' ) === '';

		// Skip if aria-hidden is true
		if ( hasEmptyAlt && node.getAttribute( 'aria-hidden' ) === 'true' ) {
			return true;
		}

		// Skip if inside figure with figcaption
		if ( hasEmptyAlt && isInsideValidCaption( node ) ) {
			return true;
		}

		// Skip if it's a plugin-specific image that should be ignored
		if ( hasEmptyAlt && shouldIgnorePluginImage( node ) ) {
			return true;
		}

		// Skip 1x1 tracking pixels (any src or base64)
		if ( hasEmptyAlt && isTrackingPixel( node ) ) {
			return true;
		}

		// Return false if alt is empty and none of the exceptions apply
		return ! hasEmptyAlt;
	},
};

/**
 * Check if image is inside a valid caption element
 * @param {HTMLElement} node - The node to check
 * @return {boolean} True if inside valid caption
 */
function isInsideValidCaption( node ) {
	// Check if inside figure with figcaption
	const figure = node.closest( 'figure' );
	if ( figure && figure.querySelector( 'figcaption' ) ) {
		return true;
	}

	// Check if inside WordPress caption div
	const wpCaption = node.closest( 'div.wp-caption' );
	if ( wpCaption && wpCaption.querySelector( '.wp-caption-text' ) ) {
		return true;
	}

	// Check if inside anchor with valid text alternative
	const anchor = node.closest( 'a' );
	if ( anchor ) {
		// Check if anchor has aria-label, title, or non-empty text
		if ( anchor.hasAttribute( 'aria-label' ) && anchor.getAttribute( 'aria-label' ).trim() !== '' ) {
			return true;
		}
		if ( anchor.hasAttribute( 'title' ) && anchor.getAttribute( 'title' ).trim() !== '' ) {
			return true;
		}
		// Check if anchor has text content that's not just the image
		const anchorText = anchor.textContent.trim();
		if ( anchorText !== '' && anchorText.length > 5 ) {
			return true;
		}
	}

	return false;
}

/**
 * Check if image is a 1x1 tracking pixel.
 * Checks both HTML attributes and computed natural dimensions.
 * @param {HTMLElement} node - The node to check
 * @return {boolean} True if image is a 1x1 tracking pixel
 */
function isTrackingPixel( node ) {
	// Check HTML width/height attributes
	const widthAttr = node.getAttribute( 'width' );
	const heightAttr = node.getAttribute( 'height' );
	if ( widthAttr === '1' && heightAttr === '1' ) {
		return true;
	}

	// Check computed natural dimensions (e.g. for base64 or loaded images without explicit attributes)
	if (
		typeof node.naturalWidth === 'number' &&
		typeof node.naturalHeight === 'number' &&
		node.naturalWidth === 1 &&
		node.naturalHeight === 1
	) {
		return true;
	}

	return false;
}

/**
 * Check if image should be ignored due to plugin-specific cases
 * @param {HTMLElement} node - The node to check
 * @return {boolean} True if image should be ignored
 */
function shouldIgnorePluginImage( node ) {
	// WordPress smileys
	if ( node.classList.contains( 'wp-smiley' ) ) {
		return true;
	}

	// Advanced WP columns spacer pixel
	const src = node.getAttribute( 'src' ) || '';
	if ( src.includes( 'advanced-wp-columns/assets/js/plugins/views/img/1x1-pixel.png' ) ) {
		return true;
	}

	// Google ad code
	if ( src.includes( 'googleads.g.doubleclick.net/pagead/viewthroughconversion' ) ) {
		return true;
	}

	return false;
}
