import { __ } from '@wordpress/i18n';

const ambiguousPhrases = [
	__( 'click', 'accessibility-checker' ),
	__( 'click here', 'accessibility-checker' ),
	__( 'here', 'accessibility-checker' ),
	__( 'go here', 'accessibility-checker' ),
	__( 'more', 'accessibility-checker' ),
	__( 'more...', 'accessibility-checker' ),
	__( 'more…', 'accessibility-checker' ),
	__( 'details', 'accessibility-checker' ),
	__( 'more details', 'accessibility-checker' ),
	__( 'link', 'accessibility-checker' ),
	__( 'this page', 'accessibility-checker' ),
	__( 'continue', 'accessibility-checker' ),
	__( 'continue reading', 'accessibility-checker' ),
	__( 'read more', 'accessibility-checker' ),
	__( 'open', 'accessibility-checker' ),
	__( 'download', 'accessibility-checker' ),
	__( 'button', 'accessibility-checker' ),
	__( 'keep reading', 'accessibility-checker' ),
	__( 'learn more', 'accessibility-checker' ),
];

const checkAmbiguousPhrase = ( text ) => {
	if ( ! text ) {
		return false;
	}
	text = text.toLowerCase().replace( /[^a-z]+/g, ' ' ).trim();
	return ambiguousPhrases.includes( text );
};

export default {
	id: 'has_ambiguous_text',
	evaluate: ( node ) => {
		if ( node.hasAttribute( 'aria-label' ) ) {
			const ariaLabel = node.getAttribute( 'aria-label' );
			return checkAmbiguousPhrase( ariaLabel );
		}

		if ( node.hasAttribute( 'aria-labelledby' ) ) {
			const label = node.getAttribute( 'aria-labelledby' );
			const labelText = document.getElementById( label )?.textContent;
			return checkAmbiguousPhrase( labelText );
		}

		if ( node.textContent && node.textContent !== '' ) {
			return checkAmbiguousPhrase( node.textContent );
		}

		const images = node.querySelectorAll( 'img' );
		for ( const image of images ) {
			const altText = image.getAttribute( 'alt' );
			if ( checkAmbiguousPhrase( altText ) ) {
				return true;
			}
		}

		return false;
	},
};
