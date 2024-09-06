const RemoveTitleIfPreferredAccessibleNameFix = window.edac_frontend_fixes?.remove_title_if_preferred_accessible_name || {
	enabled: false,
};

const RemoveTitleIfPreferredAccessibleName = () => {
	if ( ! RemoveTitleIfPreferredAccessibleNameFix.enabled ) {
		return;
	}

	const elementsWithTitle = document.querySelectorAll( 'img[title], a[title], input[title], textarea[title], select[title], button[title]' );

	const imagesWithTitles = document.querySelectorAll( 'img[title]' );
	imagesWithTitles.forEach( ( element ) => {
		if ( element.getAttribute( 'title' )?.trim() === '' ) {
			return;
		}

		// if the image has a non-empty alt attribute, remove the title attribute
		if ( element.getAttribute( 'alt' )?.trim() !== '' ) {
			removeTitle( element );
			return;
		}
		// since the alt is empty put the title in the alt then remove the title.
		element.setAttribute( 'alt', element.getAttribute( 'title' ) );
		removeTitle( element );
	} );

	const linksOrButtonsWithTitles = elementsWithTitle.querySelectorAll( 'a, button' );
	linksOrButtonsWithTitles.forEach( ( element ) => {
		if ( element.getAttribute( 'title' )?.trim() === '' ) {
			return;
		}

		// if the element has non-empty text content, remove the title attribute
		if ( element.innerText.trim() !== '' ) {
			removeTitle( element );
			return;
		}

		// element has no text content, check if it has aria-label or aria-labelledby attribute
		const ariaLabel = element.getAttribute( 'aria-label' );
		const ariaLabelledBy = element.getAttribute( 'aria-labelledby' );
		if ( ariaLabel?.trim() !== '' || ariaLabelledBy?.trim() !== '' ) {
			removeTitle( element );
			return;
		}

		// by this point the element has no text content, aria-label or aria-labelledby, move the title to the aria-label then remove the title
		element.setAttribute( 'aria-label', element.getAttribute( 'title' ) );
		removeTitle( element );
	} );

	const inputsWithTitles = elementsWithTitle.querySelectorAll( 'input, textarea, select' );
	inputsWithTitles.forEach( ( element ) => {
		if ( element.getAttribute( 'title' )?.trim() === '' ) {
			return;
		}

		// check for some associated label or wrapping label.
		const ariaLabel = element.getAttribute( 'aria-label' );
		const ariaLabelledBy = element.getAttribute( 'aria-labelledby' );
		const associatedLabel = element.labels?.[ 0 ]?.innerText;
		const wrappingLabel = element.closest( 'label' )?.innerText;

		if ( ariaLabel?.trim() !== '' || ariaLabelledBy?.trim() !== '' || associatedLabel?.trim() !== '' || wrappingLabel?.trim() !== '' ) {
			// there exists a label already, remove the title attribute.
			removeTitle( element );
			return;
		}

		// by this point there is no proper labeling found so move the title to the aria-label then remove the title
		element.setAttribute( 'aria-label', element.getAttribute( 'title' ) );
		removeTitle( element );
	} );
};

const removeTitle = ( element ) => {
	element.classList.add( 'edac-removed-title' );
	element.removeAttribute( 'title' );
};

export default RemoveTitleIfPreferredAccessibleName;
