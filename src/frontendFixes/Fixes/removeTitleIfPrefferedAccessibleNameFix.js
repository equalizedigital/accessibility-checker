const RemoveTitleIfPreferredAccessibleNameFix = window.edac_frontend_fixes?.remove_title_if_preferred_accessible_name || {
	enabled: false,
};

const RemoveTitleIfPreferredAccessibleName = () => {
	if ( ! RemoveTitleIfPreferredAccessibleNameFix.enabled ) {
		return;
	}

	const elementsWithTitle = document.querySelectorAll( 'img[title], a[title], input[title], textarea[title], select[title], button[title]' );

	elementsWithTitle.forEach( ( element ) => {
		if ( element.getAttribute( 'title' )?.trim() === '' ) {
			return;
		}

		const tagName = element.tagName.toLowerCase();

		if ( tagName === 'img' ) {
			handleImageElement( element );
		} else if ( [ 'a', 'button' ].includes( tagName ) ) {
			handleLinkOrButtonElement( element );
		} else if ( [ 'input', 'textarea', 'select' ].includes( tagName ) ) {
			handleInputElements( element );
		}
	} );
};

/**
 * Handle image elements.
 *
 * Prefer alt attribute over title attribute.
 *
 * @param {HTMLElement} element
 */
const handleImageElement = ( element ) => {
	// if the image has a non-empty alt attribute, remove the title attribute
	if ( element.getAttribute( 'alt' )?.trim() !== '' ) {
		removeTitle( element );
		return;
	}
	// since the alt is empty put the title in the alt then remove the title.
	element.setAttribute( 'alt', element.getAttribute( 'title' ) );
	removeTitle( element );
};

/**
 * Handle link or button elements.
 *
 * Prefer aria-label or aria-labelledby over title attribute.
 *
 * @param {HTMLElement} element
 */
const handleLinkOrButtonElement = ( element ) => {
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
};

/**
 * Handle input elements.
 *
 * Prefer aria-label or aria-labelledby over title attribute.
 *
 * @param {HTMLElement} element
 */
const handleInputElements = ( element ) => {
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
};

const removeTitle = ( element ) => {
	element.classList.add( 'edac-removed-title' );
	element.removeAttribute( 'title' );
};

export default RemoveTitleIfPreferredAccessibleName;
