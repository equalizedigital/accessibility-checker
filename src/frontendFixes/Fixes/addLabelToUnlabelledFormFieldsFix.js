const AddLabelToUnlablledFormFieldsFixData = window.edac_frontend_fixes?.add_label_to_unlabelled_form_fields || {
	enabled: false,
};

const AddLabelToUnlabelledFormFieldsFix = () => {
	if ( ! AddLabelToUnlablledFormFieldsFixData.enabled ) {
		return;
	}

	// find all form fields on the page, excluding submit buttons.
	const formFields = document.querySelectorAll( 'input:not([type="submit"]), select, textarea' );

	formFields.forEach( ( field ) => {
		// If the field is hidden, skip it.
		if ( field.offsetParent === null ) {
			return;
		}

		// If the field has an ID check if there are labels with a for attribute that matches the ID and skip if found.
		if ( field.id ) {
			const label = document.querySelector( `label[for="${ field.id }"]` );
			if ( label ) {
				return;
			}
		}

		// Look at up to 3 parent elements to see if the field is in a label and if so don't add another.
		let parentElement = field.parentElement;
		let parentElementCount = 0;
		while ( parentElementCount < 3 ) {
			if ( parentElement.tagName.toLowerCase() === 'label' ) {
				return;
			}
			parentElement = parentElement.parentElement;
			parentElementCount++;
		}

		// Is there a labelledby attribute?
		const labelledBy = field.getAttribute( 'aria-labelledby' );
		if ( labelledBy ) {
			// Check if the element with that id has text content and return if non-empty.
			const labelledByElement = document.getElementById( labelledBy );
			if ( labelledByElement && labelledByElement.innerText.trim() !== '' ) {
				return;
			}
		}

		// Try get something to use as label text.
		const labelData = tryGetLabelData( field );
		if ( labelData.attribute !== '' && labelData.text !== '' ) {
			wrapFieldInLabel( field, labelData );
		}
	} );
};

/**
 * Try to get label data from the field's attributes.
 *
 * Prefer title, aria-label, name, placeholder in that order.
 *
 * @param {HTMLElement} field
 * @return {Object} - {attribute: string, text: string}
 */
const tryGetLabelData = ( field ) => {
	const preferredAttributesOrder = [ 'title', 'aria-label', 'name', 'placeholder' ];

	const labelData = {
		attribute: '',
		text: '',
	};

	preferredAttributesOrder.forEach( ( attributeToCheck ) => {
		if ( labelData.text.length && labelData.attribute.length ) {
			return;
		}

		const attributeText = field.getAttribute( attributeToCheck )?.trim();
		if ( attributeText.length ) {
			labelData.attribute = attributeToCheck;
			labelData.text = attributeText;
		}
	} );

	if ( labelData.text !== '' ) {
		// Replace underscores with spaces.
		labelData.text = labelData.text.replace( /_/g, ' ' );
		// Seporate words with spaces if camelCase.
		labelData.text = labelData.text.replace( /([a-z])([A-Z])/g, '$1 $2' );
		// Capitalize first letter.
		labelData.text = labelData.text.charAt( 0 ).toUpperCase() + labelData.text.slice( 1 );
	}

	return labelData;
};

/**
 * Wrap the field in a label element.
 *
 * The label will have a class of 'edac-generated-label' and a data attribute of 'data-edac-generated-label-from' with the attribute used to generate the label.
 *
 * @param {HTMLElement} field
 * @param {Object}      labelData - {attribute: string, text: string}
 */
const wrapFieldInLabel = ( field, labelData ) => {
	// Create a label with the text, and add a class and some data to it.
	const label = document.createElement( 'label' );
	label.classList.add( 'edac-generated-label' );
	label.setAttribute( 'data-edac-generated-label-from', labelData.attribute );
	label.innerText = labelData.text;

	// Insert the label and put the field inside it.
	field.parentNode.insertBefore( label, field );
	label.appendChild( field );

	// Remove the attribute the label was generated from, unless it is a placeholder.
	if ( labelData.attribute !== 'placeholder' ) {
		field.removeAttribute( labelData.attribute );
	}
};

export default AddLabelToUnlabelledFormFieldsFix;
