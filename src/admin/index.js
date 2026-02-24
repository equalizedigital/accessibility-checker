/* eslint-disable no-unused-vars */

import {
	clearAllTabsAndPanelState,
	initFixButtonEventHandlers,
	initSummaryTabKeyboardAndClickHandlers,
} from './summary/summary-tab-input-event-handlers';

import { initFixesInputStateHandler } from './fixes-page/conditional-disable-settings';
import { initRequiredSetup } from './fixes-page/conditional-required-settings';
import { inlineSettingsProUpsell } from '../common/settings-pro-callout';

// eslint-disable-next-line camelcase
const edacScriptVars = edac_script_vars;

( function() {
	'use strict';

	jQuery( function() {
		if ( document.getElementById( 'edac-fixes-page' ) ) {
			initFixesInputStateHandler();
			initRequiredSetup();
		}

		if ( document.querySelector( '.edac-fix--upsell, .edac-setting--upsell' ) ) {
			inlineSettingsProUpsell();
		}

		// Accessibility Statement disable
		jQuery(
			'input[type=checkbox][name=edac_add_footer_accessibility_statement]'
		).on( 'change', function() {
			if ( this.checked ) {
				jQuery(
					'input[type=checkbox][name=edac_include_accessibility_statement_link]'
				).prop( 'disabled', false );
			} else {
				jQuery(
					'input[type=checkbox][name=edac_include_accessibility_statement_link]'
				).prop( 'disabled', true );
				jQuery(
					'input[type=checkbox][name=edac_include_accessibility_statement_link]'
				).prop( 'checked', false );
			}
			//
		} );

		// Show Simplified Summary code on options page
		if (
			jQuery(
				'input[type=radio][name=edac_simplified_summary_position]:checked'
			).val() === 'none'
		) {
			jQuery( '#ac-simplified-summary-option-code' ).show();
		}
		jQuery( 'input[type=radio][name=edac_simplified_summary_position]' ).on(
			'load',
			function() {
				if ( this.value === 'none' ) {
					jQuery( '#ac-simplified-summary-option-code' ).show();
				} else {
					jQuery( '#ac-simplified-summary-option-code' ).hide();
				}
			}
		);
	} );

	jQuery( window ).on( 'load', function() {
		document.addEventListener( 'edac-cleared-issues', function() {
			refreshTabDetails();
		} );

		// Listen for simplified summary save from Gutenberg sidebar
		window.addEventListener( 'edac-simplified-summary-saved', function( event ) {
			refreshSummaryAndReadability();
		} );

		// Listen for ignore updates from the Gutenberg sidebar modal
		window.addEventListener( 'edac-ignore-updated', function( event ) {
			// Small delay to ensure the database update is complete
			window.setTimeout( function() {
				refreshSummaryAndReadability();
				edacDetailsAjax();
			}, 300 );
		} );

		// Allow other js to trigger a tab refresh through an event listener. Refactor.
		const refreshTabDetails = () => {
			// reset to first meta box tab
			clearAllTabsAndPanelState();

			const summaryPanel = jQuery( '#edac-summary-panel' );
			jQuery( summaryPanel )
				.show()
				.addClass( 'active' );
			const summaryTab = '#' + jQuery( summaryPanel ).attr( 'aria-labelledby' );
			jQuery( summaryTab )
				.addClass( 'active' )
				.attr( 'aria-selected', 'true' )
				.removeAttr( 'tabindex' );

			edacDetailsAjax();
			refreshSummaryAndReadability();
		};
		top.addEventListener( 'edac_js_scan_save_complete', function( event ) {
			refreshTabDetails();
		} );

		/**
		 * Tabs
		 */

		// Refresh data on summary and readability tabs
		const refreshSummaryAndReadability = () => {
			edacSummaryAjax( () => {
				edacReadabilityAjax();
				jQuery( '.edac-panel' ).removeClass( 'edac-panel-loading' );
			} );
		};

		/**
		 * Ajax Summary
		 * @param {Function} callback - Callback function to run after ajax is complete
		 */
		function edacSummaryAjax( callback = null ) {
			const postID = edacScriptVars.postID;

			if ( postID === null ) {
				return;
			}

			jQuery.ajax( {
				url: ajaxurl,
				method: 'GET',
				data: {
					action: 'edac_summary_ajax',
					post_id: postID,
					nonce: edacScriptVars.nonce,
				},
			} ).done( function( response ) {
				if ( true === response.success ) {
					const responseJSON = jQuery.parseJSON( response.data );

					jQuery( '.edac-summary' ).html( responseJSON.content );

					if ( typeof callback === 'function' ) {
						callback();
					}
				} else {
					// eslint-disable-next-line no-console
					console.log( response );
				}
			} );
		}

		/**
		 * Ajax Details
		 */
		function edacDetailsAjax() {
			const postID = edacScriptVars.postID;

			if ( postID === null ) {
				return;
			}

			jQuery.ajax( {
				url: ajaxurl,
				method: 'GET',
				data: {
					action: 'edac_details_ajax',
					post_id: postID,
					nonce: edacScriptVars.nonce,
				},
			} ).done( function( response ) {
				if ( true === response.success ) {
					const responseJSON = jQuery.parseJSON( response.data );

					jQuery( '#edac-details-panel' ).html( responseJSON );

					// Rule on click
					jQuery( '.edac-details-rule-title' ).click( function() {
						// jQuery('.edac-details-rule-records').slideUp();
						if ( jQuery( this ).hasClass( 'active' ) ) {
							jQuery( this ).next().slideUp();
							jQuery( this ).removeClass( 'active' );
						} else {
							jQuery( this ).next().slideDown();
							jQuery( this ).addClass( 'active' );
						}
					} );

					// Title arrow button on click
					jQuery( '.edac-details-rule-title-arrow' ).click(
						function( e ) {
							e.preventDefault();
							if (
								jQuery( this ).attr( 'aria-expanded' ) === 'true'
							) {
								jQuery( this ).attr( 'aria-expanded', 'false' );
							} else {
								jQuery( this ).attr( 'aria-expanded', 'true' );
							}
						}
					);

					// Ignore on click
					jQuery(
						'.edac-details-rule-records-record-actions-ignore'
					).click( function( e ) {
						e.preventDefault();
						jQuery( this )
							.parent()
							.next( '.edac-details-rule-records-record-ignore' )
							.slideToggle();
						if ( jQuery( this ).attr( 'aria-expanded' ) === 'true' ) {
							jQuery( this ).attr( 'aria-expanded', 'false' );
						} else {
							jQuery( this ).attr( 'aria-expanded', 'true' );
						}
					} );

					// Ignore submit on click
					ignoreSubmit();

					// handle fix button click events.
					initFixButtonEventHandlers();
				} else {
					// eslint-disable-next-line no-console
					console.log( response );
				}
			} );
		}

		/**
		 * Ajax Readability
		 */
		function edacReadabilityAjax() {
			const postID = edacScriptVars.postID;

			if ( postID === null ) {
				return;
			}

			jQuery.ajax( {
				url: ajaxurl,
				method: 'GET',
				data: {
					action: 'edac_readability_ajax',
					post_id: postID,
					nonce: edacScriptVars.nonce,
				},
			} ).done( function( response ) {
				if ( true === response.success ) {
					const responseJSON = jQuery.parseJSON( response.data );

					jQuery( '#edac-readability-panel' ).html( responseJSON );

					// Simplified Summary on click
					jQuery( '.edac-readability-simplified-summary' ).submit(
						function( event ) {
							event.preventDefault();

							// var postID = wp.data.select("core/editor").getCurrentPostId();
							const summary = jQuery( '#edac-readability-text' ).val();

							jQuery.ajax( {
								url: edacScriptVars.edacApiUrl + '/simplified-summary/' + postID,
								method: 'POST',
								headers: {
									'X-WP-Nonce': edacScriptVars.restNonce,
								},
								contentType: 'application/json',
								data: JSON.stringify( {
									summary,
								} ),
							} ).done( function( doneResponse ) {
								if ( doneResponse.success ) {
									refreshSummaryAndReadability();

									// Dispatch custom event to notify sidebar to refresh its data
									const readabilityUpdatedEvent = new CustomEvent( 'edac-metabox-readability-updated', {
										detail: {
											postId: postID,
											readabilityData: doneResponse,
										},
									} );
									window.dispatchEvent( readabilityUpdatedEvent );
								} else {
									// eslint-disable-next-line no-console
									console.log( doneResponse );
								}
							} ).fail( function( error ) {
								// eslint-disable-next-line no-console
								console.error( 'Failed to save simplified summary:', error );
							} );
						}
					);
				} else {
					// eslint-disable-next-line no-console
					console.log( response );
				}
			} );
		}

		/**
		 * Ignore Submit on click
		 */
		function ignoreSubmit() {
			jQuery( '.edac-details-rule-records-record-ignore-submit' ).click(
				function( e ) {
					e.preventDefault();

					const issueId = jQuery( this ).attr( 'data-id' );
					const ignoreAction = jQuery( this ).attr( 'data-action' );
					const ignoreType = jQuery( this ).attr( 'data-type' );
					const comment = jQuery(
						'.edac-details-rule-records-record-ignore-comment',
						jQuery( this ).parent()
					).val();
					const reason = jQuery(
						'input.edac-details-rule-records-record-ignore-reason-input:checked',
						jQuery( this ).parent()
					).val() || '';

					// Map legacy actions to REST endpoint actions.
					const restAction = ignoreAction === 'enable' ? 'dismiss' : 'undismiss';

					jQuery.ajax( {
						url: edacScriptVars.edacApiUrl + '/dismiss-issue/' + issueId,
						method: 'POST',
						headers: {
							'X-WP-Nonce': edacScriptVars.restNonce,
						},
						contentType: 'application/json',
						data: JSON.stringify( {
							action: restAction,
							reason,
							comment,
						} ),
					} ).done( function( data ) {
						if ( true === data.success ) {
							const record =
								'#edac-details-rule-records-record-' + issueId;
							const isDismissed = data.ignre === true || data.ignre === 1;
							const doneIgnoreAction = isDismissed ? 'disable' : 'enable';
							const doneCommentDisabled = isDismissed;
							const doneActionsIgnoreLabel = isDismissed ? 'Ignored' : 'Ignore';
							const ignoreSubmitLabel = isDismissed
								? 'Stop Ignoring'
								: 'Ignore This ' + ignoreType;
							const username = data.ignre_user_name
								? '<strong>Username:</strong> ' + data.ignre_user_name
								: '';
							const date = data.ignre_date
								? '<strong>Date:</strong> ' + data.ignre_date
								: '';

							jQuery(
								record +
									' .edac-details-rule-records-record-ignore-submit'
							).attr( 'data-action', doneIgnoreAction );
							jQuery(
								record +
									' .edac-details-rule-records-record-ignore-comment'
							).attr( 'disabled', doneCommentDisabled );
							// Disable/enable the dismiss reason radios.
							jQuery(
								record +
									' .edac-details-rule-records-record-ignore-reason-input'
							).attr( 'disabled', doneCommentDisabled );
							if ( ! isDismissed ) {
								jQuery(
									record +
										' .edac-details-rule-records-record-ignore-comment'
								).val( '' );
							}
							jQuery(
								record +
									' .edac-details-rule-records-record-actions-ignore'
							).toggleClass( 'active' );
							jQuery(
								".edac-details-rule-records-record-actions-ignore[data-id='" +
									issueId +
									"']"
							).toggleClass( 'active' ); // pro
							jQuery(
								record +
									' .edac-details-rule-records-record-actions-ignore-label'
							).html( doneActionsIgnoreLabel );
							jQuery(
								".edac-details-rule-records-record-actions-ignore[data-id='" +
									issueId +
									"'] .edac-details-rule-records-record-actions-ignore-label"
							).html( doneActionsIgnoreLabel ); // pro
							jQuery(
								record +
									' .edac-details-rule-records-record-ignore-submit-label'
							).html( ignoreSubmitLabel );
							jQuery(
								record +
									' .edac-details-rule-records-record-ignore-info-user'
							).html( username );
							jQuery(
								record +
									' .edac-details-rule-records-record-ignore-info-date'
							).html( date );

							// Update rule count
							const rule =
								jQuery( record ).parents( '.edac-details-rule' );
							let count = parseInt(
								jQuery( '.edac-details-rule-count', rule ).html()
							);
							if ( isDismissed ) {
								count--;
							} else {
								count++;
							}
							if ( count === 0 ) {
								jQuery(
									'.edac-details-rule-count',
									rule
								).removeClass( 'active' );
							} else {
								jQuery( '.edac-details-rule-count', rule ).addClass(
									'active'
								);
							}
							count.toString();
							jQuery( '.edac-details-rule-count', rule ).html( count );

							// Update ignore rule count
							let countIgnore = parseInt(
								jQuery(
									'.edac-details-rule-count-ignore',
									rule
								).html()
							);
							if ( isDismissed ) {
								countIgnore++;
							} else {
								countIgnore--;
							}
							if ( countIgnore === 0 ) {
								jQuery(
									'.edac-details-rule-count-ignore',
									rule
								).hide();
							} else {
								jQuery(
									'.edac-details-rule-count-ignore',
									rule
								).show();
							}
							countIgnore.toString();
							jQuery( '.edac-details-rule-count-ignore', rule ).html(
								countIgnore + ' Ignored Items'
							);

							// Dispatch event to notify sidebar that ignore action was completed
							const event = new CustomEvent( 'edac-ignore-updated', {
								detail: {
									postId: parseInt( jQuery( '#post_ID' ).val() ),
									action: data.action,
									ruleId: data.rule_id,
								},
							} );
							window.dispatchEvent( event );

							// refresh page on ignore or unignore in pro
							if (
								jQuery( 'body' ).hasClass(
									'accessibility-checker_page_accessibility_checker_issues'
								) ||
								jQuery( 'body' ).hasClass(
									'accessibility-checker_page_accessibility_checker_ignored'
								)
							) {
								// eslint-disable-next-line no-undef
								location.reload( true );
							} else {
								refreshSummaryAndReadability();
							}
						} else {
							// eslint-disable-next-line no-console
							console.log( data );
						}
					} );
				}
			);
		}

		/**
		 * Check if Gutenberg is active
		 */
		function edacGutenbergActive() {
			// return false if widgets page
			if ( document.body.classList.contains( 'widgets-php' ) ) {
				return false;
			}

			// check if block editor page
			return document.body.classList.contains( 'block-editor-page' );
		}

		/**
		 * Review Notice Ajax
		 */
		if ( jQuery( '.edac-review-notice' ).length ) {
			jQuery( '.edac-review-notice-review' ).on( 'click', function() {
				edacReviewNoticeAjax( 'stop', true );
			} );

			jQuery( '.edac-review-notice-remind' ).on( 'click', function() {
				edacReviewNoticeAjax( 'pause', false );
			} );

			jQuery( '.edac-review-notice-dismiss' ).on( 'click', function() {
				edacReviewNoticeAjax( 'stop', false );
			} );
		}

		function edacReviewNoticeAjax( reviewAction, redirect ) {
			jQuery.ajax( {
				url: ajaxurl,
				method: 'GET',
				data: {
					action: 'edac_review_notice_ajax',
					review_action: reviewAction,
					nonce: edacScriptVars.nonce,
				},
			} ).done( function( response ) {
				if ( true === response.success ) {
					const responseJSON = jQuery.parseJSON( response.data );
					jQuery( '.edac-review-notice' ).fadeOut();
					if ( redirect ) {
						window.location.href =
							'https://wordpress.org/support/plugin/accessibility-checker/reviews/#new-post';
					}
				} else {
					//console.log(response);
				}
			} );
		}

		/**
		 * GAAD Notice Ajax
		 */
		if ( jQuery( '.edac_gaad_notice' ).length ) {
			jQuery( '.edac_gaad_notice .notice-dismiss' ).on( 'click', function() {
				edacGaadNoticeAjax( 'edac_gaad_notice_ajax' );
			} );
		}

		/**
		 * Black Friday Notice Ajax
		 */
		if ( jQuery( '.edac_black_friday_notice' ).length ) {
			jQuery( '.edac_black_friday_notice .notice-dismiss' ).on(
				'click',
				function() {
					edacGaadNoticeAjax( 'edac_black_friday_notice_ajax' );
				}
			);
		}

		function edacGaadNoticeAjax( functionName = null ) {
			jQuery.ajax( {
				url: ajaxurl,
				method: 'GET',
				data: {
					action: functionName,
					nonce: edacScriptVars.nonce,
				},
			} ).done( function( response ) {
				if ( true === response.success ) {
					const responseJSON = jQuery.parseJSON( response.data );
				} else {
					//console.log(response);
				}
			} );
		}

		if ( jQuery( '#edac-summary-panel' ).length ) {
			refreshSummaryAndReadability();
			edacDetailsAjax();
			ignoreSubmit();
		}

		if ( jQuery( '.edac-details-rule-records-record-ignore' ).length ) {
			ignoreSubmit();
		}
		if ( jQuery( '#edac-readability-panel' ).length ) {
			refreshSummaryAndReadability();
		}

		jQuery( '#dismiss_welcome_cta' ).on( 'click', function() {
			// AJAX request to handle button click
			jQuery.ajax( {
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'edac_dismiss_welcome_cta_ajax',
				},
				success( response ) {
					if ( response === 'success' ) {
						// Hide the CTA on button click
						jQuery( '#edac_welcome_page_summary' ).hide();
					}
				},
			} );
		} );

		/**
		 * Handle widget modal close click
		 * @param {Event} e - The event object
		 */
		function edacWidgetModalContentClose( e ) {
			const modal = e.target.closest( '.edac-widget-modal' );
			if ( modal ) {
				modal.style.display = 'none';
			}

			document.querySelector( '.edac-summary' ).remove();

			jQuery.ajax( {
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'edac_dismiss_dashboard_cta_ajax',
				},
			} );
		}
		const modalCloseBtn = document.querySelector(
			'.edac-widget-modal-content-close'
		);
		if ( modalCloseBtn ) {
			modalCloseBtn.addEventListener(
				'click',
				edacWidgetModalContentClose
			);
		}
	} );
}( jQuery ) );

