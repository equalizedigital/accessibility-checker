/**
 * Axe core check against nodes to determine if link opens in new tab and if so has appropriate aria-label or aria-labelledby.
 */

import { __ } from '@wordpress/i18n';

const allowedPhrases = [
	__( 'new window', 'accessibility-checker' ),
	__( 'new tab', 'accessibility-checker' ),
	__( 'new document', 'accessibility-checker' ),
];

/**
 * Check for links that open in a new tab without informing the user.
 *
 * @param {HTMLElement} node The node to evaluate.
 *                           return {boolean} False if the node is a link that opens in a new tab without informing the user, true if informed.
 */
export default {
	id: 'link_target_blank_without_informing',
	evaluate: ( node ) => {
		// Bail early if not a link or not target blank.
		if ( node.tagName.toLowerCase() !== 'a' || node.getAttribute( 'target' ) !== '_blank' ) {
			return false;
		}

		// Check plain text.
		if ( checkTextHasInfoCallout( node.textContent ) ) {
			return false;
		}

		// Check aria-label.
		if ( node.hasAttribute( 'aria-label' ) && checkTextHasInfoCallout( node.getAttribute( 'aria-label' ) ) ) {
			return false;
		}

		// Check aria-labelledby.
		if ( node.hasAttribute( 'aria-labelledby' ) ) {
			const labelElement = document.getElementById( node.getAttribute( 'aria-labelledby' ) );
			if ( labelElement && checkTextHasInfoCallout( labelElement.textContent ) ) {
				return false;
			}
		}

		// Check image alt text.
		const images = node.querySelectorAll( 'img' );
		for ( const image of images ) {
			if ( checkTextHasInfoCallout( image.getAttribute( 'alt' ) ) ) {
				return false;
			}
		}

		// Nothing so far has indicated that this is a new window/tab opener so this is a fail.
		return true;
	},
};

/**
 * Checks that some text contains a phrase that indicates a new window or tab opener.
 *
 * @param {string} text The text to check.
 * @return {boolean} True if the text contains a new window/tab phrase, false otherwise.
 */
const checkTextHasInfoCallout = ( text ) => {
	if ( ! text ) {
		return false;
	}
	return allowedPhrases.some( ( phrase ) => text.toLowerCase().includes( phrase ) );
};
