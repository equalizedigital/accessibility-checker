export const fontSizeInPx = ( node ) => {
	if ( ! node || node.nodeType !== Node.ELEMENT_NODE ) {
		return 0;
	}

	const fontSize = parseFloat( window.getComputedStyle( node ).fontSize );
	return typeof fontSize === 'number' ? fontSize : 0;
};

/**
 * Helper function to normalize text by trimming and replacing consecutive whitespace
 * @param {string} text - Text to normalize
 * @return {string} Normalized text
 */
export const normalizeText = ( text ) => {
	return ( text || '' ).trim().toLowerCase().replace( /\s+/g, ' ' );
};
