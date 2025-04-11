/**
 * Check if the provided node is an <img> tag and validate its longdesc attribute.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if the longdesc attribute is valid or not applicable, false otherwise.
 */

const imageExtensions = [
	'apng', 'bmp', 'gif', 'ico', 'cur', 'jpg', 'jpeg', 'jfif',
	'pjpeg', 'pjp', 'png', 'svg', 'tif', 'tiff', 'webp',
];

export default {
	id: 'longdesc_valid',
	evaluate: ( node ) => {
		if ( node.nodeName.toLowerCase() !== 'img' ) {
			return true;
		}

		const longdesc = node.getAttribute( 'longdesc' );
		if ( longdesc === null ) {
			return true;
		}

		if ( longdesc.trim() === '' ) {
			return false;
		}

		// Reject malformed protocols like "ht!tp://"
		if ( longdesc.includes( ':' ) ) {
			// This regex matches valid protocols per RFC 3986: e.g., "http://", "https://", "ftp://"
			const protocolPattern = /^[a-zA-Z][a-zA-Z\d+\-.]*:\/\//;
			if ( ! protocolPattern.test( longdesc ) ) {
				return false;
			}
		}

		let url;
		try {
			url = new URL( longdesc, document.baseURI );
		} catch {
			return false; // Malformed URL (even relative)
		}

		const pathname = url.pathname;
		// Handle path ending with slash
		const filename = pathname.endsWith( '/' ) ? '' : pathname.split( '/' ).pop();

		// Only check extension if there's a filename
		if ( ! filename ) {
			return false;
		}

		// Extract extension, accounting for filenames with multiple dots
		// and query parameters or fragments
		const extMatch = filename.match( /\.([^.?#]+)(?:\?.*)?(?:#.*)?$/ );
		const ext = extMatch ? extMatch[ 1 ].toLowerCase() : '';

		if ( imageExtensions.includes( ext ) ) {
			return false;
		}

		return true;
	},
};
