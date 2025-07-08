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

		// Allow roles of button or tab
		if (
			role
				.toLowerCase()
				.split( /\s+/ )
				.some( ( r ) => [ 'button', 'tab' ].includes( r ) )
		) {
			return true;
		}

		const trimmedHref = href ? href.trim() : '';

		// Fail if href is missing, just '#', or contains invalid protocols
		if ( ! href ||
			trimmedHref === '#' ||
			href.toLowerCase().startsWith( 'javascript:' ) ||
			href.toLowerCase().startsWith( 'data:' ) ||
			href.toLowerCase().startsWith( 'file:' )
		) {
			return false;
		}

		// Optionally validate URL format if it's an absolute URL
		if ( href.includes( '://' ) ) {
			try {
				new URL( href );
			} catch ( e ) {
				return false; // Invalid URL formats
			}
		}

		return true;
	},
};
