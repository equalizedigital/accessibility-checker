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

		// Check if the node has any direct text nodes as children. For a node with no
		// children, or with TEXT_NODE children, evaluate the nodes font size. This
		// handles both leaf nodes and container elements with mixed content.
		const hasTextChild = Array.from( node.childNodes ).some( ( child ) => child.nodeType === Node.TEXT_NODE );
		if ( ! node.childNodes.length || hasTextChild ) {
			return fontSizeInPx( node ) <= SMALL_FONT_SIZE_THRESHOLD;
		}

		// No text nodes were found in direct children, and this is not a leaf node,
		// so we can safely ignore font size checks.
		return false;
	},
};
