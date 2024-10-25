const setInputStates = () => {
	const elements = document.querySelectorAll( '[data-condition]' );

	elements.forEach( ( element ) => {
		const conditionId = element.getAttribute( 'data-condition' );

		const conditionElement = document.getElementById( conditionId );

		if ( conditionElement ) {
			if ( conditionElement.tagName.toLowerCase() === 'input' ) {
				if ( ( conditionElement.type === 'text' && conditionElement.value.trim() !== '' ) ||
					( conditionElement.type === 'checkbox' && conditionElement.checked ) ) {
					element.disabled = false;
					element.closest( 'tr' ).classList.remove( 'edac-fix--hidden' );
				} else {
					element.disabled = true;
					element.closest( 'tr' ).classList.add( 'edac-fix--hidden' );
				}
			}
		}
	} );
};

export const initFixesInputStateHandler = () => {
	setInputStates();

	// Find all checkboxes inside the form.
	const checkboxes = document.querySelectorAll( '.edac-settings form input[type="checkbox"]' );
	checkboxes.forEach( ( checkbox ) => {
		checkbox.addEventListener( 'change', () => {
			setInputStates();
		} );
	} );
};

