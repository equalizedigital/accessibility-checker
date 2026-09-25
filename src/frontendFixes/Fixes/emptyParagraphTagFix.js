const EmptyParagraphTagFixData = window.edac_frontend_fixes?.empty_paragraph_tag || {
	enabled: false,
};

const EmptyParagraphTagFix = () => {
	if ( ! EmptyParagraphTagFixData.enabled ) {
		return;
	}

	const paragraphElements = document.querySelectorAll( 'p' );

	paragraphElements.forEach( ( paragraphElement ) => {
		if ( paragraphElement.getAttribute( 'aria-hidden' )?.toLowerCase() === 'true' ) {
			return;
		}

		const hasNonTextChildNodes = Array.from( paragraphElement.childNodes ).some( ( childNode ) => childNode.nodeType !== 3 );
		if ( hasNonTextChildNodes ) {
			return;
		}

		if ( paragraphElement.textContent.trim() !== '' ) {
			return;
		}

		paragraphElement.remove();
	} );
};

export default EmptyParagraphTagFix;
