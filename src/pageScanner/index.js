/* eslint-disable padded-blocks, no-multiple-empty-lines */
/* global axe */

import 'axe-core';
import { rulesArray, checksArray, standardRuleIdsArray, customRuleIdsArray } from './config/rules';
import { exclusionsArray } from './config/exclusions';
import imgAnimated from './rules/img-animated';
import { preScanAnimatedImages } from './checks/img-animated-check';
import { getPageDensity } from './helpers/density';
import {
	addRulesFilter,
	addChecksFilter,
	addRunOptionsFilter,
	addConfigOptionsFilter,
	applyRulesFilters,
	applyChecksFilters,
	applyRunOptionsFilters,
	applyConfigOptionsFilters,
} from './utils/callbacks';

const SCAN_TIMEOUT_IN_SECONDS = 30;

// Hold the timeout for the scan so it can bail on long-running scans.
let tooLongTimeout;

// Landmark tags for semantic regions
const LANDMARK_TAGS = [ 'MAIN', 'HEADER', 'FOOTER', 'NAV', 'ASIDE' ];
const LANDMARK_ROLES = [
	'main',
	'navigation',
	'banner',
	'contentinfo',
	'complementary',
];

// Conditional landmark tags that only become landmarks when they have accessible names
const CONDITIONAL_LANDMARK_TAGS = [ 'SECTION', 'ARTICLE', 'FORM' ];
const CONDITIONAL_LANDMARK_ROLES = [ 'region', 'article', 'form' ];

