export default {
	id: 'link-is-naked',
	evaluate: function(node) {
		if (!node || typeof node.getAttribute !== 'function') {
			return undefined; // Not a valid element
		}
		const href = node.getAttribute('href');
		const textContent = (node.textContent || '').trim();

		if (!href) {
			return undefined; // No href attribute, so not applicable
		}

		// Check if the trimmed text content is the same as the href
		return textContent === href;
	},
	metadata: {
		description: 'Checks if a link's text is the same as its href attribute.',
		help: 'The text of a link should be descriptive and not simply the URL.',
	},
};
