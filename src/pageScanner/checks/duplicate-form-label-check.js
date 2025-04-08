export default {
	id: 'duplicate_form_label_check',
	evaluate( node ) {
		// Skip elements that don't need labels
		const skipElements = [ 'hidden', 'button', 'submit', 'reset', 'image' ];
		if ( node.tagName === 'INPUT' && skipElements.includes( node.type ) ) {
			return true;
		}

		// Skip hidden elements
		if ( node.getAttribute( 'aria-hidden' ) === 'true' ||
			node.hidden ||
			node.style.display === 'none' ||
			node.style.visibility === 'hidden' ) {
			return true;
		}

		// Skip elements with their own text content (like buttons)
		if ( ( node.tagName === 'BUTTON' || node.hasAttribute( 'aria-label' ) ) && node.textContent.trim() ) {
			return true;
		}

		// Check for associated labels using the `for` attribute
		// This is the main check for duplicate labels
		if ( node.id ) {
			const forLabels = Array.from( document.querySelectorAll( `label[for="${ node.id }"]` ) );

			// Fail if multiple labels are found - this is the primary duplicate label check
			if ( forLabels.length > 1 ) {
				return false;
			}
		}

		// Check if element has an accessible name from somewhere
		// (aria-label, aria-labelledby, label element, etc.)
		let hasAccessibleName = false;

		// Check explicit label
		if ( node.id && document.querySelector( `label[for="${ node.id }"]` ) ) {
			hasAccessibleName = true;
		}

		// Check implicit label
		if ( node.closest( 'label' ) ) {
			hasAccessibleName = true;
		}

		// Check aria-label
		if ( node.hasAttribute( 'aria-label' ) && node.getAttribute( 'aria-label' ).trim() ) {
			hasAccessibleName = true;
		}

		// Check aria-labelledby - note that multiple IDs here is valid
		const ariaLabelledBy = node.getAttribute( 'aria-labelledby' );
		if ( ariaLabelledBy ) {
			const ids = ariaLabelledBy.split( ' ' );
			// Check if at least one referenced element exists
			const hasValidReference = ids.some( ( id ) => document.getElementById( id ) );
			if ( hasValidReference ) {
				hasAccessibleName = true;
			}
		}

		// Fail if no accessible name is found
		if ( ! hasAccessibleName ) {
			return false;
		}

		return true;
	},
};
