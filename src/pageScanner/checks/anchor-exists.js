/**
 * Check if the anchor's target exists in the DOM.
 *
 * @param {Node} node The anchor node to evaluate.
 * @return {boolean} True if the target element exists, false otherwise.
 */

export default {
	id: 'anchor_exists',
	evaluate: ( node ) => {
		return document.querySelector( node.getAttribute( 'href' ) ) !== null;
	},
};
