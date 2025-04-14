/* eslint-disable padded-blocks, no-multiple-empty-lines */
/* global axe */

import 'axe-core';
import colorContrastFailure from './rules/color-contrast-failure';
import underlinedText from './rules/underlined-text';
import elementWithUnderline from './checks/element-with-underline';
import elementIsAUTag from './checks/element-is-u-tag';
import emptyParagraph from './rules/empty-paragraph';
import paragraphNotEmpty from './checks/paragraph-not-empty';
import possibleHeading from './rules/possible-heading';
import paragraphStyledAsHeader from './checks/paragraph-styled-as-header';
import textSmall from './rules/text-small';
import textSizeTooSmall from './checks/text-size-too-small';
import textJustified from './rules/text-justified';
import textIsJustified from './checks/text-is-justified';
import linkTargetBlank from './rules/link_target_blank';
import linkTargetBlankWithoutInforming from './checks/link-target-blank-without-informing';
import linkAmbiguousText from './rules/link-ambiguous-text';
import hasAmbiguousText from './checks/has-ambiguous-text';
import brokenAnchorLink from './rules/broken-anchor-link';
import anchorExists from './checks/anchor-exists';
import labelExtended from './rules/extended/label';
import imageInputHasAlt from './checks/image-input-has-alt';
import linkPDF from './rules/link-pdf';
import linkMsOfficeFile from './rules/link-ms-office-file';
import ariaHiddenValidUsage from './checks/aria-hidden-valid-usage';
import ariaHiddenValidation from './rules/aria-hidden-validation';
import headingTagEmpty from './rules/empty-heading-tag';
import headingIsEmpty from './checks/heading-is-empty';
import duplicateFormLabel from './rules/duplicate-form-label';
import duplicateFormLabelCheck from './checks/duplicate-form-label-check';
import transcriptMissing from './checks/has-transcript';
import missingTranscript from './rules/missing-transcript';
import buttonEmpty from './rules/empty-button';
import buttonIsEmpty from './checks/button-is-empty';
import sliderDetected from './checks/slider-detected';
import sliderPresent from './rules/slider-present';
import isvideoDetected from './checks/is-video-detected';
import videoPresent from './rules/video-present';
import emptyTableHeader from './rules/empty-table-header';
import tableHeaderIsEmpty from './checks/table-header-is-empty';
import missingHeadings from './rules/missing-headings';
import hasSubheadingsIfLongContent from './checks/has-subheadings-if-long-content';

//TODO: examples:
//import customRule1 from './rules/custom-rule-1';
import alwaysFail from './checks/always-fail';

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
	const context = { exclude: [ '#wpadminbar', '.edac-panel-container', '#query-monitor-main' ] };

	const defaults = {
		configOptions: {
			reporter: 'raw',

			rules: [
				//customRule1,
				colorContrastFailure,
				underlinedText,
				possibleHeading,
				emptyParagraph,
				textSmall,
				textJustified,
				linkTargetBlank,
				linkAmbiguousText,
				linkPDF,
				linkMsOfficeFile,
				brokenAnchorLink,
				labelExtended,
				ariaHiddenValidation,
				headingTagEmpty,
				duplicateFormLabel,
				missingTranscript,
				buttonEmpty,
				sliderPresent,
				videoPresent,
				emptyTableHeader,
				missingHeadings,
			],
			checks: [
				alwaysFail,
				elementIsAUTag,
				elementWithUnderline,
				paragraphStyledAsHeader,
				paragraphNotEmpty,
				textSizeTooSmall,
				textIsJustified,
				linkTargetBlankWithoutInforming,
				hasAmbiguousText,
				anchorExists,
				imageInputHasAlt,
				ariaHiddenValidUsage,
				headingIsEmpty,
				duplicateFormLabelCheck,
				transcriptMissing,
				buttonIsEmpty,
				sliderDetected,
				isvideoDetected,
				tableHeaderIsEmpty,
				hasSubheadingsIfLongContent,
			],
			iframes: false,

		},
		resultTypes: [ 'violations' ],
		runOptions: {
			runOnly: {
				type: 'rule',
				values: [
					'meta-viewport',
					'blink',
					'marquee',
					'document-title',
					'tabindex',
					'html-lang-valid',
					'html-has-lang',
					'frame-title',
					colorContrastFailure.id,
					underlinedText.id,
					emptyParagraph.id,
					possibleHeading.id,
					textSmall.id,
					textJustified.id,
					linkTargetBlank.id,
					linkAmbiguousText.id,
					linkPDF.id,
					linkMsOfficeFile.id,
					brokenAnchorLink.id,
					labelExtended.id,
					ariaHiddenValidation.id,
					headingTagEmpty.id,
					duplicateFormLabel.id,
					missingTranscript.id,
					buttonEmpty.id,
					sliderPresent.id,
					videoPresent.id,
					emptyTableHeader.id,
					missingHeadings.id,
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