window.addEventListener( 'load', function() {
	if ( document.getElementById( 'edac-tabs' ) ) {
		// bind events for the summary metabox tabs and panels.
		initSummaryTabKeyboardAndClickHandlers();
	}

	if ( this.document.querySelector( '.edac-widget .edac-summary' ) ) {
		fillDashboardWidget();
	}

	// Handle refresh button click.
	if ( this.document.querySelector( '#edac_clear_cached_stats' ) ) {
		this.document
			.querySelector( '#edac_clear_cached_stats' )
			.addEventListener( 'click', function() {
				const container = document.querySelector(
					'#edac_welcome_page_summary .edac-welcome-grid-container'
				);
				if ( container ) {
					container.classList.add( 'edac-panel-loading' );
				}

				postData(
					edacScriptVars.edacApiUrl + '/clear-cached-scans-stats'
				).then( ( data ) => {
					if ( data.success ) {
						if ( container ) {
							container.classList.remove( 'edac-panel-loading' );
						}

						// Reload the current page
						window.location.reload();
					}
				} );
			} );
	}

	if ( this.document.querySelector( 'body.accessibility-checker_page_accessibility_checker_issues' ) ) {
		initFixButtonEventHandlers();
	}

	// On our welcome page the notices break layout, so we move them to a new container in
	// the grid we have in the header.
	const notices = document.querySelectorAll( '.edac-welcome-header-left .notice' );
	if ( notices.length ) {
		// Create a new div after .edac-welcome-header-right element
		const noticesContainer = document.createElement( 'div' );
		noticesContainer.classList.add( 'edac-welcome-header-notices' );
		document.querySelector( '.edac-welcome-header-right' ).insertAdjacentElement( 'afterend', noticesContainer );
		// If the new container was created then put the notices into it.
		if ( document.querySelector( '.edac-welcome-header-notices' ) ) {
			notices.forEach( function( notice ) {
				noticesContainer.appendChild( notice );
			} );
		}
	}

	edacTimestampToLocal();

	initArchivesScanningDependency();
} );

