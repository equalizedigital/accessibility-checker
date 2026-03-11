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

		// Parse roles once for efficiency (DRY principle)
		const roles = role.toLowerCase().split( /\s+/ );

		// Allow roles of button or tab
		if ( roles.some( ( r ) => [ 'button', 'tab' ].includes( r ) ) ) {
			return true;
		}

		// Allow role="menuitem" with aria-expanded (for expandable menu items)
		// See: https://wordpress.org/support/topic/improper-use-of-link-5/
		const hasAriaExpanded = node.hasAttribute( 'aria-expanded' );
		if ( roles.includes( 'menuitem' ) && hasAriaExpanded ) {
			return true;
		}

		const trimmedHref = href ? href.trim() : '';
		const normalizedHref = trimmedHref.toLowerCase();

		// Fail if href is missing, just '#', or contains invalid protocols
		if ( ! href ||
			trimmedHref === '#' ||
			normalizedHref.startsWith( 'javascript:' ) ||
			normalizedHref.startsWith( 'data:' ) ||
			normalizedHref.startsWith( 'file:' )
		) {
			return false;
		}

		// Optionally validate URL format if it's an absolute URL
		if ( trimmedHref.includes( '://' ) ) {
			try {
				new URL( trimmedHref );
			} catch ( e ) {
				return false; // Invalid URL formats
			}
		}

		return true;
	},
};
