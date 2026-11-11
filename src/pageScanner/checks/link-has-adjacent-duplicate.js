/**
 * Check whether a link sits directly next to another link (only whitespace text between them)
 * that points to the same destination. This commonly happens by accident when a thumbnail
 * image and its title are wrapped in separate links instead of being combined.
 */

const isWhitespaceTextNode = ( node ) => node.nodeType === Node.TEXT_NODE && node.textContent.trim() === '';

// Comments create no rendered or accessibility tree content, so they do not separate two links.
const isCommentNode = ( node ) => node.nodeType === Node.COMMENT_NODE;

/**
 * Walk past whitespace-only text nodes and comments to find the next element sibling in a direction.
 * Returns null if a non-whitespace text node or non-element node is encountered first,
 * since that means the links are not directly adjacent.
 *
 * @param {HTMLElement} node      The node to start from.
 * @param {string}      direction Either 'next' or 'previous'.
 * @return {HTMLElement|null} The adjacent element, or null if none is directly adjacent.
 */
const getAdjacentLinkElement = ( node, direction ) => {
	let sibling = direction === 'next' ? node.nextSibling : node.previousSibling;

	while ( sibling ) {
		if ( isWhitespaceTextNode( sibling ) || isCommentNode( sibling ) ) {
			sibling = direction === 'next' ? sibling.nextSibling : sibling.previousSibling;
			continue;
		}

		return sibling.nodeType === Node.ELEMENT_NODE ? sibling : null;
	}

	return null;
};

/**
 * Whether the link has a real destination. Empty, `#` and `javascript:` hrefs are skipped,
 * because they do not point anywhere and adjacent ones usually trigger different actions.
 *
 * @param {HTMLElement} element The link to check.
 * @return {boolean} True if the href is a real destination.
 */
const hasUsableHref = ( element ) => {
	const href = ( element.getAttribute( 'href' ) || '' ).trim();
	return href !== '' && href !== '#' && ! /^javascript:/i.test( href );
};

/**
 * Whether the link has been removed from assistive technology and keyboard navigation,
 * which is the fix the rule recommends: aria-hidden="true" together with tabindex="-1".
 *
 * @param {HTMLElement} element The link to check.
 * @return {boolean} True if the link is aria-hidden and not in the tab order.
 */
const isPresentationalLink = ( element ) =>
	element.closest( '[aria-hidden="true"]' ) !== null && element.getAttribute( 'tabindex' ) === '-1';

const isMatchingLink = ( candidate, node ) => {
	if ( ! candidate || candidate.tagName?.toLowerCase() !== 'a' || ! hasUsableHref( candidate ) || isPresentationalLink( candidate ) ) {
		return false;
	}

	return candidate.href === node.href;
};

export default {
	id: 'link_has_adjacent_duplicate',
	evaluate: ( node ) => {
		if ( node.tagName.toLowerCase() !== 'a' || ! hasUsableHref( node ) || isPresentationalLink( node ) ) {
			return false;
		}

		const nextLink = getAdjacentLinkElement( node, 'next' );
		const previousLink = getAdjacentLinkElement( node, 'previous' );

		return isMatchingLink( nextLink, node ) || isMatchingLink( previousLink, node );
	},
};
