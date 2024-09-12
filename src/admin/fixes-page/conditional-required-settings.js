// if element with id edac_fix_add_skip_link is checked then force element with id edac_fix_add_skip_link_target_id to be required

export const initRequiredSetup = () => {
	document.querySelectorAll( '[data-required_when]' ).forEach( ( element ) => {
		const conditionId = element.getAttribute( 'data-required_when' );

		const conditionElement = document.getElementById( conditionId );

		if ( conditionElement ) {
			setRequiredState( conditionElement.checked, element.id );
			conditionElement.addEventListener( 'change', ( event ) => {
				// find any element that points to this element id and set it to required
				const targets = document.querySelectorAll( `[data-required_when="${ conditionId }"]` );
				targets.forEach( ( target ) => {
					setRequiredState( event.target.checked, target.id );
				} );
			} );
		}
	} );
};

const setRequiredState = ( checked, elementId ) => {
	// if this is checked then force the target id to be required
	const targetElement = document.getElementById( elementId );
	targetElement.required = checked;
};
