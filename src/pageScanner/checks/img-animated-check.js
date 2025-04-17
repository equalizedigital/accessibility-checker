/**
 * Animation detection for accessibility compliance (WCAG 2.2.2)
 *
 * This module detects animated GIFs and WebP images that may cause
 * accessibility issues for users with certain cognitive disabilities
 * or seizure disorders.
 */

// Animation cache to store pre-scan results
export const animationCache = new Map();

/**
 * Main accessibility check for animated images
 */
export default {
	id: 'img_animated_check',
	evaluate: ( node ) => {
		const nodeName = node.nodeName.toLowerCase();
		const src = node.getAttribute( 'src' ) || '';
		const srcLower = src.toLowerCase();

		// Return cached result if available
		if ( animationCache.has( src ) ) {
			return animationCache.get( src );
		}

		// Check for different node types
		if ( nodeName === 'img' ) {
			// Check all potential animated image patterns
			if ( hasAnimationIndicatorsInName( srcLower ) ||
                isGifService( srcLower ) ||
                isGifUrl( srcLower ) ||
                isWebPUrl( srcLower ) ) {
				animationCache.set( src, true );
				return true;
			}
			return false;
		} else if ( nodeName === 'iframe' ) {
			// For iframes, check if the src belongs to a known GIF service
			const isAnimated = isGifService( srcLower );
			animationCache.set( src, isAnimated );
			return isAnimated;
		}

		return false;
	},
};

/**
 * Pre-scans all images on the page to detect animations
 * This helps improve performance by caching results
 *
 * @return {Map} The animation cache with results
 */
export async function preScanAnimatedImages() {
	const imgElements = document.querySelectorAll( 'img[src]' );

	for ( const img of imgElements ) {
		const src = img.getAttribute( 'src' ) || '';
		const srcLower = src.toLowerCase();

		// Skip if already in cache
		if ( animationCache.has( src ) ) {
			continue;
		}

		// Quick checks first (name patterns and known services)
		if ( hasAnimationIndicatorsInName( srcLower ) || isGifService( srcLower ) ) {
			animationCache.set( src, true );
			continue;
		}

		// More expensive checks for file extensions
		if ( isGifUrl( srcLower ) || isWebPUrl( srcLower ) ) {
			try {
				const response = await fetch( src, { mode: 'cors' } );
				if ( ! response.ok ) {
					// If fetch fails, assume animated only if it has animation indicators
					animationCache.set( src, hasAnimationIndicatorsInName( srcLower ) );
					continue;
				}

				const buffer = await response.arrayBuffer();
				const isAnimated = detectAnimationFromBytes( buffer );
				animationCache.set( src, isAnimated );
			} catch ( error ) {
				// Only mark as animated if the filename explicitly indicates animation
				animationCache.set( src, hasAnimationIndicatorsInName( srcLower ) );
			}
		} else {
			// Not a GIF or WebP - mark as not animated
			animationCache.set( src, false );
		}
	}

	return animationCache;
}

/**
 * Analyzes an ArrayBuffer bytestream to determine if the image is animated.
 *
 * @param {ArrayBuffer} buffer - The image bytestream
 * @return {boolean} - Whether the image is animated
 */
function detectAnimationFromBytes( buffer ) {
	const bytes = new Uint8Array( buffer );

	// Check for GIF animation
	if ( isGifFormat( bytes ) ) {
		return hasMultipleGifFrames( bytes );
	}

	// Check for WebP animation
	if ( isWebPFormat( bytes ) ) {
		return hasWebPAnimation( bytes );
	}

	// Fallback for other types, assume non-animated
	return false;
}

/**
 * Checks if the bytestream has a GIF format signature
 *
 * @param {Uint8Array} bytes - Image bytestream
 * @return {boolean} - Whether it's a GIF format
 */
function isGifFormat( bytes ) {
	const header = String.fromCharCode( ...bytes.slice( 0, 6 ) );
	return header === 'GIF89a' || header === 'GIF87a';
}

