export default {
	id: 'heading_is_empty',
	evaluate( node ) {
		// Get all aria-hidden elements
		const hiddenElements = node.querySelectorAll( '[aria-hidden="true"]' );

		// Clone node to work with
		const clone = node.cloneNode( true );

		// Remove aria-hidden elements from clone
		hiddenElements.forEach( ( el ) => {
			const elementToRemove = Array.from( clone.querySelectorAll( '*' ) ).find( ( cloneEl ) =>
				// Find the corresponding element in the clone by comparing content and structure
				cloneEl.isEqualNode( el )
			);
			if ( elementToRemove ) {
				elementToRemove.remove();
			}
		} );

		// Check for visible text content (excluding just whitespace, hyphens, underscores)
		const headingText = clone.textContent.trim();
		const hasValidText = headingText && ! /^[-_\s]*$/.test( headingText );

		// Check for aria-label
		const ariaLabel = node.getAttribute( 'aria-label' );
		const hasAriaLabel = ariaLabel && ariaLabel.trim() !== '';

		// Check for images with alt text
		const images = node.querySelectorAll( 'img' );
		let hasImageWithAlt = false;
		for ( let i = 0; i < images.length; i++ ) {
			const alt = images[ i ].getAttribute( 'alt' );
			if ( alt && alt.trim() !== '' ) {
				hasImageWithAlt = true;
				break;
			}
		}

		// Check for SVG with title or aria-label
		const svgs = node.querySelectorAll( 'svg' );
		let hasSvgWithAccessibleText = false;
		for ( let i = 0; i < svgs.length; i++ ) {
			const title = svgs[ i ].querySelector( 'title' );
			if ( title && title.textContent.trim() !== '' ) {
				hasSvgWithAccessibleText = true;
				break;
			}
			// Also check for aria-label on SVG
			const svgAriaLabel = svgs[ i ].getAttribute( 'aria-label' );
			if ( svgAriaLabel && svgAriaLabel.trim() !== '' ) {
				hasSvgWithAccessibleText = true;
				break;
			}
		}

		// Check aria-labelledby
		const ariaLabelledby = node.getAttribute( 'aria-labelledby' );
		let hasAriaLabelledby = false;
		if ( ariaLabelledby ) {
			const ids = ariaLabelledby.split( /\s+/ );
			for ( let i = 0; i < ids.length; i++ ) {
				const labelElement = document.getElementById( ids[ i ] );
				if ( labelElement && labelElement.textContent.trim() !== '' ) {
					hasAriaLabelledby = true;
					break;
				}
			}
		}

		return hasValidText || hasAriaLabel || hasImageWithAlt || hasSvgWithAccessibleText || hasAriaLabelledby;
	},
};
