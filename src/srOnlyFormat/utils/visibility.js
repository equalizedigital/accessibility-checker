import apiFetch from '@wordpress/api-fetch';

export const CLASS_NAME = 'sr-only-show-always';

let observerCleanup = null;
let domReadyPromise = null;

export const toggleClassOnDocumentBody = ( targetDocument, checked ) => {
	targetDocument?.body?.classList.toggle( CLASS_NAME, checked );
	targetDocument?.querySelectorAll( '.editor-styles-wrapper' )?.forEach( ( element ) => {
		element.classList.toggle( CLASS_NAME, checked );
	} );
};

export const applySrOnlyVisibility = ( checked ) => {
	toggleClassOnDocumentBody( document, checked );

	document.querySelectorAll( 'iframe' ).forEach( ( iframe ) => {
		try {
			toggleClassOnDocumentBody( iframe.contentDocument, checked );
		} catch ( iframeAccessError ) {
			// Ignore cross-document access failures.
		}
	} );
};

const waitForDocumentBody = () => {
	if ( document.body ) {
		return Promise.resolve();
	}

	if ( ! domReadyPromise ) {
		domReadyPromise = new Promise( ( resolve ) => {
			const handleReady = () => {
				if ( document.body ) {
					resolve();
					return;
				}

				window.requestAnimationFrame( handleReady );
			};

			if ( document.readyState === 'loading' ) {
				document.addEventListener( 'DOMContentLoaded', handleReady, { once: true } );
				return;
			}

			handleReady();
		} );
	}

	return domReadyPromise;
};

export const fetchUserMetaValue = async ( metaKey ) => {
	const response = await apiFetch( { path: '/wp/v2/users/me', method: 'GET' } );
	return Boolean( response?.meta?.[ metaKey ] );
};

const observeSrOnlyVisibility = () => {
	const handleFrameLoad = ( event ) => {
		try {
			const checked = document.body?.classList.contains( CLASS_NAME ) ?? false;
			toggleClassOnDocumentBody( event.target?.contentDocument, checked );
		} catch ( frameLoadError ) {
			// Ignore cross-document access failures.
		}
	};

	const attachListener = ( iframe ) => {
		iframe.addEventListener( 'load', handleFrameLoad );
		try {
			const checked = document.body?.classList.contains( CLASS_NAME ) ?? false;
			toggleClassOnDocumentBody( iframe.contentDocument, checked );
		} catch ( iframeAccessError ) {
			// Ignore cross-document access failures.
		}
	};

	const detachListener = ( iframe ) => {
		iframe.removeEventListener( 'load', handleFrameLoad );
	};

	const iframes = Array.from( document.querySelectorAll( 'iframe' ) );
	iframes.forEach( attachListener );

	const observer = new MutationObserver( ( mutations ) => {
		mutations.forEach( ( mutation ) => {
			mutation.addedNodes.forEach( ( node ) => {
				if ( node.nodeType !== Node.ELEMENT_NODE ) {
					return;
				}

				if ( node.tagName === 'IFRAME' ) {
					attachListener( node );
					return;
				}

				node.querySelectorAll?.( 'iframe' ).forEach( attachListener );
			} );
		} );
	} );

	if ( document.body ) {
		observer.observe( document.body, { childList: true, subtree: true } );
	}

	return () => {
		observer.disconnect();
		iframes.forEach( detachListener );
	};
};

export const ensureSrOnlyVisibilityObserver = () => {
	if ( observerCleanup ) {
		return Promise.resolve();
	}

	return waitForDocumentBody().then( () => {
		if ( observerCleanup ) {
			return;
		}

		observerCleanup = observeSrOnlyVisibility();
	} );
};

export const initializeSrOnlyVisibilityPreference = async ( metaKey ) => {
	await ensureSrOnlyVisibilityObserver();

	if ( typeof window.edacSrOnlyFormat?.showSrTextInEditor === 'boolean' ) {
		applySrOnlyVisibility( window.edacSrOnlyFormat.showSrTextInEditor );
		return;
	}

	try {
		const checked = await fetchUserMetaValue( metaKey );
		applySrOnlyVisibility( checked );
	} catch ( fetchError ) {
		// Leave the default hidden state in place if the preference lookup fails.
	}
};
