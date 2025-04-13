/**
 * Check if a link is improperly used (missing href or href="#", and not a button).
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if the link is valid or semantically correct, false otherwise.
 */

export default {
	id: 'link_has_valid_href_or_role',
	evaluate: ( node ) => {
		if ( node.nodeName.toLowerCase() !== 'a' ) {
			return true;
		}

		const href = node.getAttribute( 'href' );
		const role = node.getAttribute( 'role' ) || '';

		// Allow if it's a button role
		if ( role.toLowerCase() === 'button' ) {
			return true;
		}

		// Fail if href is missing, empty, whitespace-only, just '#', or contains javascript:
		if ( ! href ||
			href.trim() === '' ||
			href.trim() === '#' ||
			href.toLowerCase().startsWith( 'javascript:' )
		) {
			return false;
		}

		return true;
	},
};
