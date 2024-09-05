const MetaViewportScalable = window.edac_frontend_fixes.meta_viewport_scalable || {
	enabled: false,
};

const MetaViewportScalableFix = () => {
	if ( ! MetaViewportScalable.enabled ) {
		return;
	}

	// Get the meta viewport tag.
	const metaViewport = document.querySelector( 'meta[name="viewport"]' );
	if ( metaViewport ) {
		// check if it has scalable set to no.
		if ( metaViewport.content.includes( 'user-scalable=no' ) ) {
			// Remove the meta viewport tag to allow it to be recreated.
		} else {
			// Tag is fine, return.
			return;
		}
	}

	// Create a new meta viewport tag.
	const newMetaViewport = document.createElement( 'meta' );
	newMetaViewport.name = 'viewport';
	newMetaViewport.content = 'width=device-width, initial-scale=1';
	document.head.appendChild( newMetaViewport );
};

export default MetaViewportScalableFix;
