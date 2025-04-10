export default {
	id: 'table_header_is_empty',
	evaluate( node ) {
		// Check if the table header has text content after stripping spaces, &nbsp;, hyphens, emdashes, underscores
		const textContent = node.textContent.replace( /[\s\u00A0\-—_]/g, '' );
		if ( textContent !== '' ) {
			return false;
		}

		// Check for aria-label or title on the table header itself
		if ( node.hasAttribute( 'aria-label' ) && node.getAttribute( 'aria-label' )?.trim() !== '' ) {
			return false;
		}

		if ( node.hasAttribute( 'title' ) && node.getAttribute( 'title' )?.trim() !== '' ) {
			return false;
		}

		// Check aria-labelledby
		if ( node.hasAttribute( 'aria-labelledby' ) ) {
			const labelledbyIds = node.getAttribute( 'aria-labelledby' )?.split( ' ' );
			const labelledbyElements = labelledbyIds?.map( ( id ) => document.getElementById( id ) ).filter( Boolean );
			if ( labelledbyElements.some( ( el ) => el.textContent?.trim() !== '' ) ) {
				return false;
			}
		}

		// Check for images with alt text
		const images = node.querySelectorAll( 'img' );
		for ( const img of images ) {
			if ( img.hasAttribute( 'alt' ) && img.getAttribute( 'alt' )?.trim() !== '' ) {
				return false;
			}
		}

		// Check for icons with title or aria-label
		const icons = node.querySelectorAll( 'i' );
		for ( const icon of icons ) {
			if (
				( icon.hasAttribute( 'title' ) && icon.getAttribute( 'title' )?.trim() !== '' ) ||
				( icon.hasAttribute( 'aria-label' ) && icon.getAttribute( 'aria-label' )?.trim() !== '' )
			) {
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

		// If we've reached here, the table header is empty
		return true;
	},
};
