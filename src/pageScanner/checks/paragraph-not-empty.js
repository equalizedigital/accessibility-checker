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

		// Pass if there are child nodes and the first child is not a text node (notType of 3).
		if ( node.childNodes.length && node.childNodes[ 0 ].nodeType !== 3 ) {
			return true;
		}

		// If there is text content then it passes.
		return node.textContent.trim() !== '';
	},
};
