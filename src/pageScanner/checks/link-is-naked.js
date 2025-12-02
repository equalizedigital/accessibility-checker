export default {
	id: 'link-is-naked',
	evaluate( node ) {
		if ( ! node || typeof node.getAttribute !== 'function' ) {
			return undefined; // Not a valid element
		}
		const href = node.getAttribute( 'href' );
		const textContent = ( node.textContent || '' ).trim();

		if ( ! href ) {
			return undefined; // No href attribute, so not applicable
		}

		// Helper: normalize phone numbers (remove non-digits, keep leading +)
		const normalizePhone = ( str ) => {
			if ( ! str ) {
				return '';
			}
			const trimmed = str.trim();
			const hasPlus = trimmed.startsWith( '+' );
			const digitsOnly = trimmed.replace( /[^0-9]/g, '' );
			return ( hasPlus ? '+' : '' ) + digitsOnly;
		};

		// Handle mailto: links — flag when text equals the email address
		if ( href.toLowerCase().startsWith( 'mailto:' ) ) {
			const email = href.substring( 7 ).trim(); // after 'mailto:'
			// Compare case-insensitively, since emails are often shown lowercase
			if ( textContent.toLowerCase() === email.toLowerCase() ) {
				return true;
			}
		}

		// Handle tel: links — flag when text equals the phone number (normalized)
		if ( href.toLowerCase().startsWith( 'tel:' ) ) {
			const telRaw = href.substring( 4 ).trim(); // after 'tel:'
			const telNormalized = normalizePhone( telRaw );
			const textNormalized = normalizePhone( textContent );
			if ( textNormalized && telNormalized && textNormalized === telNormalized ) {
				return true;
			}
		}

		// Check if the trimmed text content is the same as the href
		return textContent === href;
	},
	metadata: {
		description: "Checks if a link's text is the same as its href attribute, or if mailto/tel links display the raw email/phone number as link text.",
		help: 'Link text should be descriptive and not simply the URL, email address, or phone number.',
	},
};
