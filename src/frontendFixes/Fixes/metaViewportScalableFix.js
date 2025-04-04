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
		// check if it has scalable set to no or 0.
		if ( ! metaViewport.content.match( /user\-scalable\s*=\s*(no|0)/ ) ) {
			return;
		}
		// remove the meta viewport tag as it blocks scaling.
		metaViewport.remove();
	}

	// Create a new meta viewport tag.
	const newMetaViewport = document.createElement( 'meta' );
	newMetaViewport.name = 'viewport';
	newMetaViewport.content = 'width=device-width, initial-scale=1';
	document.head.appendChild( newMetaViewport );
};

export default MetaViewportScalableFix;
