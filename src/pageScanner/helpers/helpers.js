export const fontSizeInPx = ( node ) => {
	if ( ! node || node.nodeType !== Node.ELEMENT_NODE ) {
		return 0;
	}

	return parseFloat( window.getComputedStyle( node ).fontSize );
};
