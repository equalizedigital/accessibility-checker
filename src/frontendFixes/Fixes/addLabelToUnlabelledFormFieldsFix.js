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

		// Look at up to 3 parent elements to see if any are a label and if so don't add another.
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
		if ( attributeText ) {
			labelData.attribute = attributeToCheck;
			labelData.text = attributeText;
		}
	} );

	if ( labelData.text !== '' ) {
		// replace underscores with spaces
		labelData.text = labelData.text.replace( /_/g, ' ' );
		// seporate words with spaces if camelcase
		labelData.text = labelData.text.replace( /([a-z])([A-Z])/g, '$1 $2' );
		// capitalize first letter
		labelData.text = labelData.text.charAt( 0 ).toUpperCase() + labelData.text.slice( 1 );
	}

	return labelData;
};

const wrapFieldInLabel = ( field, labelData ) => {
	const label = document.createElement( 'label' );
	label.classList.add( 'edac-generated-label' );
	// add data attribute to the label showing which attribute the label was generated from
	label.setAttribute( 'data-edac-generated-label-from', labelData.attribute );
	label.innerText = labelData.text;
	field.parentNode.insertBefore( label, field );
	label.appendChild( field );
	field.removeAttribute( labelData.attribute );
};

export default AddLabelToUnlabelledFormFieldsFix;
