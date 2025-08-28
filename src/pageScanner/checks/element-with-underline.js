/**
 * Axe core check for elements with underlines.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if the node has an underline in computed styles, false otherwise.
 */

export default {
	id: 'element_with_underline',
	evaluate( node ) {
		const computedStyle = window.getComputedStyle( node );
		const textDecoration = computedStyle.getPropertyValue( 'text-decoration' );
		const textDecorationLine = computedStyle.getPropertyValue( 'text-decoration-line' );

		// Check if element has underline decoration
		const hasUnderline = (
			textDecoration.includes( 'underline' ) ||
			textDecorationLine.includes( 'underline' )
		);

		// Return true if element has underline (this will trigger the rule failure)
		return hasUnderline;
	},
};
