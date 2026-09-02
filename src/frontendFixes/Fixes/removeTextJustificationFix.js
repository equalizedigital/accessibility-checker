const RemoveTextJustificationFixData = window.edac_frontend_fixes?.remove_text_justification || {
	enabled: false,
};

const TEXT_JUSTIFIED_CHECK_THRESHOLD = 200;

const RemoveTextJustificationFix = () => {
	if ( ! RemoveTextJustificationFixData.enabled ) {
		return;
	}

	const targets = document.querySelectorAll( RemoveTextJustificationFixData.target || 'p' );
	targets.forEach( ( target ) => {
		if ( target.textContent.trim().length < TEXT_JUSTIFIED_CHECK_THRESHOLD ) {
			return;
		}

		const style = window.getComputedStyle( target );
		if ( style.textAlign.toLowerCase() !== 'justify' ) {
			return;
		}

		target.style.textAlign = 'left';
	} );
};

export default RemoveTextJustificationFix;
