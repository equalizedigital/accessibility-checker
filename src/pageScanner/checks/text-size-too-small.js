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

		// If the node has no child nodes, then consider it as a text node itself and check it.
		if ( ! node.childNodes.length ) {
			return fontSizeInPx( node ) <= SMALL_FONT_SIZE_THRESHOLD;
		}

		// Check onlychild nodes of the element that are text nodes.
		let isTextTooSmall = false;
		node.childNodes.forEach( ( child ) => {
			if ( child.nodeType === Node.TEXT_NODE ) {
				if ( fontSizeInPx( node ) <= SMALL_FONT_SIZE_THRESHOLD ) {
					isTextTooSmall = true;
				}
			}
		} );
		return isTextTooSmall;
	},
};
