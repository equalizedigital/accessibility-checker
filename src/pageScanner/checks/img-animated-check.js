import { NormalizedMap } from '../helpers/helpers.js';

/**
 * Animation detection for accessibility compliance (WCAG 2.2.2)
 *
 * This module detects animated GIFs and WebP images that may cause
 * accessibility issues for users with certain cognitive disabilities
 * or seizure disorders.
 */

// Animation cache to store pre-scan results
export const animationCache = new NormalizedMap();

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
		if ( animationCache.has( srcLower ) ) {
			return animationCache.get( srcLower );
		}

		// For iframes, only check if it's from a known GIF service
		if ( nodeName === 'iframe' ) {
			const isAnimated = isGifService( srcLower );
			animationCache.set( srcLower, isAnimated );
			return isAnimated;
		}

		// For images, we need more careful checking
		if ( nodeName === 'img' ) {
			// Only consider it potentially animated if it's a GIF/WebP
			if ( ! isGifUrl( srcLower ) && ! isWebPUrl( srcLower ) ) {
				animationCache.set( srcLower, false );
				return false;
			}

			// Do a final gif service check.
			if ( isGifService( srcLower ) ) {
				animationCache.set( srcLower, true );
				return true;
			}

			// If we haven't confirmed animation, default to false
			animationCache.set( srcLower, false );
			return false;
		}

		return false;
	},
};

/**
 * Pre-scans all images on the page to detect animations
 * This helps improve performance by caching results
 *
 * @param {number} timeoutMs - The timeout in milliseconds for fetch requests
 * @return {Map} The animation cache with results
 */
export async function preScanAnimatedImages( timeoutMs = 5000 ) {
	const imgElements = document.querySelectorAll( 'img[src]' );

	for ( const img of imgElements ) {
		const src = img.getAttribute( 'src' ) || '';
		const srcLower = src.toLowerCase();

		// Skip if already in cache
		if ( animationCache.has( srcLower ) ) {
			continue;
		}

		// For GIFs and WebPs, we need to check the actual bytes
		if ( isGifUrl( srcLower ) || isWebPUrl( srcLower ) ) {
			try {
				const controller = new AbortController();
				const timeoutId = setTimeout( () => controller.abort(), timeoutMs );
				let response;
				try {
					response = await fetch(
						src,
						{
							mode: 'cors',
							signal: controller.signal,
						}
					);
				} finally {
					clearTimeout( timeoutId );
				}

				if ( response.ok ) {
					const buffer = await response.arrayBuffer();
					const isAnimated = detectAnimationFromBytes( buffer );
					animationCache.set( srcLower, isAnimated );
					continue;
				}
			} catch ( error ) {
				// On error, only flag as animated if from known service or has animation indicators
				animationCache.set( srcLower, isGifService( srcLower ) );
				continue;
			}
		}

		// For non-GIF/WebP or failed fetches, use heuristics
		animationCache.set( srcLower, isGifService( srcLower ) );
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
 * Gets color table flag from packed byte
 * @param {number} byte - The packed byte
 * @return {boolean} - Whether color table is present
 */
function hasColorTable( byte ) {
	return Math.floor( byte / 128 ) === 1; // Replace (byte & 0x80) !== 0
}

/**
 * Gets color table size from packed byte
 * @param {number} byte - The packed byte
 * @return {number} - Size bits
 */
function getColorBits( byte ) {
	return byte % 8; // Replace (byte & 0x07)
}

/**
 * Counts GIF animation frames to determine if it's animated
 *
 * @param {Uint8Array} bytes - GIF bytestream
 * @return {boolean} - True if more than one animation frame
 */
function hasMultipleGifFrames( bytes ) {
	let pos = 13; // Skip header and logical screen descriptor
	let graphicExtensions = 0;

	// Skip global color table if present
	if ( hasColorTable( bytes[ 10 ] ) ) {
		const colorBits = getColorBits( bytes[ 10 ] );
		const colorTableSize = 3 * Math.pow( 2, colorBits + 1 );
		pos += colorTableSize;
	}

	while ( pos < bytes.length ) {
		const block = bytes[ pos ];

		if ( block === 0x21 ) { // Extension Block
			const extType = bytes[ pos + 1 ];
			if ( extType === 0xF9 ) { // Graphics Control Extension
				graphicExtensions++;
				if ( graphicExtensions > 1 ) {
					return true; // Multiple graphics controls = animated
				}
			}
			pos += 2;
			// Skip sub-blocks
			let subBlockSize = bytes[ pos ];
			while ( subBlockSize !== 0 ) {
				pos += subBlockSize + 1;
				subBlockSize = bytes[ pos ];
			}
			pos++;
		} else if ( block === 0x2C ) { // Image Descriptor
			// Skip image descriptor
			pos += 10;
			// Skip local color table if present
			if ( hasColorTable( bytes[ pos - 1 ] ) ) {
				const colorBits = getColorBits( bytes[ pos - 1 ] );
				const colorTableSize = 3 * Math.pow( 2, colorBits + 1 );
				pos += colorTableSize;
			}
			// Skip image data
			pos++; // LZW minimum code size
			// Skip sub-blocks
			let subBlockSize = bytes[ pos ];
			while ( subBlockSize !== 0 ) {
				pos += subBlockSize + 1;
				subBlockSize = bytes[ pos ];
			}
			pos++;
		} else if ( block === 0x3B ) { // Trailer
			break;
		} else {
			pos++;
		}
	}

	return false; // No animation blocks found
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
	// Search for 'ANIM' chunk in WebP bytes
	for ( let i = 12; i < bytes.length - 4; i++ ) {
		if (
			bytes[ i ] === 0x41 && // 'A'
			bytes[ i + 1 ] === 0x4E && // 'N'
			bytes[ i + 2 ] === 0x49 && // 'I'
			bytes[ i + 3 ] === 0x4D // 'M'
		) {
			return true;
		}
	}
	return false;
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
