const LangAndDirFixData = window.edac_frontend_fixes?.lang_and_dir || {
	enabled: false,
};

const LangAndDirFix = () => {
	if ( ! LangAndDirFixData.enabled ) {
		return;
	}

	const HTMLElement = document.querySelector( 'html' );
	const lang = HTMLElement.getAttribute( 'lang' );
	const dir = HTMLElement.getAttribute( 'dir' );

	if ( ! lang || lang !== LangAndDirFixData.lang ) {
		HTMLElement.setAttribute( 'lang', LangAndDirFixData.lang );
	}

	if ( ! dir || dir !== LangAndDirFixData.dir ) {
		HTMLElement.setAttribute( 'dir', LangAndDirFixData.dir );
	}
};

export default LangAndDirFix;
