/**
 * Check if the aria-describedby attribute references an existing element.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if all references exist, false otherwise.
 */
import { checkAriaReferences } from '../utils/aria-utils';

export default {
	id: 'aria_describedby_not_found',
	evaluate: ( node ) => {
		return checkAriaReferences( node, 'aria-describedby' );
	},
};
