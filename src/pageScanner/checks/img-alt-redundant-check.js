import { normalizeText } from '../helpers/helpers';

// Global cache for alt text mapping
export const altTextMap = new Map();

/**
 * Initialize the alt text map if it's empty.
 * Maps normalized alt text to arrays of images with that text.
 */
export function initializeAltTextMap() {
	if ( altTextMap.size === 0 ) {
		const allImages = document.querySelectorAll( 'img' );
		allImages.forEach( ( img ) => {
			const imgAlt = normalizeText( img.getAttribute( 'alt' ) );
			if ( imgAlt ) {
				if ( ! altTextMap.has( imgAlt ) ) {
					altTextMap.set( imgAlt, [] );
				}
				altTextMap.get( imgAlt ).push( img );
			}
		} );
	}
}

/**
 * Check if an image's alternative text is redundant.
 * Returns false if redundancy is detected.
 */
export default {
	id: 'img_alt_redundant_check',
	evaluate( node ) {
		// Get the alt text (normalized)
		const alt = normalizeText( node.getAttribute( 'alt' ) );
		if ( ! alt ) {
			// If there is no alt, this check doesn't apply.
			return true;
		}

		// Check if alt text matches title attribute
		const title = normalizeText( node.getAttribute( 'title' ) );
		if ( title && alt === title ) {
			return false;
		}

		// Check image inside a link whose text equals alt text
		const parentLink = node.closest( 'a' );
		if ( parentLink ) {
			// Get visible text of the anchor.
			const linkText = normalizeText( parentLink.textContent );
			if ( linkText && alt === linkText ) {
				return false;
			}
		}

		// Check image inside a figure with figcaption matching alt text
		const figure = node.closest( 'figure' );
		if ( figure ) {
			const figcaption = figure.querySelector( 'figcaption' );
			if ( figcaption ) {
				const captionText = normalizeText( figcaption.textContent );
				if ( captionText && alt === captionText ) {
					return false;
				}
			}
		}

		// Make sure the alt text map is available and filled.
		initializeAltTextMap();

		// Check if current alt text appears in multiple images.
		// If duplicates exist, only flag when those images use a different
		// source and link destination. Repeated use of the exact same
		// image or link should not be considered redundant.
		if ( altTextMap.has( alt ) ) {
			const matches = altTextMap
				.get( alt )
				.filter( ( img ) => img !== node );

			if ( matches.length > 0 ) {
				const nodeSrc = node.getAttribute( 'src' );
				const nodeHref = node.closest( 'a' )?.getAttribute( 'href' );

				const problematic = matches.filter( ( img ) => {
					const src = img.getAttribute( 'src' );
					const href = img.closest( 'a' )?.getAttribute( 'href' );

					const sameSrc = src === nodeSrc;
					const sameHref = nodeHref && href && href === nodeHref;

					return ! sameSrc && ! sameHref;
				} );

				if ( problematic.length > 0 ) {
					return false;
				}
			}
		}

		// If no redundant text found, pass.
		return true;
	},
	options: {},
	metadata: {
		impact: 'warning',
		messages: {
			pass: 'Image alternative text is not redundant.',
			fail: 'Image alternative text is redundant (matches title, link text, caption, or is duplicated).',
		},
	},
};
