/* eslint-disable padded-blocks, no-multiple-empty-lines */
/* global axe */

import 'axe-core';
import { rulesArray, checksArray, standardRuleIdsArray, customRuleIdsArray } from './config/rules';
import { exclusionsArray } from './config/exclusions';
import imgAnimated from './rules/img-animated';
import { preScanAnimatedImages } from './checks/img-animated-check';
import { getPageDensity } from './helpers/density';

const SCAN_TIMEOUT_IN_SECONDS = 30;

// Hold the timeout for the scan so it can bail on long-running scans.
let tooLongTimeout;

// Read the data passed from the parent document.
const body = document.querySelector( 'body' );
const iframeId = body.getAttribute( 'data-iframe-id' );
const eventName = body.getAttribute( 'data-iframe-event-name' );
const postId = body.getAttribute( 'data-iframe-post-id' );

/**
 * Check if the current context the script is loaded in is a scanner iframe.
 *
 * @return {boolean} True if in iframe context, false otherwise.
 */
function isIframeContext() {
	return !! ( body && body.hasAttribute( 'data-iframe-id' ) && body.hasAttribute( 'data-iframe-event-name' ) );
}

/**
 * Get the iframe options from the body attributes/
 *
 * @return {Object} {{configOptions: {}, runOptions: {}, iframeId: string | Attribute, eventName: string | Attribute, postId: string | Attribute}}
 */
function getIframeOptions() {
	return {
		configOptions: {},
		runOptions: {},
		iframeId: body.getAttribute( 'data-iframe-id' ),
		eventName: body.getAttribute( 'data-iframe-event-name' ),
		postId: body.getAttribute( 'data-iframe-post-id' ),
	};
}

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
						const el = document.querySelector( violation.node.selector );
						violations.push( {
							selector: violation.node.selector,
							html: el ? el.outerHTML : null,
							ruleId: item.id,
							impact: item.impact,
							tags: item.tags,
						} );
					}
				} );

				// Handle incomplete results for form-field-multiple-labels only.
				if ( item.id === 'form-field-multiple-labels' ) { // Allow incomplete results for this rule.
					item.incomplete.forEach( ( incompleteItem ) => {
						const el = document.querySelector( incompleteItem.node.selector );
						violations.push( {
							selector: incompleteItem.node.selector,
							html: el ? el.outerHTML : null,
							ruleId: item.id,
							impact: item.impact,
							tags: item.tags,
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

// eslint-disable-next-line no-unused-vars
const onDone = ( violations = [], errorMsgs = [], error = false ) => {
	// cleanup the timeout.
	clearTimeout( tooLongTimeout );

	// cleanup axe.
	if ( typeof ( axe.cleanup ) !== 'undefined' ) {
		axe.cleanup(
			function() {
				axe.teardown();
				axe = null;
				dispatchDoneEvent( violations, errorMsgs, '' );
			},
			function() {
				axe.teardown();
				axe = null;
				errorMsgs.push( '***** axe.cleanup() failed.' );
				dispatchDoneEvent( violations, errorMsgs, 'cleanup-failed' );
			}
		);
	} else {
		errorMsgs.push( '***** axe.cleanup() does not exist.' );
		axe = null;
		dispatchDoneEvent( violations, errorMsgs, 'cleanup-not-exists' );
	}
};

/**
 * Attach an axe runner to the window object to allow for running the scan from
 * the active document.
 *
 * @param {Object} options Options for the accessibility scan.
 * @return {Promise<Object>} Promise resolving to the scan result.
 */
window.runAccessibilityScan = async function( options = {} ) {
	return scan( options )
		.then( ( result ) => {
			if ( typeof options.onComplete === 'function' ) {
				options.onComplete( result );
			}
			return result;
		} )
		.catch( ( err ) => {
			if ( typeof options.onComplete === 'function' ) {
				options.onComplete( null, err );
			}
			throw err;
		} );
};

// Auto-run scan and dispatch event to parent frame if in iframe context
if ( isIframeContext() ) {
	const iframeOptions = getIframeOptions();

	tooLongTimeout = setTimeout( () => {
		dispatchDoneEvent( [], [ 'Scan timed out' ], 'timeout' );
	}, SCAN_TIMEOUT_IN_SECONDS * 1000 );

	scan( iframeOptions )
		.then( ( result ) => onDone( result.violations, [], null ) )
		.catch( ( err ) => onDone( [], [ err.message || 'Unknown error' ], err.message ) );
}
