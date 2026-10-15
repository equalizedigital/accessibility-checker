/**
 * Check whether a link sits directly next to another link (only whitespace text between them)
 * that points to the same destination. This commonly happens by accident when a thumbnail
 * image and its title are wrapped in separate links instead of being combined.
 */

const isWhitespaceTextNode = ( node ) => node.nodeType === Node.TEXT_NODE && node.textContent.trim() === '';

/**
 * Walk past whitespace-only text nodes to find the next element sibling in a direction.
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
		if ( isWhitespaceTextNode( sibling ) ) {
			sibling = direction === 'next' ? sibling.nextSibling : sibling.previousSibling;
			continue;
		}

		return sibling.nodeType === Node.ELEMENT_NODE ? sibling : null;
	}

	return null;
};

const hasUsableHref = ( element ) => {
	const href = element.getAttribute( 'href' );
	return !! href && href.trim() !== '' && href.trim() !== '#';
};

const isMatchingLink = ( candidate, node ) => {
	if ( ! candidate || candidate.tagName?.toLowerCase() !== 'a' || ! hasUsableHref( candidate ) ) {
		return false;
	}

	return candidate.href === node.href;
};

export default {
	id: 'link_has_adjacent_duplicate',
	evaluate: ( node ) => {
		if ( node.tagName.toLowerCase() !== 'a' || ! hasUsableHref( node ) ) {
			return false;
		}

		const nextLink = getAdjacentLinkElement( node, 'next' );
		const previousLink = getAdjacentLinkElement( node, 'previous' );

		return isMatchingLink( nextLink, node ) || isMatchingLink( previousLink, node );
	},
};
