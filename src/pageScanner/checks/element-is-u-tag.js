/**
 * Axe core check against nodes to determine if they are <u> tags.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if the node is a <u> tag, false otherwise.
 */

export default {
	id: 'element_is_u_tag',
	evaluate( node ) {
		return node.tagName && node.tagName.toLowerCase() === 'u';
	},
};
