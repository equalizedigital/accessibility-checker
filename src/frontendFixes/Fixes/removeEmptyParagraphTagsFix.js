const RemoveEmptyParagraphTagsFixData = window.edac_frontend_fixes?.remove_empty_paragraph_tags || {
	enabled: false,
};

const hasNonTextChild = ( node ) => Array.from( node.childNodes ).some( ( child ) => child.nodeType !== Node.TEXT_NODE );

const isAriaHiddenTrue = ( node ) => {
	const value = node.getAttribute( 'aria-hidden' );
	return value && value.toLowerCase() === 'true';
};

const isEmptyParagraph = ( node ) => {
	if ( node.tagName.toLowerCase() !== 'p' ) {
		return false;
	}

	if ( isAriaHiddenTrue( node ) ) {
		return false;
	}

	if ( hasNonTextChild( node ) ) {
		return false;
	}

	return node.textContent.trim() === '';
};

const RemoveEmptyParagraphTagsFix = () => {
	if ( ! RemoveEmptyParagraphTagsFixData.enabled ) {
		return;
	}

	document.querySelectorAll( 'p' ).forEach( ( paragraph ) => {
		if ( isEmptyParagraph( paragraph ) ) {
			paragraph.remove();
		}
	} );
};

export default RemoveEmptyParagraphTagsFix;
