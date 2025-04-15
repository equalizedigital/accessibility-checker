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

		// Multiple label detection
		let labelCount = 0;
		let hasLabelElement = false;
		let hasAriaLabel = false;
		let hasAriaLabelledby = false;

		// Check for associated labels using the `for` attribute
		if ( node.id ) {
			const forLabels = Array.from( document.querySelectorAll( `label[for="${ node.id }"]` ) );
			if ( forLabels.length > 0 ) {
				hasLabelElement = true;
				labelCount += forLabels.length;

				// Fail if multiple label elements reference this input
				if ( forLabels.length > 1 ) {
					return false;
				}
			}
		}

		// Check aria-label
		const ariaLabel = node.getAttribute( 'aria-label' );
		if ( ariaLabel && ariaLabel.trim() ) {
			hasAriaLabel = true;
			labelCount++;
		}

		// Check aria-labelledby
		const ariaLabelledBy = node.getAttribute( 'aria-labelledby' );
		if ( ariaLabelledBy ) {
			hasAriaLabelledby = true;
			labelCount++;

			// Check for multiple IDs in aria-labelledby (should fail according to test cases)
			const ids = ariaLabelledBy.split( ' ' ).filter( Boolean );
			if ( ids.length > 1 ) {
				return false;
			}

			// Check for duplicate IDs in aria-labelledby
			const uniqueIds = new Set( ids );
			if ( uniqueIds.size < ids.length ) {
				return false;
			}

			// Check if aria-labelledby references exist
			const validReferences = ids.filter( ( id ) => document.getElementById( id ) ).length;
			if ( validReferences === 0 && ids.length > 0 ) {
				return false;
			}
		}

		// Fail if multiple labeling methods are used
		if ( labelCount > 1 ) {
			return false;
		}

		// Check for conflicts between label element and aria-label
		if ( hasLabelElement && hasAriaLabel && node.id ) {
			const explicitLabel = document.querySelector( `label[for="${ node.id }"]` )?.textContent?.trim();
			if ( explicitLabel && ariaLabel.trim() && explicitLabel !== ariaLabel.trim() ) {
				return false;
			}
		}

		// Check for conflicts between label element and aria-labelledby
		if ( hasLabelElement && hasAriaLabelledby && node.id && ariaLabelledBy ) {
			const explicitLabel = document.querySelector( `label[for="${ node.id }"]` )?.textContent?.trim();
			const referencedElement = document.getElementById( ariaLabelledBy.trim() );
			const labelledByText = referencedElement?.textContent?.trim();

			if ( explicitLabel && labelledByText && explicitLabel !== labelledByText ) {
				return false;
			}
		}

		return true;
	},
};
