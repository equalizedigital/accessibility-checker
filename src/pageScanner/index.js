/* eslint-disable padded-blocks, no-multiple-empty-lines */
/* global axe */

import 'axe-core';
import { rulesArray, checksArray, standardRuleIdsArray, customRuleIdsArray } from './config/rules';
import { exclusionsArray } from './config/exclusions';
import imgAnimated from './rules/img-animated';
import { preScanAnimatedImages } from './checks/img-animated-check';
import { getPageDensity } from './helpers/density';

const SCAN_TIMEOUT_IN_SECONDS = 30;

// Landmark tags for semantic regions
const LANDMARK_TAGS = [ 'MAIN', 'HEADER', 'FOOTER', 'NAV', 'ASIDE' ];
const LANDMARK_ROLES = [
	'main',
	'navigation',
	'banner',
	'contentinfo',
	'complementary',
];

function getLandmarkForSelector( selector ) {
	const el = document.querySelector( selector );
	if ( ! el ) {
		return { type: null, selector: null };
	}
	let current = el;
	while ( current && current !== document.body ) {
		if ( LANDMARK_TAGS.includes( current.tagName ) ) {
			return { type: current.tagName.toLowerCase(), selector: getElementSelector( current ) };
		}
		if ( current.hasAttribute( 'role' ) ) {
			const role = current.getAttribute( 'role' ).toLowerCase();
			if ( LANDMARK_ROLES.includes( role ) ) {
				return { type: role, selector: getElementSelector( current ) };
			}
		}
		current = current.parentElement;
	}
	return { type: null, selector: null };
}

// Helper to get a unique CSS selector for an element
function getElementSelector( element ) {
	if ( ! element ) {
		return null;
	}
	if ( element.id ) {
		return `#${ element.id }`;
	}
	const path = [];
	while ( element && element.nodeType === Node.ELEMENT_NODE && element !== document.body ) {
		let selector = element.nodeName.toLowerCase();
		if ( element.className ) {
			const classes = element.className.trim().split( /\s+/ ).join( '.' );
			selector += `.${ classes }`;
		}
		const siblingIndex = Array.from( element.parentNode.children ).indexOf( element ) + 1;
		selector += `:nth-child(${ siblingIndex })`;
		path.unshift( selector );
		element = element.parentElement;
	}
	return path.length ? path.join( ' > ' ) : null;
}

// Read the data passed from the parent document.
const body = document.querySelector( 'body' );
const iframeId = body.getAttribute( 'data-iframe-id' );
const eventName = body.getAttribute( 'data-iframe-event-name' );
const postId = body.getAttribute( 'data-iframe-post-id' );

const scan = async (
	options = { configOptions: {}, runOptions: {} }
) => {
	const context = { exclude: exclusionsArray };

	const defaults = {
		configOptions: {
			reporter: 'raw',
			rules: rulesArray,
			checks: checksArray,
			iframes: false,
		},
		resultTypes: [ 'violations', 'incomplete' ],
		runOptions: {
			runOnly: {
				type: 'rule',
				values: [ ...standardRuleIdsArray, ...customRuleIdsArray ],
			},
		},
	};

	const configOptions = Object.assign( defaults.configOptions, options.configOptions );
	axe.configure( configOptions );

	const runOptions = Object.assign( defaults.runOptions, options.runOptions );

	// Axe core checks can't run async and to find animated gifs we need to use fetch. So this
	// function will do that fetching and cache the results so they are available when the
	// img_animated rule runs.
	// NOTE: in future we should flag this and run it only if the img_animated rule is enabled.
	if ( runOptions?.runOnly?.values?.includes( imgAnimated.id ) ) {
		await preScanAnimatedImages();
	}

	return await axe.run( context, runOptions )
		.then( ( rules ) => {
			const violations = [];

			rules.forEach( ( item ) => {
				//Build an array of the dom selectors and ruleIDs for violations/failed tests
				item.violations.forEach( ( violation ) => {
					if ( violation.result === 'failed' ) {
						const selector = violation.node.selector;
						const html = document.querySelector( selector )?.outerHTML;
						const landmark = getLandmarkForSelector( selector );
						violations.push( {
							selector,
							html,
							ruleId: item.id,
							impact: item.impact,
							tags: item.tags,
							landmark: landmark.type,
							landmarkSelector: landmark.selector,
						} );
					}
				} );

				// Handle incomplete results for form-field-multiple-labels only.
				if ( item.id === 'form-field-multiple-labels' ) { // Allow incomplete results for this rule.
					item.incomplete.forEach( ( incompleteItem ) => {
						const selector = incompleteItem.node.selector;
						const html = document.querySelector( selector )?.outerHTML;
						const landmark = getLandmarkForSelector( selector );
						violations.push( {
							selector,
							html,
							ruleId: item.id,
							impact: item.impact,
							tags: item.tags,
							landmark: landmark.type,
							landmarkSelector: landmark.selector,
						} );
					} );
				}
			} );

			const rulesMin = rules.map( ( r ) => {
				return {
					id: r.id,
					description: r.description,
					help: r.help,
					impact: r.impact,
					tags: r.tags,
				};
			} );

			//Sort the violations by order they appear in the document
			violations.sort( function( a, b ) {
				a = document.querySelector( a.selector );
				b = document.querySelector( b.selector );

				if ( a === b ) {
					return 0;
				}

				/* eslint-disable no-bitwise */
				if ( a.compareDocumentPosition( b ) & 2 ) {
					// b comes before a
					return 1;
				}
				return -1;
			} );

			return { rules, rulesMin, violations };
		} ).catch( ( err ) => {
			throw err;
		} );
};

/**
 * Dispatch the done event to the parent window.
 *
 * @param {Array}  violations The violations found during the scan.
 * @param {Array}  errorMsgs  Any error messages that occurred during the scan.
 * @param {string} error      The error message if an error occurred during scan cleanup.
 */
function dispatchDoneEvent( violations, errorMsgs, error ) {
	const [ elementCount, contentLength ] = getPageDensity( body );

	const customEvent = new CustomEvent( eventName, {
		detail: {
			iframeId,
			postId,
			violations,
			errorMsgs,
			error,
			densityMetrics: {
				elementCount,
				contentLength,
			},
		},
		bubbles: false,
	} );

	top.dispatchEvent( customEvent );
}

const onDone = ( violations = [], errorMsgs = [], error = false ) => {
	// cleanup the timeout.
	clearTimeout( tooLongTimeout );

	// cleanup axe.
	if ( typeof ( axe.cleanup ) !== 'undefined' ) {
		axe.cleanup(
			function() {
				axe.teardown();
				axe = null;

				dispatchDoneEvent( violations, errorMsgs, error );
			},
			function() {
				axe.teardown();
				axe = null;

				// Create a custom event
				errorMsgs.push( '***** axe.cleanup() failed.' );

				dispatchDoneEvent( violations, errorMsgs, error );
			}
		);
	} else {
		error = true;

		errorMsgs.push( '***** axe.cleanup() does not exist.' );
		axe = null;

		dispatchDoneEvent( violations, errorMsgs, error );
	}
};

// Fire a failed event if the scan doesn't complete on time.
const tooLongTimeout = setTimeout( function() {
	onDone( [], [ '***** axe scan took too long.' ], true );
}, SCAN_TIMEOUT_IN_SECONDS * 1000 );

// Start the scan.
scan().then( ( results ) => {
	const violations = JSON.parse( JSON.stringify( results.violations ) );
	onDone( violations );
} ).catch( ( err ) => {
	onDone( [], [ err.message ], true );
} );
