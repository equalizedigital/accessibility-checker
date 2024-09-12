const RemoveTabindexFixData = window.edac_frontend_fixes?.tabindex || {
	enabled: false,
};

const TabindexFix = () => {
	if ( ! RemoveTabindexFixData.enabled ) {
		return;
	}

	// Remove tabindex from elements that are natively focusable
	const focusable = document.querySelectorAll( 'input, a, select, textarea, button' );
	focusable.forEach( ( element ) => {
		// Skip anchor tags without an href attribute or those with role="button" as they are not natively focusable
		if ( element.tagName === 'A' && ( ! element.hasAttribute( 'href' ) || element.getAttribute( 'role' ) === 'button' ) ) {
			return;
		}

		// Remove the tabindex if present and is not -1.
		if ( element.hasAttribute( 'tabindex' ) && element.getAttribute( 'tabindex' ) !== '-1' ) {
			element.removeAttribute( 'tabindex' );
		}
	} );

	// Add tabindex to elements that appear active but are not natively focusable.
	// Select all <div> elements with a role of "button" or <a> elements with role="button" without href or tabindex
	const elementsToFocus = document.querySelectorAll(
		'div[role="button"]:not([tabindex]), a[role="button"]:not([tabindex]):not([href])'
	);

	// Loop through each element and add tabindex="0" to make it focusable
	elementsToFocus.forEach( ( element ) => {
		// Don't add tabindex 0 to elements that alaredy have -1.
		if ( element.hasAttribute( 'tabindex' ) && element.getAttribute( 'tabindex' ) === '-1' ) {
			return;
		}
		element.setAttribute( 'tabindex', '0' );
		element.classList.add( 'edac-focusable' );
	} );
};

export default TabindexFix;
