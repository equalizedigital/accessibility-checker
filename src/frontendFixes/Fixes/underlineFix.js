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

	targets.forEach( function( target ) {
		if ( ! target.closest( 'nav' ) ) {
			target.style.textDecoration = 'underline';
			return;
		}

		// Store the original styles in data-* attributes
		target.setAttribute( 'data-original-outline', target.style.outlineWidth );
		target.setAttribute( 'data-original-offset', target.style.outlineOffset );
		target.setAttribute( 'data-original-color', target.style.outlineColor );

		const textColor = target.style.color; // Capture the text color separately

		target.addEventListener( 'mouseenter', function() {
			target.style.textDecoration = 'none';
		} );

		target.addEventListener( 'mouseleave', function() {
			target.style.textDecoration = 'underline';
		} );

		target.addEventListener( 'focusin', function() {
			let newOutline = '2px';
			if ( target.style.outlineWidth === '2px' ) {
				newOutline = '4px';
			}
			// Increase outline thickness and adjust color and offset to ensure a visible focus indicator.
			target.style.outlineWidth = newOutline;
			target.style.outlineColor = textColor;
			target.style.outlineOffset = '2px';
		} );

		target.addEventListener( 'focusout', function() {
			// Restore the original styles from data-* attributes
			target.style.outlineWidth = target.getAttribute( 'data-original-outline' );
			target.style.outlineColor = target.getAttribute( 'data-original-color' );
			target.style.outlineOffset = target.getAttribute( 'data-original-offset' );
		} );
	} );
};

export default ForceUnderlineFix;
