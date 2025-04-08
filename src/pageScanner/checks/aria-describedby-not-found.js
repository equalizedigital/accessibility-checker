/**
 * Check if the aria-describedby attribute references an existing element.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if all references exist, false otherwise.
 */

export default {
	id: 'aria_describedby_not_found',
	evaluate: ( node ) => {
		const ids = ( node.getAttribute( 'aria-describedby' ) || '' ).split( /\s+/ );
		return ids.every( ( id ) => document.getElementById( id ) !== null );
	},
};
