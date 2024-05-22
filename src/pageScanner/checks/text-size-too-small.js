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
		// fails if the font size is less than or equal to 10px.
		return fontSizeInPx( node ) <= SMALL_FONT_SIZE_THRESHOLD;
	},
};
