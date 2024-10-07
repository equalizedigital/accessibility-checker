/**
 * Axe core check against nodes to determine if link opens in new tab and if so has appropriate aria-label or aria-labelledby.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if the node is an a tag with valid new tab callout, false otherwise.
 */

import { __ } from '@wordpress/i18n';

const allowedPhrases = [ __( 'new window', 'accessibility-checker' ), __( 'new tab', 'accessibility-checker' ) ];

export default {
	id: 'link_target_blank',
	evaluate: ( node ) => {
		// Make sure it's an anchor tag with target="_blank".
		if ( node.tagName.toLowerCase() !== 'a' || node.getAttribute( 'target' ) !== '_blank' ) {
			return false;
		}

		// Check plain text.
		if ( checkTextHasInfoCallout( node.textContent ) ) {
			return true;
		}

		// Check aria-label.
		if ( node.hasAttribute( 'aria-label' ) ) {
			return checkTextHasInfoCallout( node.getAttribute( 'aria-label' ) );
		}

		// Check aria-labelledby.
		if ( node.hasAttribute( 'aria-labelledby' ) ) {
			const labels = node.getAttribute( 'aria-labelledby' ).split( ' ' );
			for ( const label of labels ) {
				const element = document.getElementById( label );
				if ( element && checkTextHasInfoCallout( element.textContent ) ) {
					return true;
				}
			}
		}

		// Check image alt text.
		const images = node.querySelectorAll( 'img' );
		for ( const image of images ) {
			if ( checkTextHasInfoCallout( image.getAttribute( 'alt' ) ) ) {
				return true;
			}
		}

		// Nothing so far has indicated that this is a new window/tab opener so this is a fail.
		return false;
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
	text = text.toLowerCase();
	return allowedPhrases.some( ( phrase ) => text.includes( phrase ) );
};
