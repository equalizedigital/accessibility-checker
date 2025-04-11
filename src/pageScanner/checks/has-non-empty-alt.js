export default {
	id: 'has_non_empty_alt',
	evaluate( node ) {
		const alt = node.getAttribute( 'alt' );
		return typeof alt === 'string' && alt.trim().length > 0; // Ensure alt is a non-empty string
	},
	metadata: {
		impact: 'critical',
		messages: {
			pass: 'Area element has non-empty alt text',
			fail: 'Area element has missing or empty alt text',
		},
	},
};
