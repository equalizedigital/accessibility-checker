/**
 * Check that a paragraph is NOT empty.
 *
 * @param {Node} node The paragraph element to evaluate.
 * @return {boolean} Returns true if the paragraph has content, false if empty.
 */

export default {
	id: 'paragraph_not_empty',
	evaluate: ( node ) => {
		if ( 'p' !== node.tagName.toLowerCase() ) {
			return true;
		}

		// if there are child nodes then it passes.
		if ( node.childNodes.length ) {
			return true;
		}

		if ( node.textContent.trim() !== '' ) {
			return true;
		}

		return false;
	},
};
