/**
 * Post Save Block Notice
 *
 * Client-side publish blocking with three defense layers:
 *
 * Layer 1 — Pre-save interception (wp.data.subscribe):
 * Synchronously detects when Gutenberg sets the edited status to 'publish'
 * (via the Publish button's editPost call) and reverts it BEFORE savePost()
 * runs. If the AC store already shows blocking issues the status is reverted
 * to its previous value, a "Scanning…" info notice is shown, and a pending-
 * publish intent flag is set so Layer 2 can auto-publish after the scan if
 * the issues are resolved.
 *
 * Layer 2 — Post-save scan result handler (edac_js_scan_save_complete):
 * After every user-initiated save, waits for the automatic scan to finish.
 * If a pending-publish intent exists: auto-publishes when the fresh scan is
 * clean, or shows an error when issues remain. For already-published posts
 * without a pending intent, reverts to Pending if the scan finds issues.
 *
 * Layer 3 — Server-side filter (PublishBlocker.php):
 * The wp_insert_post_data filter reads _edac_summary post meta and blocks
 * publish attempts when issues exist. Acts as a safety net.
 *
 * Only active for Pro users with hard-mode publish blocking enabled.
 */

import { useEffect, useRef } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { __, _n, _x, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { STORE_NAME } from '../store/accessibility-checker-store';

// Module-level flag shared between Layer 1 and Layer 2.
// When Layer 1 blocks a publish attempt it sets this to true so that
// Layer 2 can auto-publish after the scan if the post turns out clean.
let pendingPublishIntent = false;

/**
 * Check if the summary contains issues that should block publishing.
 *
 * @param {Object} summary The accessibility scan summary object.
 * @return {boolean} True if blocking issues exist.
 */
const hasBlockingIssues = ( summary ) => {
	const settings = window.edac_sidebar_app || {};

	if ( settings.blockPublishOnErrors ) {
		const errors =
			( parseInt( summary.errors, 10 ) || 0 ) +
			( parseInt( summary.contrast_errors, 10 ) || 0 );
		if ( errors > 0 ) {
			return true;
		}
	}

	if ( settings.blockPublishOnWarnings ) {
		if ( ( parseInt( summary.warnings, 10 ) || 0 ) > 0 ) {
			return true;
		}
	}

	return false;
};

/**
 * Build a human-readable issue count string for the notice.
 *
 * @param {Object} summary The scan summary.
 * @return {string} e.g. "3 errors, 1 warning"
 */
const buildIssueSummary = ( summary ) => {
	const settings = window.edac_sidebar_app || {};
	const parts = [];

	if ( settings.blockPublishOnErrors ) {
		const errorCount =
			( parseInt( summary.errors, 10 ) || 0 ) +
			( parseInt( summary.contrast_errors, 10 ) || 0 );
		if ( errorCount > 0 ) {
			parts.push(
				sprintf(
					// translators: %d: number of error-level issues.
					_n( '%d error', '%d errors', errorCount, 'accessibility-checker' ),
					errorCount,
				),
			);
		}
	}

	if ( settings.blockPublishOnWarnings ) {
		const warningCount = parseInt( summary.warnings, 10 ) || 0;
		if ( warningCount > 0 ) {
			parts.push(
				sprintf(
					// translators: %d: number of warning-level issues.
					_n( '%d warning', '%d warnings', warningCount, 'accessibility-checker' ),
					warningCount,
				),
			);
		}
	}

	// translators: used between list items, e.g. "3 errors, 1 warning".
	return parts.join( _x( ', ', 'list separator', 'accessibility-checker' ) );
};

/**
 * Headless component — renders nothing, side-effects only.
 *
 * @return {null} Renders nothing.
 */
const PostSaveBlockNotice = () => {
	const settings = window.edac_sidebar_app || {};
	const isPro = !! settings.isPro;
	const blockPublish = !! settings.blockPublish;
	const blockPublishMode = settings.blockPublishMode || 'soft';
	const userCanBypass = !! settings.userCanBypassPublishBlock;
	const shouldBypassFlow =
		! isPro || ! blockPublish || blockPublishMode !== 'hard' || userCanBypass;

	const { createErrorNotice, createInfoNotice, createSuccessNotice, removeNotice } =
		useDispatch( 'core/notices' );

	// Keep notice dispatch refs stable for closures that outlive renders.
	const noticeRef = useRef( {
		createErrorNotice,
		createInfoNotice,
		createSuccessNotice,
		removeNotice,
	} );
	useEffect( () => {
		noticeRef.current = {
			createErrorNotice,
			createInfoNotice,
			createSuccessNotice,
			removeNotice,
		};
	}, [ createErrorNotice, createInfoNotice, createSuccessNotice, removeNotice ] );

	// ── Layer 1: Pre-save status interception ──────────────────────────
	//
	// Gutenberg's publish button does:
	//   editPost({ status: 'publish' })   ← dispatch, subscribers fire
	//   savePost()                         ← uses the edited status
	//
	// Our subscriber runs between those two calls and reverts the status
	// so savePost() sends a non-publish status to the server. A pending-
	// publish intent is set so Layer 2 can auto-publish after the scan if
	// the fresh scan shows no blocking issues.
	useEffect( () => {
		if ( shouldBypassFlow ) {
			return;
		}

		// Guard against double-registration across hot reloads.
		if ( top.edacPublishBlockSubscribed ) {
			return;
		}
		top.edacPublishBlockSubscribed = true;

		const editor = wp.data.select( 'core/editor' );

		// Seed with the current status so we do not trigger on page load.
		let lastStatus = editor.getEditedPostAttribute( 'status' );

		const unsubscribe = wp.data.subscribe( () => {
			const status = editor.getEditedPostAttribute( 'status' );

			// Only react to actual status transitions.
			if ( status === lastStatus ) {
				return;
			}
			const previousStatus = lastStatus;
			lastStatus = status;

			// We only care about transitions TO 'publish'.
			if ( status !== 'publish' ) {
				return;
			}

			// Never intercept during an active save — that would be the
			// server returning a status in the response, not a user click.
			if ( editor.isSavingPost() ) {
				return;
			}

			// Read the latest scan data from the AC store.
			const acStore = wp.data.select( STORE_NAME );
			const acData = acStore ? acStore.getData() : null;
			const summary = acData?.summary;

			// No scan data yet — cannot determine issues, allow publish.
			if ( ! summary ) {
				return;
			}

			if ( ! hasBlockingIssues( summary ) ) {
				return;
			}

			// ── Block the publish ──
			const revertTo =
				previousStatus && previousStatus !== 'publish'
					? previousStatus
					: 'pending';

			wp.data.dispatch( 'core/editor' ).editPost(
				{ status: revertTo },
				{ undoIgnore: true },
			);
			lastStatus = revertTo;

			// Set the pending-publish intent so Layer 2 auto-publishes
			// after the scan if the issues have been resolved.
			pendingPublishIntent = true;

			const notices = noticeRef.current;
			notices.removeNotice( 'edac-publish-blocked' );
			notices.createInfoNotice(
				__(
					'Accessibility Checker is scanning your content before publishing\u2026',
					'accessibility-checker',
				),
				{ id: 'edac-scanning', isDismissible: false },
			);
		} );

		return () => {
			unsubscribe();
			top.edacPublishBlockSubscribed = false;
		};
	}, [ shouldBypassFlow ] );

	// ── Layer 2: Post-save scan result handler ─────────────────────────
	//
	// After every user-initiated save the editor automatically triggers a
	// scan (via checkPage.js). This layer waits for the scan to complete
	// and then:
	//   • If a pendingPublishIntent exists → auto-publish when clean, or
	//     show an error when issues remain.
	//   • If a post is already published (no intent) → revert to pending
	//     when issues exist.
	useEffect( () => {
		if ( shouldBypassFlow ) {
			return;
		}

		let userSaved = false;
		let correcting = false;

		// Track user-initiated saves so we only act after real saves,
		// not after the initial page-load scan.
		const saveUnsub = wp.data.subscribe( () => {
			const isSaving = wp.data.select( 'core/editor' ).isSavingPost();
			const isAuto = wp.data.select( 'core/editor' ).isAutosavingPost();
			if ( isSaving && ! isAuto ) {
				userSaved = true;
			}
		} );

		const handleScanComplete = async () => {
			if ( ! userSaved || correcting ) {
				return;
			}
			userSaved = false;

			const editor = wp.data.select( 'core/editor' );
			const post = editor.getCurrentPost();
			if ( ! post ) {
				return;
			}

			const hasPendingIntent = pendingPublishIntent;
			if ( hasPendingIntent ) {
				pendingPublishIntent = false;
			}

			try {
				// Fetch the freshest summary directly from the REST API
				// (the AC store might still be debouncing its refresh).
				const response = await apiFetch( {
					path: '/accessibility-checker/v1/sidebar-data/' + post.id,
				} );

				if ( ! response.success ) {
					return;
				}

				const summary = response.data?.summary;
				const notices = noticeRef.current;

				// ── Pending-publish intent flow ──
				if ( hasPendingIntent ) {
					notices.removeNotice( 'edac-scanning' );

					if ( ! summary || ! hasBlockingIssues( summary ) ) {
						// Issues resolved — auto-publish.
						correcting = true;

						const typeObj = wp.data.select( 'core' ).getPostType( post.type );
						const restBase = typeObj?.rest_base;
						if ( ! restBase ) {
							return;
						}

						const updated = await apiFetch( {
							path: '/wp/v2/' + restBase + '/' + post.id,
							method: 'PUT',
							data: { status: 'publish' },
						} );

						wp.data.dispatch( 'core' ).receiveEntityRecords(
							'postType',
							post.type,
							[ updated ],
						);
						wp.data.dispatch( 'core/editor' ).editPost(
							{ status: updated.status },
							{ undoIgnore: true },
						);

						notices.removeNotice( 'edac-publish-blocked' );
						notices.createSuccessNotice(
							__( 'Post published.', 'accessibility-checker' ),
							{ id: 'edac-auto-published', isDismissible: true },
						);
					} else {
						// Issues remain — show error.
						notices.removeNotice( 'edac-publish-blocked' );
						notices.createErrorNotice(
							sprintf(
								/* translators: %s: issue summary */
								__(
									'Publishing blocked: this content has %s. Fix or ignore the issues in Accessibility Checker before publishing.',
									'accessibility-checker',
								),
								buildIssueSummary( summary ),
							),
							{ id: 'edac-publish-blocked', isDismissible: true },
						);
					}
					return;
				}

				// ── Already-published correction flow ──
				if ( post.status !== 'publish' ) {
					return;
				}

				if ( ! summary || ! hasBlockingIssues( summary ) ) {
					return;
				}

				correcting = true;

				const typeObj = wp.data.select( 'core' ).getPostType( post.type );
				const restBase = typeObj?.rest_base;
				if ( ! restBase ) {
					return;
				}

				const updated = await apiFetch( {
					path: '/wp/v2/' + restBase + '/' + post.id,
					method: 'PUT',
					data: { status: 'pending' },
				} );

				wp.data.dispatch( 'core' ).receiveEntityRecords(
					'postType',
					post.type,
					[ updated ],
				);
				wp.data.dispatch( 'core/editor' ).editPost(
					{ status: updated.status },
					{ undoIgnore: true },
				);

				notices.removeNotice( 'edac-publish-blocked' );
				notices.createErrorNotice(
					__(
						'Publishing blocked: accessibility issues were found after scanning. The post has been set to Pending Review. Fix or ignore the issues before publishing.',
						'accessibility-checker',
					),
					{ id: 'edac-publish-blocked', isDismissible: true },
				);
			} catch ( e ) {
				// Non-critical — server-side filter provides a safety net.
			} finally {
				correcting = false;
			}
		};

		window.addEventListener( 'edac_js_scan_save_complete', handleScanComplete );
		try {
			top.addEventListener( 'edac_js_scan_save_complete', handleScanComplete );
		} catch ( e ) {
			// Cross-origin top frame — ignore.
		}

		return () => {
			saveUnsub();
			window.removeEventListener( 'edac_js_scan_save_complete', handleScanComplete );
			try {
				top.removeEventListener( 'edac_js_scan_save_complete', handleScanComplete );
			} catch ( e ) {
				// Ignore.
			}
		};
	}, [ shouldBypassFlow ] );

	// ── Layer 3 notice: Detect server-side status reversion ────────────
	//
	// If the server-side wp_insert_post_data filter blocked publish and
	// reverted the status, show the notice in the Gutenberg editor.
	useEffect( () => {
		if ( shouldBypassFlow ) {
			return;
		}

		let wasSaving = false;

		const detectUnsub = wp.data.subscribe( () => {
			const editor = wp.data.select( 'core/editor' );
			const isSaving = editor.isSavingPost();
			const isAuto = editor.isAutosavingPost();

			if ( isSaving && ! isAuto ) {
				wasSaving = true;
				return;
			}

			if ( ! wasSaving || isSaving ) {
				return;
			}

			wasSaving = false;

			// Skip if Layer 2 is handling a pending-publish intent — the
			// interim status change (to pending/draft) is expected during
			// the scan-before-publish flow.
			if ( pendingPublishIntent ) {
				return;
			}

			const savedStatus = editor.getCurrentPost()?.status;
			const editedStatus = editor.getEditedPostAttribute( 'status' );

			if (
				editedStatus === 'publish' &&
				( savedStatus === 'pending' || savedStatus === 'draft' )
			) {
				const statusLabel = savedStatus === 'pending'
					? __( 'Pending Review', 'accessibility-checker' )
					: __( 'Draft', 'accessibility-checker' );

				const notices = noticeRef.current;
				notices.removeNotice( 'edac-publish-blocked' );
				notices.createErrorNotice(
					sprintf(
						/* translators: %s: the new post status label */
						__(
							'Publishing blocked: accessibility issues were found. The post has been set to %s. Fix or ignore the issues in Accessibility Checker before publishing.',
							'accessibility-checker',
						),
						statusLabel,
					),
					{ id: 'edac-publish-blocked', isDismissible: true },
				);

				// Sync the editor so the UI does not show stale status.
				wp.data.dispatch( 'core/editor' ).editPost(
					{ status: savedStatus },
					{ undoIgnore: true },
				);
			}
		} );

		return () => {
			detectUnsub();
		};
	}, [ shouldBypassFlow ] );

	return null;
};

export default PostSaveBlockNotice;
