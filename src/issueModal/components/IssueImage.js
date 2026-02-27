/**
 * Issue Image Component
 *
 * Extracts and displays images found in HTML markup fragments.
 */

import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Extract image URLs from an HTML markup string
 *
 * @param {string} markup - HTML markup string to search for images.
 * @return {Array} Array of image URLs found in the markup.
 */
const extractImageUrls = ( markup ) => {
	if ( ! markup ) {
		return [];
	}

	const urls = [];
	const decodedMarkup = decodeEntities( markup );

	// Match src attributes from img tags
	const imgSrcRegex = /<img[^>]+src=["']([^"']+)["']/gi;
	let match;
	while ( ( match = imgSrcRegex.exec( decodedMarkup ) ) !== null ) {
		if ( match[ 1 ] ) {
			urls.push( match[ 1 ] );
		}
	}

	// Match srcset attributes from img tags (get first URL from srcset)
	const srcsetRegex = /<img[^>]+srcset=["']([^"']+)["']/gi;
	while ( ( match = srcsetRegex.exec( decodedMarkup ) ) !== null ) {
		if ( match[ 1 ] ) {
			// srcset contains multiple URLs with sizes, get the first one
			const firstUrl = match[ 1 ].split( ',' )[ 0 ].trim().split( ' ' )[ 0 ];
			if ( firstUrl && ! urls.includes( firstUrl ) ) {
				urls.push( firstUrl );
			}
		}
	}

	// Match background-image URLs in style attributes
	const bgImageRegex = /background(?:-image)?:\s*url\(["']?([^"')]+)["']?\)/gi;
	while ( ( match = bgImageRegex.exec( decodedMarkup ) ) !== null ) {
		if ( match[ 1 ] && ! urls.includes( match[ 1 ] ) ) {
			urls.push( match[ 1 ] );
		}
	}

	// Match data-src attributes (common for lazy-loaded images)
	const dataSrcRegex = /data-src=["']([^"']+)["']/gi;
	while ( ( match = dataSrcRegex.exec( decodedMarkup ) ) !== null ) {
		if ( match[ 1 ] && ! urls.includes( match[ 1 ] ) ) {
			urls.push( match[ 1 ] );
		}
	}

	// Match inline <svg> elements and convert to data URIs
	const svgRegex = /<svg[\s\S]*?<\/svg>/gi;
	while ( ( match = svgRegex.exec( decodedMarkup ) ) !== null ) {
		if ( match[ 0 ] ) {
			const dataUri = 'data:image/svg+xml,' + encodeURIComponent( match[ 0 ] );
			urls.push( dataUri );
		}
	}

	return urls;
};

/**
 * Issue Image component
 *
 * Displays images extracted from HTML markup.
 *
 * @param {Object} props        - Component props.
 * @param {string} props.markup - HTML markup to extract images from.
 * @param {string} props.alt    - Alt text for the images.
 */
const IssueImage = ( { markup, alt = '' } ) => {
	const imageUrls = useMemo( () => extractImageUrls( markup ), [ markup ] );

	if ( imageUrls.length === 0 ) {
		return null;
	}

	return (
		<div className="edac-analysis__issue-image">
			{ imageUrls.map( ( url, index ) => (
				<img
					key={ index }
					src={ url }
					alt={ alt || __( 'Issue related image', 'accessibility-checker' ) }
					className="edac-analysis__issue-image-item"
					onError={ ( e ) => {
						// Hide image if it fails to load
						e.target.style.display = 'none';
					} }
				/>
			) ) }
		</div>
	);
};

export default IssueImage;
export { extractImageUrls };
