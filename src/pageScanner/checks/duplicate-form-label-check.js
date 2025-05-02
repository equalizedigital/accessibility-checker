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
				labelCount++;

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

		// Enhanced aria-labelledby checking
		const ariaLabelledBy = node.getAttribute( 'aria-labelledby' );
		if ( ariaLabelledBy ) {
			hasAriaLabelledby = true;
			labelCount++;
		}

		// Fail if multiple labeling methods are used
		if ( labelCount > 1 ) {
			return false;
		}

		// Only check for conflicts if we have at least one label
		if ( labelCount === 0 ) {
			return true;
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
