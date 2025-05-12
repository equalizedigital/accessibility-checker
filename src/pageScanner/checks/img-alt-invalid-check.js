/**
 * Check if image alt text is invalid.
 * Ported from PHP function edac_rule_img_alt_invalid
 */

export default {
	id: 'img_alt_invalid_check',
	evaluate( node ) {
		// If no alt attribute exists, this check doesn't apply
		if ( ! node.hasAttribute( 'alt' ) ) {
			return true;
		}

		const altRaw = node.getAttribute( 'alt' );

		// Skip empty alt text (decorative images).
		if ( altRaw === '' ) {
			return true;
		}

		// Fail if alt contains just strippable whitespace.
		if ( altRaw.trim() === '' ) {
			return false;
		}

		// Trim the alt, normalize to lower case and replace multiple consecutive spaces with a single space.
		const altTrimmed = altRaw.toLowerCase().trim().replace( /\s+/g, ' ' );

		// Check if string begins with problematic phrases
		const startsWithKeywords = [
			'graphic of',
			'bullet',
			'image of',
		];
		for ( const keyword of startsWithKeywords ) {
			if ( altTrimmed.startsWith( keyword ) ) {
				return false;
			}
		}

		// Check if string ends with problematic words
		const endsWithKeywords = [
			'image',
			'graphic',
		];
		for ( const keyword of endsWithKeywords ) {
			if ( altTrimmed.endsWith( keyword ) ) {
				return false;
			}
		}

		// Check for image extensions
		const imageExtensions = [
			'.apng', '.bmp', '.gif', '.ico', '.cur', '.jpg', '.jpeg',
			'.jfif', '.pjpeg', '.pjp', '.png', '.svg', '.tif', '.tiff', '.webp',
		];
		for ( const extension of imageExtensions ) {
			if ( altTrimmed.includes( extension ) ) {
				return false;
			}
		}

		// Check for exact matches with problematic keywords
		const keywords = [
			'graphic of', 'bullet', 'image of', 'image', 'graphic', 'photo',
			'photograph', 'drawing', 'painting', 'artwork', 'logo',
			'button', 'arrow', 'more', 'spacer', 'blank', 'chart', 'table',
			'diagram', 'graph', '*',
		];
		if ( keywords.includes( altTrimmed ) ) {
			return false;
		}

		// Check if the alt contains a problematic substring
		const contains = [
			'_', 'img', 'jpg', 'jpeg', 'apng', 'png', 'svg', 'webp',
		];
		for ( const substring of contains ) {
			if ( altTrimmed.includes( substring ) ) {
				return false;
			}
		}

		// Check if the alt is composed of only numbers
		if ( /^\d+$/.test( altTrimmed ) ) {
			return false;
		}

		// All checks passed, the alt text is valid
		return true;
	},
	options: {},
	metadata: {
		impact: 'serious',
		messages: {
			pass: 'Image alt text is valid',
			fail: 'Image has invalid alt text (contains generic terms, file names, or only numbers)',
		},
	},
};
