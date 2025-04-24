/* globals global, __dirname */
import fs from 'fs';
import path from 'path';
import axe from 'axe-core';
import imgAnimatedCheckDefault, {
	preScanAnimatedImages,
	animationCache,
} from '../../../src/pageScanner/checks/img-animated-check';

beforeAll( async () => {
	// Dynamically import the modules
	const imgAnimatedRuleModule = await import( '../../../src/pageScanner/rules/img-animated.js' );
	const imgAnimatedRule = imgAnimatedRuleModule.default;

	// Configure axe with the imported rule and our check
	axe.configure( {
		rules: [ imgAnimatedRule ],
		checks: [ imgAnimatedCheckDefault ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';
	animationCache.clear();

	// Enhanced fetch mock to handle different URL formats
	global.fetch = jest.fn( async ( url ) => {
		// Extract the base filename regardless of URL format
		let filename;
		try {
			let formatFromQuery = null;

			// Handle absolute URLs by stripping domain
			if ( url.includes( '://' ) ) {
				const urlObj = new URL( url );

				// Check for format in query params
				if ( urlObj.searchParams.has( 'format' ) ) {
					formatFromQuery = urlObj.searchParams.get( 'format' );
				}

				// Get path without domain
				filename = path.basename( urlObj.pathname );
			} else {
				// Handle relative URLs
				const urlParts = url.split( '?' );
				const urlPath = urlParts[ 0 ].split( '#' )[ 0 ];
				filename = path.basename( urlPath );

				// Check for format in query string if present
				if ( urlParts.length > 1 ) {
					const queryMatch = urlParts[ 1 ].match( /format=([^&]+)/ );
					if ( queryMatch ) {
						formatFromQuery = queryMatch[ 1 ];
					}
				}
			}

			// If we have a format specified in query params, modify filename
			if ( formatFromQuery ) {
				// Replace extension or add one if none exists
				if ( filename.includes( '.' ) ) {
					filename = filename.replace( /\.[^.]+$/, `.${ formatFromQuery }` );
				} else {
					filename = `${ filename }.${ formatFromQuery }`;
				}
			}

			// If filename is empty, use a default name for testing
			if ( ! filename ) {
				filename = 'default-test-image.gif';
			}

			const filePath = path.resolve( __dirname, '..', 'mock-assets', filename );

			if ( ! fs.existsSync( filePath ) ) {
				return Promise.resolve( {
					ok: false,
					status: 404,
					statusText: 'Not Found',
				} );
			}

			const buffer = fs.readFileSync( filePath );
			return {
				ok: true,
				status: 200,
				arrayBuffer: async () => buffer,
				blob: async () => new Blob( [ buffer ] ),
			};
		} catch ( error ) {
			return Promise.resolve( {
				ok: false,
				status: 500,
				statusText: 'Internal Error',
			} );
		}
	} );
} );

afterEach( () => {
	jest.restoreAllMocks();
} );

describe( 'img_animated rule detection', () => {
	// Group 1: Basic file extension detection
	const basicCases = [
		{
			name: 'detects animated GIF image',
			html: '<img src="A-image.gif" alt="Animated GIF">',
			shouldPass: false,
		},
		{
			name: 'detects animated WebP image',
			html: '<img src="A-image.webp" alt="Animated WebP">',
			shouldPass: false,
		},
		{
			name: 'does not detect substring match of gif',
			html: '<img src="S-image.jpg" alt="Gift box">',
			shouldPass: true,
		},
	];

	// Group 2: File naming pattern detection
	const namingPatternCases = [
		{
			name: 'does not detect GIF with animation indicators in filename',
			html: '<img src="S-animated-in-name.gif" alt="Loading animation">',
			shouldPass: true,
		},
		{
			name: 'does not detect  WebP with animation indicators in filename',
			html: '<img src="S-animated-in-name.webp" alt="Animated banner">',
			shouldPass: true,
		},
	];

	// Group 3: Service detection
	const serviceDetectionCases = [
		{
			name: 'detects images from Giphy',
			html: '<img src="https://media.giphy.com/media/example.gif" alt="GIPHY animation">',
			shouldPass: false,
		},
		{
			name: 'detects images from Tenor',
			html: '<img src="https://media.tenor.com/abc123/example.gif" alt="Tenor animation">',
			shouldPass: false,
		},
		{
			name: 'detects images from Gfycat',
			html: '<img src="https://thumbs.gfycat.com/example.gif" alt="Gfycat animation">',
			shouldPass: false,
		},
	];

	// Group 4: URL parameter detection
	const urlParameterCases = [
		{
			name: 'detects animated GIF URL with query parameters',
			html: '<img src="A-image.gif?size=large" alt="GIF with query">',
			shouldPass: false,
		},
		{
			name: 'detects GIF URL with hash',
			html: '<img src="A-image.gif#section1" alt="GIF with hash">',
			shouldPass: false,
		},
		{
			name: 'detects URL with format=gif parameter',
			html: '<img src="https://example.com/A-image?format=gif" alt="Dynamic GIF">',
			shouldPass: false,
		},
	];

	// Group 5: Iframe tests
	const iframeCases = [
		{
			name: 'detects Giphy embedded in iframe',
			html: '<iframe src="https://giphy.com/embed/12345" title="Giphy animation"></iframe>',
			shouldPass: false,
		},
		{
			name: 'ignores regular iframe',
			html: '<iframe src="https://example.com/frame" title="Regular iframe"></iframe>',
			shouldPass: true,
		},
	];

	// Group 6: Edge cases
	const edgeCases = [
		{
			name: 'handles missing image gracefully',
			html: '<img src="nonexistent.gif" alt="Missing image">',
			shouldPass: true, // We give benefit of doubt for missing images
		},
		{
			name: 'handles empty src attribute',
			html: '<img src="" alt="Empty src">',
			shouldPass: true,
		},
	];

	// Run all test groups
	const allTestCases = [
		...basicCases,
		...namingPatternCases,
		...serviceDetectionCases,
		...urlParameterCases,
		...iframeCases,
		...edgeCases,
	];

	allTestCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			document.body.innerHTML = testCase.html;

			// Use the real preScan implementation
			await preScanAnimatedImages();

			const results = await axe.run( document.body, {
				runOnly: [ 'img_animated' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
			}
		} );
	} );
} );

// Test individual utility functions
describe( 'Animation detection utilities', () => {
	test( 'Cache stores and retrieves results correctly', async () => {
		document.body.innerHTML = '<img src="test.gif" alt="Test">';

		// Prime the cache
		animationCache.set( 'test.gif', true );

		// Run the check
		await preScanAnimatedImages();

		// The check should use cached value
		expect( animationCache.get( 'test.gif' ) ).toBe( true );

		// Fetch should not be called for cached items
		expect( global.fetch ).not.toHaveBeenCalledWith( 'test.gif' );
	} );

	test( 'Multiple images in a single scan', async () => {
		document.body.innerHTML = `
            <img src="test1.gif" alt="Test 1">
            <img src="test2.webp" alt="Test 2">
            <img src="test3.jpg" alt="Test 3">
        `;

		await preScanAnimatedImages();

		// Check that all images were processed
		expect( animationCache.size ).toBe( 3 );
	} );
} );

// Add a specific test for the fetch mock itself
describe( 'Fetch mock functioning', () => {
	test( 'Fetch mock correctly processes test files', async () => {
		// Test with a simple filename
		let response = await fetch( 'test-image.gif' );
		expect( response.ok ).toBeDefined();

		// Test with query parameters
		response = await fetch( 'test-image.gif?size=large#fragment' );
		expect( response.ok ).toBeDefined();

		// Test with an absolute URL
		response = await fetch( 'https://example.com/test-image.gif' );
		expect( response.ok ).toBeDefined();
	} );
} );

describe( 'Timeout behavior in preScanAnimatedImages', () => {
	test( 'times out after wait time in fetch', async () => {
		document.body.innerHTML = '<img src="A-image.gif" alt="slow image">';

		// Override fetch to simulate a connection slower than the precache wait time.
		global.fetch = jest.fn( ( url, options ) => {
			return new Promise( ( resolve, reject ) => {
				const timer = setTimeout( () => {
					try {
						const filePath = path.resolve( __dirname, '..', 'mock-assets', path.basename( url ) );
						if ( fs.existsSync( filePath ) ) {
							const buffer = fs.readFileSync( filePath );
							resolve( {
								ok: true,
								arrayBuffer: async () => buffer,
							} );
						} else {
							resolve( {
								ok: false,
								status: 404,
								statusText: 'Not Found',
							} );
						}
					} catch ( error ) {
						reject( error );
					}
				}, 100 ); // simulated slow response.
				if ( options && options.signal ) {
					options.signal.addEventListener( 'abort', () => {
						clearTimeout( timer );
						reject( new DOMException( 'Aborted', 'AbortError' ) );
					} );
				}
			} );
		} );

		// Call the preScan function (which should trigger a 0.05 second timeout)
		await preScanAnimatedImages( 50 );

		// The url is animated, but should be marked as false due to timeout.
		expect( animationCache.get( 'A-image.gif' ) ).toBe( false );
	} );
} );

describe( 'Cache URL normalization', () => {
	test( 'normalizes URLs in the cache', async () => {
		document.body.innerHTML = `
            <img src="TEST-IMAGE.GIF" alt="Uppercase test">
            <img src="test-image.gif" alt="Lowercase test">
            <img src="TEST-image.gif" alt="Mixedcase test">
            <img src="another-image.gif" alt="Extra uppercase to check">
            <img src="another-image.gif" alt="Extra lowercase to check">
            <img src="ANOTHER-image.gif" alt="Extra mixedcase to check">
        `;
		// Run the pre-scan to populate the cache
		await preScanAnimatedImages();

		// Verify that all cache keys are lowercase
		const keys = Array.from( animationCache.keys() );
		keys.forEach( ( key ) => {
			expect( key ).toBe( key.toLowerCase() );
		} );

		// Verify that only normalized keys exists for similar URLs
		expect( keys.length ).toBe( 2 );
	} );
} );
