const IframeMissingTitleFixData = window.edac_frontend_fixes?.iframe_missing_title || {
	enabled: false,
	fallback_title: 'Embedded content',
};

const getIframeTitle = ( iframe ) => {
	const src = iframe.getAttribute( 'src' ) || '';
	if ( ! src ) {
		return IframeMissingTitleFixData.fallback_title;
	}

	try {
		const srcUrl = new URL( src, window.location.href );
		if ( srcUrl.hostname ) {
			return `${ IframeMissingTitleFixData.fallback_title } from ${ srcUrl.hostname }`;
		}
	} catch ( error ) {
		// Ignore invalid URL and use fallback title.
	}

	return IframeMissingTitleFixData.fallback_title;
};

const IframeMissingTitleFix = () => {
	if ( ! IframeMissingTitleFixData.enabled ) {
		return;
	}

	const iframes = document.querySelectorAll( 'iframe' );

	iframes.forEach( ( iframe ) => {
		const currentTitle = iframe.getAttribute( 'title' );
		if ( currentTitle && currentTitle.trim() ) {
			return;
		}

		iframe.setAttribute( 'title', getIframeTitle( iframe ) );
	} );
};

export default IframeMissingTitleFix;
