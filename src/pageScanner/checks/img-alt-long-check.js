/**
 * Check if image alt text exceeds the maximum allowed length.
 * Ported from PHP function edac_rule_img_alt_long
 */

export default {
	id: 'img_alt_long_check',
	evaluate( node, options = {} ) {
		// Get alt text from the node
		const altText = node.getAttribute( 'alt' );

		// If alt text exists and is longer than max length, it fails the check
		if ( altText && altText.length > options.maxAltLength ) {
			return false;
		}

		// Otherwise it passes
		return true;
	},
	options: {
		maxAltLength: 300,
	},
};