// Fill the dashboard widget
const fillDashboardWidget = () => {
	getData( edacScriptVars.edacApiUrl + '/scans-stats' )
		.then( ( data ) => {
			if ( data.success ) {
				// Set passed %
				const passedPercentage = data.stats.passed_percentage;
				const passedPercentageFormatted =
					data.stats.passed_percentage_formatted;

				const passedPercentageEl = document.querySelector(
					'#edac-summary-passed'
				);
				if ( passedPercentageEl ) {
					passedPercentageEl.setAttribute(
						'aria-valuenow',
						passedPercentage
					);
					passedPercentageEl.style.background =
						'radial-gradient(closest-side, white 85%, transparent 80% 100%), conic-gradient(#006600 ' +
						passedPercentage +
						'%, #e2e4e7 0)';
				}
				const passedPercentageTextEl = document.querySelector(
					'#edac-summary-passed .edac-progress-percentage'
				);
				if ( passedPercentageTextEl ) {
					passedPercentageTextEl.textContent =
						passedPercentageFormatted;
				}

				// Set the summary cached at time for display in the dashboard widget.
				const summaryCachedAt = data.stats.cached_at_formatted || data.stats.fullscan_completed_at_formatted;
				const summaryCachedAtEl = document.getElementById( 'edac-summary-info-date' );
				summaryCachedAtEl.textContent = summaryCachedAt;

				// scanned
				const postsScanned = data.stats.posts_scanned;
				const postsScannedFormatted =
					data.stats.posts_scanned_formatted;
				const postsScannedEl = document.querySelector(
					'#edac-summary-info-count'
				);
				if ( postsScannedEl ) {
					postsScannedEl.textContent = postsScannedFormatted;
				}

				// errors
				const errors = data.stats.distinct_errors_without_contrast;
				const errorsFormatted =
					data.stats.distinct_errors_without_contrast_formatted;
				const errorsContainerEl = document.querySelector(
					'.edac-summary-info-stats-box-error'
				);
				if ( errors > 0 && errorsContainerEl ) {
					errorsContainerEl.classList.add( 'has-errors' );
				}
				const errorsEl = document.querySelector(
					'#edac-summary-info-errors'
				);
				if ( errorsEl ) {
					errorsEl.textContent = errorsFormatted;
				}

				// constrast errors
				const contrastErrors = data.stats.distinct_contrast_errors;
				const contrastErrorsFormatted =
					data.stats.distinct_contrast_errors_formatted;
				const contrastContainerEl = document.querySelector(
					'.edac-summary-info-stats-box-contrast'
				);
				if ( errors > 0 && contrastContainerEl ) {
					contrastContainerEl.classList.add( 'has-errors' );
				}
				const contrastErrorsEl = document.querySelector(
					'#edac-summary-info-contrast-errors'
				);
				if ( contrastErrorsEl ) {
					contrastErrorsEl.textContent = contrastErrorsFormatted;
				}

				// warnings
				const warnings = data.stats.distinct_warnings;
				const warningsFormatted =
					data.stats.distinct_warnings_formatted;
				const warningsContainerEl = document.querySelector(
					'.edac-summary-info-stats-box-warning'
				);
				if ( warnings > 0 && warningsContainerEl ) {
					warningsContainerEl.classList.add( 'has-warning' );
				}
				const warningsEl = document.querySelector(
					'#edac-summary-info-warnings'
				);
				if ( warningsEl ) {
					warningsEl.textContent = warningsFormatted;
				}

				// summary notice
				if ( errors + contrastErrors + warnings > 0 ) {
					const hasIssuesNoticeEl = document.querySelector(
						'.edac-summary-notice-has-issues'
					);
					if ( hasIssuesNoticeEl ) {
						hasIssuesNoticeEl.classList.remove( 'edac-hidden' );
					}
				} else {
					const hasNoIssuesNoticeEl = document.querySelector(
						'.edac-summary-notice-no-issues'
					);
					if ( hasNoIssuesNoticeEl && postsScanned > 0 ) {
						hasNoIssuesNoticeEl.classList.remove( 'edac-hidden' );
					}
				}

				// truncated notice
				const isTruncated = data.stats.is_truncated;
				const isTruncatedEl = document.querySelector(
					'.edac-summary-notice-is-truncated'
				);
				if ( isTruncatedEl && isTruncated ) {
					isTruncatedEl.classList.remove( 'edac-hidden' );
				}

				const wrapper = document.querySelector(
					'.edac-summary.edac-modal-container'
				);
				if ( wrapper ) {
					wrapper.classList.remove( 'edac-hidden' );
				}

				//edacTimestampToLocal();
			}
		} )
		.catch( ( e ) => {
			//TODO:
		} );

	getData( edacScriptVars.edacApiUrl + '/scans-stats-by-post-types' )
		.then( ( data ) => {
			if ( data.success ) {
				Object.entries( data.stats ).forEach( ( [ key, value ] ) => {
					if ( data.stats[ key ] ) {
						const errors = value.distinct_errors_without_contrast;
						const errorsFormatted =
							value.distinct_errors_without_contrast_formatted;
						const contrastErrors = value.distinct_contrast_errors;
						const contrastErrorsFormatted =
							value.distinct_contrast_errors_formatted;
						const warnings = value.distinct_warnings;
						const warningsFormatted =
							value.distinct_warnings_formatted;

						const errorsEl = document.querySelector(
							'#' + key + '-errors'
						);
						if ( errorsEl ) {
							errorsEl.textContent = errorsFormatted;
						}

						const contrastErrorsEl = document.querySelector(
							'#' + key + '-contrast-errors'
						);
						if ( contrastErrorsEl ) {
							contrastErrorsEl.textContent =
								contrastErrorsFormatted;
						}

						const warningsEl = document.querySelector(
							'#' + key + '-warnings'
						);
						if ( warningsEl ) {
							warningsEl.textContent = warningsFormatted;
						}
					} else {
						//We aren't tracking stats for this post type
					}
				} );
			}

			const wrapper = document.querySelector( '.edac-issues-summary' );
			if ( wrapper ) {
				wrapper.classList.remove( 'edac-hidden' );
			}

			edacTimestampToLocal();
		} )
		.catch( ( e ) => {
			// eslint-disable-next-line no-console
			console.log( e );
		} );
};

