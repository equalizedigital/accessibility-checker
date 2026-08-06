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
		const roleTokens = role.toLowerCase().split( /\s+/ );

		const tabindex = node.getAttribute( 'tabindex' );
		const parsedTabindex = null === tabindex ? null : Number.parseInt( tabindex, 10 );
		// A tabindex whose value is not a valid integer is ignored per the HTML spec, so it
		// confers no focusability at all.
		const hasValidTabindex = null !== tabindex && ! Number.isNaN( parsedTabindex );
		const isKeyboardFocusable = hasValidTabindex && parsedTabindex >= 0;

		// Reachable with the Tab key: an href (including href="") or a non-negative tabindex.
		const isKeyboardOperable = node.hasAttribute( 'href' ) || isKeyboardFocusable;

		// Focusable at all. Unlike the above this includes tabindex="-1", which is focusable
		// programmatically and by click even though it is outside the tab order.
		const isFocusable = node.hasAttribute( 'href' ) || hasValidTabindex;

		// Allow roles of button, tab or slider. None of these is exposed as a link, so the
		// anchor is not being used improperly regardless of how it is focused. Whether a
		// non-focusable widget role is itself a defect is a separate question from this rule.
		if ( roleTokens.some( ( r ) => [ 'button', 'tab', 'slider' ].includes( r ) ) ) {
			return true;
		}

		// Allow role="menuitem" with aria-expanded (for expandable menu items)
		// See: https://wordpress.org/support/topic/improper-use-of-link-5/
		const hasAriaExpanded = node.hasAttribute( 'aria-expanded' );
		if ( roleTokens.includes( 'menuitem' ) && hasAriaExpanded ) {
			return true;
		}

		// Allow role="none"/"presentation", which removes the element from the accessibility
		// tree so it is never exposed as a link. Unlike the named-anchor exemption below,
		// content is permitted here: the role applies regardless of what the anchor wraps.
		// Per the presentational roles conflict resolution the role is ignored on an element
		// that is focusable or carries global ARIA states/properties, so neither may apply.
		if ( ! isFocusable && ( roleTokens.includes( 'none' ) || roleTokens.includes( 'presentation' ) ) ) {
			// Approximated as any aria-* attribute, which errs toward reporting. aria-hidden is
			// excluded because it removes the element from the tree outright, making the role moot.
			const hasGlobalAria = Array.from( node.attributes ).some(
				( attr ) => attr.name.startsWith( 'aria-' ) && attr.name !== 'aria-hidden'
			);

			if ( ! hasGlobalAria ) {
				return true;
			}
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

		if ( ! isKeyboardOperable && hasAnchorTargetName && node.children.length === 0 && node.textContent.trim() === '' ) {
			return true;
		}

		const trimmedHref = href ? href.trim() : '';
		const normalizedHref = trimmedHref.toLowerCase();

		// Fail if href is missing, just '#', or contains invalid protocols
		if ( ! trimmedHref ||
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
