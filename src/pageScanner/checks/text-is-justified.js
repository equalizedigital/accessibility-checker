/**
 * Axe core check for elements that are aligned to justify.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if the node is justified, false otherwise.
 */

export default {
	id: 'text_is_justified',
	evaluate: ( node ) => {
		const style = window.getComputedStyle( node );
		return style.textAlign.toLowerCase() === 'justify';
	},
};