/**
 * Helper function to convert unixtime timestamp to the local date time.
 */
function edacTimestampToLocal() {
	const options = { year: 'numeric', month: 'short', day: 'numeric' };

	const elements = document.querySelectorAll( '.edac-timestamp-to-local' );

	elements.forEach( function( element ) {
		if ( /^[0-9]+$/.test( element.textContent ) ) {
			//if only numbers

			const unixtimeInSeconds = element.textContent;

			const d = new Date( unixtimeInSeconds * 1000 ).toLocaleDateString(
				[],
				options
			);
			const t = new Date( unixtimeInSeconds * 1000 ).toLocaleTimeString(
				[],
				{ timeStyle: 'short' }
			);

			const parts = Intl.DateTimeFormat( [], {
				timeZoneName: 'short',
			} ).formatToParts( new Date() );
			let tz = '';
			for ( const part of parts ) {
				if ( part.type === 'timeZoneName' ) {
					tz = part.value;
					break;
				}
			}

			element.innerHTML =
				'<span class="edac-date">' +
				d +
				'</span>&nbsp;<span class="edac-time">' +
				t +
				'</span>&nbsp;<span class="edac-timezone">' +
				tz +
				'</span>';

			element.classList.remove( 'edac-timestamp-to-local' );
		}
	} );
}