/**
 * Counts GIF animation frames to determine if it's animated
 *
 * @param {Uint8Array} bytes - GIF bytestream
 * @return {boolean} - True if more than one frame
 */
function hasMultipleGifFrames( bytes ) {
	let controlCount = 0;
	for ( let i = 0; i < bytes.length - 1; i++ ) {
		if ( bytes[ i ] === 0x21 && bytes[ i + 1 ] === 0xF9 ) {
			controlCount++;
			if ( controlCount > 1 ) {
				return true; // Animated if more than one frame
			}
		}
	}
	return false; // Static GIF if only one frame
}

/**
 * Checks if the bytestream has a WebP format signature
 *
 * @param {Uint8Array} bytes - Image bytestream
 * @return {boolean} - Whether it's a WebP format
 */
function isWebPFormat( bytes ) {
	const riffHeader = String.fromCharCode( ...bytes.slice( 0, 4 ) );
	const webpHeader = String.fromCharCode( ...bytes.slice( 8, 12 ) );
	return riffHeader === 'RIFF' && webpHeader === 'WEBP';
}

/**
 * Detects if a WebP image is animated by looking for the ANIM chunk
 *
 * @param {Uint8Array} bytes - WebP bytestream
 * @return {boolean} - Whether the WebP is animated
 */
function hasWebPAnimation( bytes ) {
	const text = String.fromCharCode( ...bytes );
	return text.includes( 'ANIM' );
}

/**
 * Checks if a URL points to a GIF image based on extension and parameters
 *
 * @param {string} srcLower - Lowercase source URL
 * @return {boolean} - Whether it's likely a GIF image
 */
const isGifUrl = ( srcLower ) => {
	return srcLower.endsWith( '.gif' ) ||
           srcLower.includes( '.gif?' ) ||
           srcLower.includes( '.gif#' ) ||
           srcLower.endsWith( '%2egif' ) ||
           srcLower.includes( '%2egif?' ) ||
           srcLower.includes( '%2egif#' ) ||
           srcLower.includes( 'format=gif' ) ||
           srcLower.includes( 'type=gif' ) ||
           srcLower.includes( 'filetype=gif' ) ||
           srcLower.startsWith( 'data:image/gif' );
};

/**
 * Checks if a URL points to a WebP image based on extension and parameters
 *
 * @param {string} srcLower - Lowercase source URL
 * @return {boolean} - Whether it's likely a WebP image
 */
const isWebPUrl = ( srcLower ) => {
	return srcLower.endsWith( '.webp' ) ||
           srcLower.includes( '.webp?' ) ||
           srcLower.includes( '.webp#' ) ||
           srcLower.endsWith( '%2ewebp' ) ||
           srcLower.includes( '%2ewebp?' ) ||
           srcLower.includes( '%2ewebp#' ) ||
           srcLower.includes( 'format=webp' ) ||
           srcLower.includes( 'type=webp' ) ||
           srcLower.includes( 'filetype=webp' ) ||
           srcLower.startsWith( 'data:image/webp' );
};

/**
 * Determines if the image URL belongs to a known GIF service
 *
 * @param {string} srcLower - Lowercase source URL
 * @return {boolean} - Whether the URL is from a known GIF service
 */
const isGifService = ( srcLower ) => {
	const knownServices = [
		'giphy.com',
		'tenor.com',
		'gfycat.com',
		'imgur.com/a/', // Imgur album/animation
		'media.discordapp.net', // Discord often hosts GIFs
	];

	return knownServices.some( ( service ) => srcLower.includes( service ) );
};

/**
 * Checks if filename contains keywords suggesting animation
 *
 * @param {string} srcLower - Lowercase source URL
 * @return {boolean} - Whether the name suggests animation
 */
const hasAnimationIndicatorsInName = ( srcLower ) => {
	const animationKeywords = [
		'animate', 'animation', 'spinner', 'loading',
		'rotating', 'rotation', 'moving', 'blink', 'flashing',
	];

	return animationKeywords.some( ( keyword ) => srcLower.includes( keyword ) );
};

