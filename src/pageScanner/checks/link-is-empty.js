export default {
	id: 'link-is-empty',
	evaluate( node ) {
		// Skip if the link has no href attribute
		if ( ! node.hasAttribute( 'href' ) ) {
			return false;
		}

		// Skip if there is a name attribute
		if ( node.hasAttribute( 'name' ) ) {
			return false;
		}

		// Check if the link has text content after stripping spaces, &nbsp;, hyphens, emdashes, underscores
		const textContent = node.textContent.replace( /[\s\u00A0\-—_]/g, '' );
		if ( textContent !== '' ) {
			return false;
		}

		// Check for aria-label or title on the link itself
		if ( node.hasAttribute( 'aria-label' ) && node.getAttribute( 'aria-label' ).trim() !== '' ) {
			return false;
		}

		if ( node.hasAttribute( 'title' ) && node.getAttribute( 'title' ).trim() !== '' ) {
			return false;
		}

		// Check aria-labelledby
		if ( node.hasAttribute( 'aria-labelledby' ) ) {
			const labelledbyIds = node.getAttribute( 'aria-labelledby' ).split( ' ' );
			const labelledbyElements = labelledbyIds.map( ( id ) => document.getElementById( id ) ).filter( Boolean );
			if ( labelledbyElements.some( ( el ) => el.textContent.trim() !== '' ) ) {
				return false;
			}
		}

		// Check for images with alt text
		const images = node.querySelectorAll( 'img' );
		for ( const img of images ) {
			if ( img.hasAttribute( 'alt' ) && img.getAttribute( 'alt' ).trim() !== '' ) {
				return false;
			}
		}

		// Check for inputs with value
		const inputs = node.querySelectorAll( 'input' );
		for ( const input of inputs ) {
			if ( input.hasAttribute( 'value' ) && input.getAttribute( 'value' ).trim() !== '' ) {
				return false;
			}
		}

		// Check for icons with title or aria-label
		const icons = node.querySelectorAll( 'i' );
		for ( const icon of icons ) {
			if ( ( icon.hasAttribute( 'title' ) && icon.getAttribute( 'title' ).trim() !== '' ) ||
          ( icon.hasAttribute( 'aria-label' ) && icon.getAttribute( 'aria-label' ).trim() !== '' ) ) {
				return false;
			}
		}

		// Check for SVGs with title
		const svgs = node.querySelectorAll( 'svg' );
		for ( const svg of svgs ) {
			if ( svg.querySelector( 'title' ) ) {
				return false;
			}
		}

		// If we've reached here, the link is empty
		return true;
	},
};
