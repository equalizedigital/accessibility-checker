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

		// Allow named anchors used as jump targets: no href, has id/name, no visible content,
		// and not keyboard-focusable.
		// Per the HTML spec, <a> without href has role="generic" and is not keyboard focusable,
		// so it does not create a false link for AT or keyboard users.
		// Use hasAttribute instead of !href to exclude href="" which is keyboard focusable.
		// Check children.length to exclude anchors wrapping images or other elements.
		const id = node.getAttribute( 'id' ) || '';
		const name = node.getAttribute( 'name' ) || '';
		const hasAnchorTargetName = id.trim() !== '' || name.trim() !== '';

		const tabindex = node.getAttribute( 'tabindex' );
		const parsedTabindex = null === tabindex ? null : Number.parseInt( tabindex, 10 );
		const isKeyboardFocusable =
			null !== tabindex && ( Number.isNaN( parsedTabindex ) || parsedTabindex >= 0 );

		if ( ! node.hasAttribute( 'href' ) && hasAnchorTargetName && ! isKeyboardFocusable && node.children.length === 0 && node.textContent.trim() === '' ) {
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
