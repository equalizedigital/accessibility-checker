export default {
	id: 'image_input_has_alt',
	evaluate: ( node ) => {
		// Only applies to image inputs.
		if ( node.tagName.toLowerCase() !== 'input' || node.type !== 'image' ) {
			return false;
		}

		const alt = node.getAttribute( 'alt' );
		return alt !== null && alt.trim() !== '';
	},
};
