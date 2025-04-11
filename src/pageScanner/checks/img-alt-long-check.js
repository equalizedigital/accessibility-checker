/**
 * Check if image alt text exceeds the maximum allowed length.
 * Ported from PHP function edac_rule_img_alt_long
 */

export default {
	id: 'img_alt_long_check',
	evaluate( node, options = {} ) {
		// Default maximum alt text length is 300 characters
		// This can be configured through options if needed
		const maxAltLength = options.maxAltLength || 300;

		// Get alt text from the node
		const altText = node.getAttribute( 'alt' );

		// If alt text exists and is longer than max length, it fails the check
		if ( altText && altText.length > maxAltLength ) {
			return false;
		}

		// Otherwise it passes
		return true;
	},
	options: {
		maxAltLength: 300,
	},
	metadata: {
		impact: 'moderate',
		messages: {
			pass: 'Image alt text length is acceptable',
			fail: 'Image alt text is too long (exceeds 300 characters)',
		},
	},
};
