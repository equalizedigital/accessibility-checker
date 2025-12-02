export default {
	id: 'link-is-naked',
	evaluate( node ) {
		if ( ! node || typeof node.getAttribute !== 'function' ) {
			return undefined;
		}

		const href = node.getAttribute( 'href' );
		const textContent = ( node.textContent || '' ).trim();

		if ( ! href ) {
			return undefined;
		}

		const PROTOCOL_OR_WWW = /^(https?:\/\/|www\.)/i;
		const URL_PATTERN = /https?:\/\/[^\s]+|www\.[^\s]+/i;

		const normalizePhone = ( str ) => {
			if ( ! str ) {
				return '';
			}
			const hasPlus = str.trim().startsWith( '+' );
			const digitsOnly = str.replace( /[^0-9]/g, '' );
			return ( hasPlus ? '+' : '' ) + digitsOnly;
		};

		const normalizeUrl = ( url ) => {
			if ( ! url ) {
				return '';
			}
			return url.toLowerCase().trim()
				.replace( /^https?:\/\//i, '' ) // Remove protocol
				.replace( /^www\./i, '' ) // Remove www prefix
				.replace( /[?#].*$/, '' ) // Remove query and hash
				.replace( /\/$/, '' ); // Remove trailing slash
		};

		// Check mailto: links
		if ( href.toLowerCase().startsWith( 'mailto:' ) ) {
			const email = href.substring( 7 ).trim();
			return textContent.toLowerCase() === email.toLowerCase();
		}

		// Check tel: links
		if ( href.toLowerCase().startsWith( 'tel:' ) ) {
			const telNormalized = normalizePhone( href.substring( 4 ) );
			const textNormalized = normalizePhone( textContent );
			return Boolean( telNormalized && textNormalized && telNormalized === textNormalized );
		}

		// Check if text contains a full URL pattern (http://, https://, or www.)
		if ( URL_PATTERN.test( textContent ) ) {
			return true;
		}

		// Flag exact matches (including relative paths) before normalization alters them.
		if ( textContent === href ) {
			return true;
		}

		// Close-match logic (absolute URLs differing only by protocol/www/query/hash)
		const normalizedHref = normalizeUrl( href );
		const normalizedText = normalizeUrl( textContent );

		if ( normalizedHref && normalizedText && normalizedHref === normalizedText ) {
			// Only flag if user typed a full URL (has protocol or www)
			if ( PROTOCOL_OR_WWW.test( textContent ) ) {
				return true;
			}
			// If no protocol/www (e.g. bare domain or relative path), fall through (do not return false here).
		}

		// Default: not a naked link per this check
		return false;
	},
	metadata: {
		description: "Checks if a link's text is the same as its href attribute, or if mailto/tel links display the raw email/phone number as link text.",
		help: 'Link text should be descriptive and not simply the URL, email address, or phone number.',
	},
};
