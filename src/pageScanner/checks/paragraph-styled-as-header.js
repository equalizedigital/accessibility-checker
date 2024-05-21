/**
 * Check for elements with text content that is styled like a header.
 *
 * Axe-core has a built-in `p-as-heading` rule that checks for paragraphs
 * that are styled like headings. That rule is less robust than this check,
 * as it only checks for paragraphs with bold or italic text and gives back
 * 'incomplete' or uncertain results for some of the test data that we want
 * to flag for further checks.
 *
 * This check in this file takes into account the font size, length of the
 * text, and considers paragraphs with large font size as headers as well.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if the node is styled like a header, false otherwise. Paragraphs with only bold or italic,
 *                   are shorter than 50 characters, or are short with large font size are considered headers.
 */

import { fontSizeInPx } from '../helpers/helpers.js';

export default {
	id: 'paragraph_styled_as_header',
	evaluate: ( node ) => {
		const pixelSize = fontSizeInPx( node );

		// long paragraphs or with size under 16px are unlikely to be headers.
		if ( node.textContent.trim().length > 50 || pixelSize <= 16 ) {
			return false;
		}

		// paragraphs that are 20px or more are probably headers.
		if ( pixelSize >= 20 ) {
			return true;
		}

		const style = window.getComputedStyle( node );

		const fontWeight = style.getPropertyValue( 'font-weight' );
		const isBold = [ 'bold', 'bolder', '700', '800', '900' ].includes( fontWeight );

		const fontStyle = style.getPropertyValue( 'font-style' );
		const isItalic = [ 'italic', 'oblique' ].includes( fontStyle );

		const hasBoldOrItalicTag = node.querySelector( 'b, strong, i, em' ) !== null;

		if ( isBold || isItalic || hasBoldOrItalicTag ) {
			return true;
		}

		// didn't find anything indicating this is a possible header.
		return false;
	},
};
