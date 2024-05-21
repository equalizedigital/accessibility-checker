export const fontSizeInPx = ( node ) => {
	if ( node === null || node === 'undefined' || node.nodeType !== Node.ELEMENT_NODE ) {
		return 0;
	}

	return parseFloat( window.getComputedStyle( node ).fontSize );
};
