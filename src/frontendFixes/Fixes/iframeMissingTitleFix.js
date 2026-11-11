const iframeMissingTitle = window.edac_frontend_fixes?.iframe_missing_title || {
	enabled: false,
};

const getFallbackIframeTitle = ( iframe ) => {
	const src = iframe.getAttribute( 'src' )?.trim();

	if ( src ) {
		try {
			const parsedUrl = new URL( src, window.location.href );
			return `Embedded content from ${ parsedUrl.hostname }`;
		} catch ( e ) {
			// Continue to generic fallback if the URL can't be parsed.
		}
	}

	return 'Embedded content';
};

const iframeMissingTitleFix = () => {
	if ( ! iframeMissingTitle.enabled ) {
		return;
	}

	const iframes = document.querySelectorAll( 'iframe' );

	iframes.forEach( ( iframe ) => {
		const title = iframe.getAttribute( 'title' );
		if ( title && title.trim() ) {
			return;
		}

		iframe.setAttribute( 'title', getFallbackIframeTitle( iframe ) );
	} );
};

export default iframeMissingTitleFix;
