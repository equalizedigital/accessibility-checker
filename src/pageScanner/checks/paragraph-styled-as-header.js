/**
 * Axe core check for elements with text content over x characters.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if the node has text content longer than x characters, false otherwise.
 */

export default {
	id: 'paragraph_styled_as_header',
	evaluate: ( node ) => {
		const text = node.textContent.trim();
		const pixelSize = parseFloat( window.getComputedStyle( node ).fontSize );

		// long paragraphs or with size under 16px are unlikely to be headers.
		if ( text.length > 50 || pixelSize <= 16 ) {
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
