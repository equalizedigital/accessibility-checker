/**
 * Check to detect if an anchor tag links to a non-HTML document type.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if the link is not a problematic file type, false if it should be flagged.
 */

export const nonHtmlExtensions = [
	'rtf', 'wpd', 'ods', 'odt', 'odp', 'sxw', 'sxc', 'sxd', 'sxi', 'pages', 'key',
];

export default {
	id: 'link_points_to_html',
	evaluate: ( node ) => {
		if ( node.nodeName.toLowerCase() !== 'a' ) {
			return true;
		}

		const href = node.getAttribute( 'href' ) || '';

		try {
			const url = new URL( href, document.baseURI );
			const pathParts = url.pathname.split( '.' );
			const extension = pathParts.length > 1 ? pathParts.pop().toLowerCase() : '';
			if ( nonHtmlExtensions.includes( extension ) ) {
				// ... rest of the code
			}
		} catch ( error ) {
			// ... error handling
		}
			if ( nonHtmlExtensions.includes( extension ) ) {
				return false; // Fail check
			}
		} catch {
			// Invalid URL — ignore this link
			return true;
		}

		return true; // Pass
	},
};
