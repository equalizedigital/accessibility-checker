export default {
	id: 'button_is_empty',
	evaluate( node ) {
		// Check for visible text content
		const textContent = node.textContent.trim();
		// if we have text content and that text content is not just an emoji.
		if ( textContent && textContent.length > 0 && ! /^[\p{Emoji}\s]+$/u.test( textContent ) ) {
			return false;
		}

		// Check for aria-label or title attributes
		const ariaLabel = node.getAttribute( 'aria-label' );
		const title = node.getAttribute( 'title' );
		if ( ariaLabel || title ) {
			return false;
		}

		// Check for aria-labelledby attribute
		const ariaLabelledby = node.getAttribute( 'aria-labelledby' );
		if ( ariaLabelledby ) {
			const ids = ariaLabelledby.split( /\s+/ );
			for ( const id of ids ) {
				const labelElement = document.getElementById( id );
				if ( labelElement && labelElement.textContent.trim() ) {
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
			if ( icon.getAttribute( 'aria-label' )?.trim() || icon.getAttribute( 'title' )?.trim() ) {
				return false;
			}
		}

		// If none of the above checks pass, the button is empty
		return true;
	},
};
