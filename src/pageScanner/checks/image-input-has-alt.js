/**
 * Returns true when the node is an <input type="image"> with a non-empty,
 * non-whitespace alt attribute. Returns false for all other element types so
 * non-image-input nodes never receive a spurious passing vote in the label
 * rule's any[] group.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if the node is an image input with a meaningful alt attribute.
 */
export default {
	id: 'image_input_has_alt',
	evaluate: ( node ) => {
		// Only applies to image inputs.
		if ( node.tagName.toLowerCase() !== 'input' || node.type !== 'image' ) {
			return false;
		}

		const alt = node.getAttribute( 'alt' );
		return alt !== null && alt.trim() !== '';
	},
};
