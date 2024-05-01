/**
 * Axe core check for elements with underlines.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if the node has an underline in computed styles, false otherwise.
 */

export default {
	id: 'element_has_computed_underline',
	evaluate: ( node ) => {
		const style = window.getComputedStyle( node );
		return style.textDecorationLine.includes( 'underline' );
	},
};
