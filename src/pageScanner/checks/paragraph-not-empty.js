/**
 * Check if a paragraph is empty.
 *
 * @param {Node} node The paragraph element to evaluate.
 * @return {boolean} Returns true if the paragraph is empty, false otherwise.
 */

export default {
	id: 'paragraph_not_empty',
	evaluate: ( node ) => {
		// if not a paragraph this immediately passes.
		if ( 'p' !== node.tagName.toLowerCase() ) {
			return true;
		}

		// if the paragraph has text content it is a pass, otherwise it is a fail.
		return !! node.textContent.trim();
	},
};
