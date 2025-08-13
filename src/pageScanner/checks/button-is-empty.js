export default {
	id: 'button_is_empty',
	evaluate( node ) {
		// Get all aria-hidden and visually hidden elements
		const getVisibleTextContent = ( element ) => {
			const hiddenTexts = [];

			// Get aria-hidden content
			element.querySelectorAll( '[aria-hidden="true"]' ).forEach( ( el ) => {
				hiddenTexts.push( el.cloneNode( true ).textContent );
			} );

			// Get visually hidden content
			Array.from( element.getElementsByTagName( '*' ) ).forEach( ( el ) => {
				const style = window.getComputedStyle( el );
				if ( style.display === 'none' || style.visibility === 'hidden' ) {
					hiddenTexts.push( el.textContent );
				}
			} );

			// Remove all hidden content from text
			let text = element.textContent;
			hiddenTexts.forEach( ( hiddenText ) => {
				text = text.replace( hiddenText, '' );
			} );

			return text.trim();
		};

		// Check for visible text content (excluding hidden content)
		const textContent = getVisibleTextContent( node );
		if ( textContent && textContent.length > 0 ) {
			return false;
		}

		// Check for aria-label or title attributes
		const ariaLabel = node.getAttribute( 'aria-label' );
		const ariaDescription = node.getAttribute( 'aria-description' );
		const title = node.getAttribute( 'title' );
		if ( ariaLabel || ariaDescription || title ) {
			return false;
		}

		// Check for aria-labelledby attribute
		const ariaLabelledby = node.getAttribute( 'aria-labelledby' );
		if ( ariaLabelledby ) {
			const ids = ariaLabelledby.split( /\s+/ );
			for ( const id of ids ) {
				const labelElement = document.getElementById( id );
				if ( labelElement?.textContent?.trim() ) {
					return false;
				}
			}
		}

		// Check for aria-describedby attribute
		const ariaDescribedby = node.getAttribute( 'aria-describedby' );
		if ( ariaDescribedby ) {
			const ids = ariaDescribedby.split( /\s+/ );
			for ( const id of ids ) {
				const descElement = document.getElementById( id );
				if ( descElement?.textContent?.trim() ) {
					return false;
				}
			}
		}

		// Check for an image with alt text
		const images = node.querySelectorAll( 'img' );
		for ( const img of images ) {
			if ( img.getAttribute( 'alt' )?.trim() ) {
				return false;
			}
		}

		// Check for input with a value attribute
		if ( node.tagName.toLowerCase() === 'input' && node.getAttribute( 'value' ) ) {
			return false;
		}

		// Check for <i> elements with aria-label or title
		const icons = node.querySelectorAll( 'i' );
		for ( const icon of icons ) {
			if (
				icon.getAttribute( 'title' )?.trim() ||
				icon.getAttribute( 'aria-label' )?.trim() ||
				icon.getAttribute( 'aria-description' )?.trim()
			) {
				return false;
			}
		}

		// Check for SVG elements with title
		const svgs = node.querySelectorAll( 'svg' );
		for ( const svg of svgs ) {
			if (
				svg.querySelector( 'title' )?.textContent?.trim() ||
				svg.getAttribute( 'aria-label' )?.trim() ||
				svg.getAttribute( 'aria-description' )?.trim()
			) {
				return false;
			}
		}

		// If none of the above checks pass, the button is empty
		return true;
	},
};