function getLandmarkForSelector( selector ) {
	const el = document.querySelector( selector );
	if ( ! el ) {
		return { type: null, selector: null };
	}
	let current = el;
	while ( current && current !== document.body ) {
		// Check unconditional landmark tags
		if ( LANDMARK_TAGS.includes( current.tagName ) ) {
			return { type: current.tagName.toLowerCase(), selector: getElementSelector( current ) };
		}

		// Check conditional landmark tags (require accessible name)
		if (
			CONDITIONAL_LANDMARK_TAGS.includes( current.tagName ) &&
			( current.hasAttribute( 'aria-label' ) || current.hasAttribute( 'aria-labelledby' ) )
		) {
			return { type: current.tagName.toLowerCase(), selector: getElementSelector( current ) };
		}

		// Check roles
		if ( current.hasAttribute( 'role' ) ) {
			const role = current.getAttribute( 'role' ).toLowerCase();

			// Check unconditional landmark roles
			if ( LANDMARK_ROLES.includes( role ) ) {
				return { type: role, selector: getElementSelector( current ) };
			}

			// Check conditional landmark roles (require accessible name)
			if (
				CONDITIONAL_LANDMARK_ROLES.includes( role ) &&
				( current.hasAttribute( 'aria-label' ) || current.hasAttribute( 'aria-labelledby' ) )
			) {
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

	// Use ID if available (most reliable)
	if ( element.id ) {
		return `#${ element.id }`;
	}

	// For landmark elements, try to use semantic selectors first
	const tagName = element.tagName.toLowerCase();

	// For main element, use tag selector if it's unique
	if ( tagName === 'main' ) {
		const mainElements = document.querySelectorAll( 'main' );
		if ( mainElements.length === 1 ) {
			return 'main';
		}
	}

	// For header/footer, check if they're direct children of body
	if ( ( tagName === 'header' || tagName === 'footer' ) && element.parentElement === document.body ) {
		return tagName;
	}

	// For nav elements, try role-based selector first
	if ( tagName === 'nav' || element.getAttribute( 'role' ) === 'navigation' ) {
		const navElements = document.querySelectorAll( 'nav, [role="navigation"]' );
		if ( navElements.length === 1 ) {
			return tagName === 'nav' ? 'nav' : '[role="navigation"]';
		}
		// If multiple, try to use aria-label or other identifying attributes
		if ( element.hasAttribute( 'aria-label' ) ) {
			const ariaLabel = element.getAttribute( 'aria-label' );
			return `${ tagName === 'nav' ? 'nav' : '[role="navigation"]' }[aria-label="${ ariaLabel }"]`;
		}
	}

	// For other landmark roles, use role selector if unique
	const role = element.getAttribute( 'role' );
	if ( role && LANDMARK_ROLES.includes( role ) ) {
		const roleElements = document.querySelectorAll( `[role="${ role }"]` );
		if ( roleElements.length === 1 ) {
			return `[role="${ role }"]`;
		}
		// If multiple, try to use aria-label
		if ( element.hasAttribute( 'aria-label' ) ) {
			const ariaLabel = element.getAttribute( 'aria-label' );
			return `[role="${ role }"][aria-label="${ ariaLabel }"]`;
		}
	}

	// Fallback to path-based selector (simplified)
	const path = [];
	let current = element;
	while ( current && current.nodeType === Node.ELEMENT_NODE && current !== document.body ) {
		let selector = current.nodeName.toLowerCase();

		// Add ID if available
		if ( current.id ) {
			selector = `#${ current.id }`;
			path.unshift( selector );
			break; // Stop here since ID is unique
		}

		// Add stable classes (avoid dynamic/generated classes)
		if ( current.className ) {
			const classes = current.className.trim().split( /\s+/ )
				.map( ( cls ) => CSS.escape( cls ) )
				.filter( ( cls ) => ! cls.match( /^(wp-|js-|css-|generated-|dynamic-)/ ) ) // Filter out common dynamic classes
				.slice( 0, 2 ); // Limit to first 2 classes for stability
			if ( classes.length > 0 ) {
				selector += `.${ classes.join( '.' ) }`;
			}
		}

		// Only add nth-child as last resort and only if element has no other identifying features
		if ( ! current.id && ! current.className ) {
			const siblingIndex = Array.from( current.parentNode.children ).indexOf( current ) + 1;
			selector += `:nth-child(${ siblingIndex })`;
		}

		path.unshift( selector );
		current = current.parentElement;

		// Limit path depth to avoid overly complex selectors
		if ( path.length >= 4 ) {
			break;
		}
	}
	return path.length ? path.join( ' > ' ) : null;
}

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

	// Apply callback filters to rules and checks before setting defaults
	const filteredRules = applyRulesFilters( rulesArray );
	const filteredChecks = applyChecksFilters( checksArray );

	const defaults = {
		configOptions: {
			reporter: 'raw',
			rules: filteredRules,
			checks: filteredChecks,
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

	// Apply callback filters to options
	const baseConfigOptions = Object.assign( defaults.configOptions, options.configOptions );
	const configOptions = applyConfigOptionsFilters( baseConfigOptions );
	axe.configure( configOptions );

	const baseRunOptions = Object.assign( defaults.runOptions, options.runOptions );
	const runOptions = applyRunOptionsFilters( baseRunOptions );

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
						violations.push( processViolation( violation, item ) );
					}
				} );

				// Handle incomplete results for form-field-multiple-labels only.
				if ( item.id === 'form-field-multiple-labels' ) { // Allow incomplete results for this rule.
					item.incomplete.forEach( ( incompleteItem ) => {
						violations.push( processViolation( incompleteItem, item ) );
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

// Expose callback registration functions globally
window.addPageScannerRulesFilter = addRulesFilter;
window.addPageScannerChecksFilter = addChecksFilter;
window.addPageScannerRunOptionsFilter = addRunOptionsFilter;
window.addPageScannerConfigOptionsFilter = addConfigOptionsFilter;

/**
 * Usage Examples:
 *
 * // Filter out specific rules before scanning
 * window.addPageScannerRulesFilter((rules) => {
 *   return rules.filter(rule => rule.id !== 'color-contrast-failure');
 * });
 *
 * // Add custom checks
 * window.addPageScannerChecksFilter((checks) => {
 *   return [...checks, myCustomCheck];
 * });
 *
 * // Modify run options (e.g., only run specific rules)
 * window.addPageScannerRunOptionsFilter((options) => {
 *   return {
 *     ...options,
 *     runOnly: {
 *       type: 'rule',
 *       values: ['meta-viewport', 'document-title']
 *     }
 *   };
 * });
 *
 * // Modify config options
 * window.addPageScannerConfigOptionsFilter((options) => {
 *   return {
 *     ...options,
 *     iframes: true
 *   };
 * });
 */

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

// Helper to process a violation and return the formatted object
function processViolation( violation, item ) {
	// Note that this is an array, generally with one item, but can be more.
	const selector = violation.node.selector;
	const landmark = getLandmarkForSelector( selector );
	const ancestry = violation.node.ancestry || [];
	const xpath = violation.node.xpath || [];
	const html = document.querySelector( selector )?.outerHTML;
	return {
		selector,
		ancestry,
		xpath,
		html,
		ruleId: item.id,
		impact: item.impact,
		tags: item.tags,
		landmark: landmark.type,
		landmarkSelector: landmark.selector,
	};
}
