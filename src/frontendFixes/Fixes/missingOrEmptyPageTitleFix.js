const MissingOrEmptyPageTitle = window.edac_frontend_fixes?.missing_or_empty_page_title || {
	enabled: false,
};

const MissingOrEmptyPageTitleFix = () => {
	if ( ! MissingOrEmptyPageTitle.enabled ) {
		return;
	}

	if ( document.title && document.title.trim() !== '' ) {
		return;
	}

	if ( MissingOrEmptyPageTitle.full_title && MissingOrEmptyPageTitle.full_title.trim() !== '' ) {
		document.title = MissingOrEmptyPageTitle.full_title;
		return;
	}

	// try finding the first header element with text content and use it.
	const headers = document.querySelectorAll( 'h1, .h1, h2, h3, h4, h5, h6' );
	for ( let i = 0; i < headers.length; i++ ) {
		const header = headers[ i ];
		if ( header.innerText.trim() !== '' ) {
			document.title = header.innerText;
			if ( MissingOrEmptyPageTitle.site_name && MissingOrEmptyPageTitle.site_name.trim() !== '' ) {
				document.title += ' ' + MissingOrEmptyPageTitle.seporator + ' ' + MissingOrEmptyPageTitle.site_name;
			}
			break;
		}
	}

	if ( ! document.title && MissingOrEmptyPageTitle.site_name && MissingOrEmptyPageTitle.site_name.trim() !== '' ) {
		document.title = MissingOrEmptyPageTitle.site_name;
	}
};

export default MissingOrEmptyPageTitleFix;
