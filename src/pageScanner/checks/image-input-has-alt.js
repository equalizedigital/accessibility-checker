/**
 * Axe core check against nodes to determine if they are <u> tags.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if the node is a <u> tag, false otherwise.
 */

export default {
	id: 'image_input_has_alt',
	evaluate: ( node ) => {
		// Not an image input, skip.
		if ( node.tagName.toLowerCase() === 'input' && node.type !== 'image' ) {
			return false;
		}

		// Non empty alt attribute.
		if ( node.getAttribute( 'alt' )?.trim() !== '' ) {
			return true;
		}

		return false;
	},
};
