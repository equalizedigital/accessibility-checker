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
	const alt = element.getAttribute( 'alt' );
	if ( alt && alt?.trim() !== '' ) {
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
	const textContent = element.innerText;
	if ( textContent && textContent?.trim() !== '' ) {
		removeTitle( element );
		return;
	}

	// check if it has aria-label or aria-labelledby attribute
	if ( checkAriaLabel( element ) ) {
		removeTitle( element );
		return;
	}

	// By this point the element has no aria-label or aria-labelledby, move the title to the aria-label then remove the title
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
	// Check if it has aria-label or aria-labelledby attribute.
	if ( checkAriaLabel( element ) ) {
		removeTitle( element );
		return;
	}

	// Has an associated label or is wrapped in a label.
	const associatedLabel = element.labels?.[ 0 ]?.innerText;
	const wrappingLabel = element.closest( 'label' )?.innerText;
	if (
		( associatedLabel && associatedLabel?.trim() !== '' ) ||
		( wrappingLabel && wrappingLabel?.trim() !== '' )
	) {
		removeTitle( element );
		return;
	}

	// By this point there is no proper labeling found so move the title to the aria-label then remove the title.
	element.setAttribute( 'aria-label', element.getAttribute( 'title' ) );
	removeTitle( element );
};

/**
 * Remove the title attribute and add a class to the element.
 *
 * @param {HTMLElement} element
 */
const removeTitle = ( element ) => {
	element.classList.add( 'edac-removed-title' );
	element.removeAttribute( 'title' );
};

/**
 * Check if the element has aria-label or aria-labelledby attribute.
 *
 * @param {HTMLElement} element
 * @return {boolean} True if the element has aria-label or aria-labelledby attribute.
 */
const checkAriaLabel = ( element ) => {
	const ariaLabel = element.getAttribute( 'aria-label' );
	const ariaLabelledBy = element.getAttribute( 'aria-labelledby' );
	if (
		( ariaLabel && ariaLabel?.trim() !== '' ) ||
		( ariaLabelledBy && ariaLabelledBy?.trim() !== '' )
	) {
		return true;
	}
	return false;
};

export default RemoveTitleIfPreferredAccessibleName;
