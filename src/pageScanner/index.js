/* eslint-disable padded-blocks, no-multiple-empty-lines */
/* global axe */

import 'axe-core';
import html2canvas from 'html2canvas';
import { rulesArray, checksArray, standardRuleIdsArray, customRuleIdsArray } from './config/rules';
import { exclusionsArray } from './config/exclusions';
import imgAnimated from './rules/img-animated';
import { preScanAnimatedImages } from './checks/img-animated-check';
import { getPageDensity } from './helpers/density';

const SCAN_TIMEOUT_IN_SECONDS = 30;

// Read the data passed from the parent document.
const body = document.querySelector( 'body' );
const iframeId = body.getAttribute( 'data-iframe-id' );
const eventName = body.getAttribute( 'data-iframe-event-name' );
const postId = body.getAttribute( 'data-iframe-post-id' );

/**
 * Takes a screenshot of an element that violates accessibility rules
 * @param {HTMLElement} element The DOM element to screenshot
 * @return {Promise<string>} Base64 encoded screenshot
 */
const captureViolationScreenshot = async ( element ) => {
	try {
		const canvas = await html2canvas( element, {
			backgroundColor: null,
			logging: false,
			scale: 1,
		} );
		return canvas.toDataURL( 'image/png' );
	} catch ( err ) {
		// eslint-disable-next-line no-console
		console.error( 'Failed to capture screenshot:', err );
		return null;
	}
};

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
		.then( async ( rules ) => {
			const violations = [];

			for ( const item of rules ) {
				for ( const violation of item.violations ) {
					if ( violation.result === 'failed' ) {
						const element = document.querySelector( violation.node.selector );
						let screenshot = null;

						if ( element ) {
							screenshot = await captureViolationScreenshot( element );
						}

						violations.push( {
							selector: violation.node.selector,
							html: element ? element.outerHTML : '',
							ruleId: item.id,
							impact: item.impact,
							tags: item.tags,
							screenshot,
						} );
					}
				}

				// Handle incomplete results for form-field-multiple-labels only.
				if ( item.id === 'form-field-multiple-labels' ) { // Allow incomplete results for this rule.
					for ( const incompleteItem of item.incomplete ) {
						const element = document.querySelector( incompleteItem.node.selector );
						let screenshot = null;

						if ( element ) {
							screenshot = await captureViolationScreenshot( element );
						}

						violations.push( {
							selector: incompleteItem.node.selector,
							html: element ? element.outerHTML : '',
							ruleId: item.id,
							impact: item.impact,
							tags: item.tags,
							screenshot,
						} );
					}
				}
			}

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
				const elemA = document.querySelector( a.selector );
				const elemB = document.querySelector( b.selector );

				if ( elemA === elemB ) {
					return 0;
				}

				/* eslint-disable no-bitwise */
				if ( elemA.compareDocumentPosition( elemB ) & 2 ) {
					// b comes before a
					return 1;
				}
				return -1;
			} );

			return { rules, rulesMin, violations };
		} )
		.catch( ( err ) => {
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
