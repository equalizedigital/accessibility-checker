const ForceUnderlineFixData = window.edac_frontend_fixes?.underline || {
	enabled: false,
};

const ForceUnderlineFix = () => {
	if ( ! ForceUnderlineFixData.enabled ) {
		return;
	}

	// Apply underline to any link that is not within a `nav` element.
	// Using JavaScript ensures this style takes precedence over conflicting CSS rules.
	const targets = document.querySelectorAll( ForceUnderlineFixData.target );
	const originalOutline = targets[ 0 ].style.outlineWidth;
	const originalOffset = targets[ 0 ].style.outlineOffset;
	const textColor = targets[ 0 ].style.color;
	const originalColor = targets[ 0 ].style.outlineColor;

	targets.forEach( function( target ) {
		if ( ! target.closest( 'nav' ) ) {
			target.style.textDecoration = 'underline';
		}

		target.addEventListener( 'mouseenter', function() {
			target.style.textDecoration = 'none';
		} );

		target.addEventListener( 'mouseleave', function() {
			target.style.textDecoration = 'underline';
		} );

		target.addEventListener( 'focusin', function() {
			let newOutline = '2px';
			if ( originalOutline === '2px' ) {
				newOutline = '4px';
			}
			// Increase outline thickness and adjust color and offset to ensure a visible focus indicator.
			target.style.outlineWidth = newOutline;
			target.style.outlineColor = textColor;
			target.style.outlineOffset = '2px';
		} );

		target.addEventListener( 'focusout', function() {
			target.style.outlineWidth = originalOutline;
			target.style.outlineColor = originalColor;
			target.style.outlineOffset = originalOffset;
		} );
	} );
};

export default ForceUnderlineFix;
