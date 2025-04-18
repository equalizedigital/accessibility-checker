const LONG_CONTENT_THRESHOLD = 400;

export default {
	id: 'has_subheadings_if_long_content',
	evaluate: ( node ) => {
		if ( node !== document.body ) {
			return true;
		}

		// Count words in text content (excluding scripts, styles)
		const text = node.textContent.replace( /\s+/g, ' ' ).trim();
		const wordCount = text.split( /\s+/ ).length;

		if ( wordCount < LONG_CONTENT_THRESHOLD ) {
			return true;
		}

		// Find all headings (both HTML and ARIA)
		const headings = [
			'h2, [role="heading"][aria-level="2"]',
			'h3, [role="heading"][aria-level="3"]',
			'h4, [role="heading"][aria-level="4"]',
			'h5, [role="heading"][aria-level="5"]',
			'h6, [role="heading"][aria-level="6"]',
		].map( ( selector ) => document.querySelectorAll( selector ).length )
			.reduce( ( sum, count ) => sum + count, 0 );

		return headings > 0;
	},
};
