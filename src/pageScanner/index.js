/* eslint-disable padded-blocks, no-multiple-empty-lines */
/* global axe */

import 'axe-core';
import colorContrastFailure from './rules/color-contrast-failure';
import underlinedText from './rules/underlined-text';
import elementWithUnderline from './checks/element-with-underline';
import elementIsAUTag from './checks/element-is-u-tag';

//TODO: examples:
//import customRule1 from './rules/custom-rule-1';
//import alwaysFail from './checks/always-fail';

//TODO:
//see: https://github.com/dequelabs/axe-core/blob/develop/doc/developer-guide.md#api-reference
//see: https://www.deque.com/axe/core-documentation/api-documentation/

//NOTE: to get no-axe baseline for memory testing:
// set SCAN_TIMEOUT_IN_SECONDS = .01
// comment out scan().then((results) => {

const SCAN_TIMEOUT_IN_SECONDS = 30;

// Read the data passed from the parent document.
const body = document.querySelector( 'body' );
const iframeId = body.getAttribute( 'data-iframe-id' );
const eventName = body.getAttribute( 'data-iframe-event-name' );
const postId = body.getAttribute( 'data-iframe-post-id' );

const scan = async (
	options = { configOptions: {}, runOptions: {} }
) => {
	const context = { exclude: [ '#wpadminbar', '.edac-panel-container' ] };

	const defaults = {
		configOptions: {
			reporter: 'raw',

			rules: [
				// customRule1,
				colorContrastFailure,
				underlinedText,
			],
			checks: [
				//alwaysFail,
				elementIsAUTag,
				elementWithUnderline,
			],
			iframes: false,

		},
		resultTypes: [ 'violations' ],
		runOptions: {
			runOnly: {
				type: 'rule',
				values: [
					'color_contrast_failure',
					'underlined_text',
				],
			},

			/*
			//TODO:
			runOnly: {
				type: 'tag',
				values: [
					'wcag2a', 'wcag2aa', 'wcag2aaa',
					'wcag21a', 'wcag21aa',
					'wcag22aa',
					'best-practice',
					'ACT',
					'section508',
					'TTv5',
					'experimental'
				]
			}
			*/

		},
	};

	const configOptions = Object.assign( defaults.configOptions, options.configOptions );
	axe.configure( configOptions );

	const runOptions = Object.assign( defaults.runOptions, options.runOptions );

	return await axe.run( context, runOptions )
		.then( ( rules ) => {
			const violations = [];

			rules.forEach( ( item ) => {
				//Build an array of the dom selectors and ruleIDs for violations/failed tests
				item.violations.forEach( ( violation ) => {
					if ( violation.result === 'failed' ) {
						violations.push( {
							selector: violation.node.selector,
							html: document.querySelector( violation.node.selector ).outerHTML,
							ruleId: item.id,
							impact: item.impact,
							tags: item.tags,
						} );
					}
				} );
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

const onDone = ( violations = [], errorMsgs = [], error = false ) => {
	// cleanup the timeout.
	clearTimeout( tooLongTimeout );

	// cleanup axe.
	if ( typeof ( axe.cleanup ) !== 'undefined' ) {
		axe.cleanup(
			function() {
				axe.teardown();
				axe = null;

				// Create a custom event
				let customEvent = new CustomEvent( eventName, {
					detail: {
						iframeId,
						postId,
						violations,
						errorMsgs,
						error,
					},
					bubbles: false,
				} );

				top.dispatchEvent( customEvent );

				customEvent = null;
			},
			function() {
				axe.teardown();
				axe = null;

				// Create a custom event
				errorMsgs.push( '***** axe.cleanup() failed.' );

				let customEvent = new CustomEvent( eventName, {
					detail: {
						iframeId,
						postId,
						violations,
						errorMsgs,
						error,
					},
					bubbles: false,
				} );

				top.dispatchEvent( customEvent );

				customEvent = null;
			}
		);
	} else {
		error = true;

		errorMsgs.push( '***** axe.cleanup() does not exist.' );
		axe = null;

		let customEvent = new CustomEvent( eventName, {
			detail: {
				iframeId,
				postId,
				violations,
				errorMsgs,
				error,
			},
			bubbles: false,
		} );

		top.dispatchEvent( customEvent );

		customEvent = null;
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

