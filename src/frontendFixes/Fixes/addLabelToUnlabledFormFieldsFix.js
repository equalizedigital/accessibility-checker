const AddLabelToUnlabledFormFieldsFixData = window.edac_frontend_fixes?.add_label_to_unlabeled_form_fields || {
	enabled: false,
};

const AddLabelToUnlabledFormFieldsFix = () => {
	if ( ! AddLabelToUnlabledFormFieldsFixData.enabled ) {
		return;
	}

	// find all form fields on the page
	const formFields = document.querySelectorAll( 'input:not([type="submit"]), select, textarea' );

	// loop through each form field
	formFields.forEach( ( field ) => {
		// If the field is hidden, skip it.
		if ( field.offsetParent === null ) {
			return;
		}

		// If the field is already inside a label, don't add another label.
		if ( field.parentNode.tagName.toLowerCase() === 'label' ) {
			return;
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

export default AddLabelToUnlabledFormFieldsFix;
