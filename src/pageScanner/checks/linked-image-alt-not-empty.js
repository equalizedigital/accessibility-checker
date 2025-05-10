/**
 * Check that linked images have non-empty alt text.
 *
 * This check evaluates whether images inside anchor tags have meaningful alternative text.
 * It fails if an image has an empty or whitespace-only alt attribute, unless:
 * - The link itself has sufficient descriptive text
 * - The image is hidden or decorative (role="presentation" or aria-hidden="true")
 * - The anchor has aria-label or title attributes
 */

import { getVisibleImages, hasAccessibleText } from '../helpers/linkedImageUtils.js';

export default {
	id: 'linked_image_alt_not_empty',
	evaluate: ( node ) => {
		if ( node.nodeName.toLowerCase() !== 'a' ) {
			return true;
		}

		if ( hasAccessibleText( node ) ) {
			return true;
		}

		const images = getVisibleImages( node );
		if ( images.length === 0 ) {
			return true;
		}

		// Check each visible image for non-empty alt text
		return images.every( ( img ) => {
			const alt = img.getAttribute( 'alt' );
			const role = img.getAttribute( 'role' );
			const ariaHidden = img.getAttribute( 'aria-hidden' );

			if ( role === 'presentation' || ariaHidden === 'true' ) {
				return true;
			}

			// Check for null, empty string, or whitespace-only alt text
			return alt !== null && alt.trim() !== '';
		} );
	},
};
