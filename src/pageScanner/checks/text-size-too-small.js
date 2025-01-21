/**
 * Axe core check against nodes to determine if the text size is too small.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if the text size is equal to or below the minimum size. False otherwise.
 */

import { fontSizeInPx } from '../helpers/helpers';

const SMALL_FONT_SIZE_THRESHOLD = 10;

export default {
	id: 'text_size_too_small',
	evaluate: ( node ) => {
		// If the node has no text content then it can't have text that's too small.
		if ( ! node.textContent.trim().length ) {
			return false;
		}

		// Check only if child nodes of the element that are text nodes. Nodes with no
		// text children are treated as if they are a text node themselves.
		const hasTextChild = Array.from( node.childNodes ).some( ( child ) => child.nodeType === Node.TEXT_NODE );
		if ( ! node.childNodes.length || hasTextChild ) {
			return fontSizeInPx( node ) <= SMALL_FONT_SIZE_THRESHOLD;
		}

		// Did not find any text that was too small.
		return false;
	},
};