const getData = async ( url = '', data = {} ) => {
	const response = await fetch( url, {
		method: 'GET',
		headers: {
			// eslint-disable-next-line camelcase
			'X-WP-Nonce': edac_script_vars.restNonce,
			'Content-Type': 'application/json',
		},
	} );
	return response.json();
};

const postData = async ( url = '', data = {} ) => {
	const response = await fetch( url, {
		method: 'POST',
		headers: {
			// eslint-disable-next-line camelcase
			'X-WP-Nonce': edac_script_vars.restNonce,
			'Content-Type': 'application/json',
		},
		body: JSON.stringify( data ),
	} );
	return response.json();
};

/**
 * Initialize the interdependency between archives scanning and taxonomy scanning settings
 */
const initArchivesScanningDependency = () => {
	const archivesCheckbox = document.getElementById( 'edacp_enable_archive_scanning' );
	const taxonomiesCheckbox = document.getElementById( 'edacp_scan_all_taxonomy_terms' );

	if ( archivesCheckbox && taxonomiesCheckbox ) {
		function updateTaxonomiesState() {
			if ( archivesCheckbox.checked && archivesCheckbox.disabled === false ) {
				taxonomiesCheckbox.disabled = false;
			} else {
				taxonomiesCheckbox.disabled = true;
				taxonomiesCheckbox.checked = false;
			}
			const containingFieldset = taxonomiesCheckbox.closest( 'fieldset' );
			if ( containingFieldset ) {
				containingFieldset.setAttribute( 'aria-disabled', String( taxonomiesCheckbox.disabled ) );
			}
		}

		// Initial state
		updateTaxonomiesState();

		// Listen for changes
		archivesCheckbox.addEventListener( 'change', updateTaxonomiesState );
	}
};
