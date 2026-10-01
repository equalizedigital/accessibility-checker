/**
 * Dismiss (ignore) issue support for the frontend highlighter.
 *
 * Mirrors the editor's DismissPanel (src/issueModal/components/DismissPanel.js)
 * in plain markup, since the highlighter does not load React. Both use the
 * same `dismiss-issue` REST endpoint.
 */

import { __, sprintf } from '@wordpress/i18n';

export const DISMISS_FORM_ID = 'edac-highlight-dismiss-form';
export const DISMISS_COMMENT_ID = 'edac-highlight-dismiss-comment';

/**
 * Escape a string for safe insertion into HTML markup.
 *
 * @param {*} value The value to escape.
 * @return {string} The escaped string.
 */
export const escapeHtml = ( value ) => String( value ?? '' )
	.replace( /&/g, '&amp;' )
	.replace( /</g, '&lt;' )
	.replace( />/g, '&gt;' )
	.replace( /"/g, '&quot;' )
	.replace( /'/g, '&#039;' );

/**
 * Whether an issue is currently dismissed.
 *
 * @param {Object} issue The issue.
 * @return {boolean} True when dismissed.
 */
export const isIssueDismissed = ( issue ) => String( issue?.ignored ) === '1';

/**
 * Whether an issue was dismissed globally (across all pages).
 *
 * @param {Object} issue The issue.
 * @return {boolean} True when globally dismissed.
 */
export const isIssueGloballyDismissed = ( issue ) => String( issue?.ignre_global ) === '1';

/**
 * Build the dismiss section markup for an issue.
 *
 * @param {Object}  args                  Arguments.
 * @param {Object}  args.issue            The issue being shown.
 * @param {Object}  args.reasons          Dismiss reasons keyed by slug, each with label and description.
 * @param {boolean} args.canDismiss       Whether the user may dismiss/reopen this issue.
 * @param {boolean} args.canDismissGlobal Whether the user may dismiss/reopen across all pages.
 * @return {string} The markup, or an empty string when there is nothing to show.
 */
export function buildDismissMarkup( { issue, reasons = {}, canDismiss = false, canDismissGlobal = false } ) {
	if ( ! issue ) {
		return '';
	}

	if ( isIssueDismissed( issue ) ) {
		return buildDismissedMarkup( { issue, reasons, canDismiss, canDismissGlobal } );
	}

	if ( ! canDismiss ) {
		return '';
	}

	const reasonEntries = Object.entries( reasons || {} );
	const selectedReason = reasons?.[ issue.ignre_reason ] ? issue.ignre_reason : reasonEntries[ 0 ]?.[ 0 ];

	const reasonsHtml = reasonEntries.map( ( [ value, data ], index ) => {
		const inputId = `edac-highlight-dismiss-reason-${ index }`;
		const descriptionId = `${ inputId }-description`;
		return `<div class="edac-highlight-dismiss-reason">
			<input type="radio" id="${ inputId }" name="edac-highlight-dismiss-reason" value="${ escapeHtml( value ) }"${ value === selectedReason ? ' checked' : '' }${ data?.description ? ` aria-describedby="${ descriptionId }"` : '' } />
			<label for="${ inputId }">${ escapeHtml( data?.label ) }</label>
			${ data?.description ? `<p id="${ descriptionId }" class="edac-highlight-dismiss-reason-description">${ escapeHtml( data.description ) }</p>` : '' }
		</div>`;
	} ).join( '' );

	const arrowUri = 'data:image/svg+xml,' + encodeURIComponent( '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path d="M6.5 12.4L12 8l5.5 4.4-.9 1.2L12 10l-4.5 3.6-1-1.2z" fill="#2271b1"/></svg>' );

	return `<div class="edac-highlight-panel-description-dismiss">
		<button type="button" class="edac-highlight-panel-description-dismiss-toggle" aria-expanded="false" aria-controls="${ DISMISS_FORM_ID }">${ __( 'Dismiss Issue', 'accessibility-checker' ) } <img src="${ arrowUri }" width="16" height="16" class="edac-highlight-panel-description-dismiss-toggle-arrow" alt="" /></button>
		<form id="${ DISMISS_FORM_ID }" class="edac-highlight-dismiss-form" hidden>
			<fieldset>
				<legend>${ __( 'Dismiss issue as:', 'accessibility-checker' ) }</legend>
				${ reasonsHtml }
			</fieldset>
			<label class="edac-highlight-dismiss-comment-label" for="${ DISMISS_COMMENT_ID }">${ __( 'Comment (optional)', 'accessibility-checker' ) }</label>
			<textarea id="${ DISMISS_COMMENT_ID }" class="edac-highlight-dismiss-comment" rows="3" aria-describedby="${ DISMISS_COMMENT_ID }-help">${ escapeHtml( issue.ignre_comment ) }</textarea>
			<p id="${ DISMISS_COMMENT_ID }-help" class="edac-highlight-dismiss-help">${ __( 'Add a note explaining why this issue is being dismissed.', 'accessibility-checker' ) }</p>
			<p class="edac-highlight-dismiss-error" role="alert"></p>
			<div class="edac-highlight-dismiss-actions">
				<button type="submit" class="edac-highlight-panel-description--button edac-highlight-dismiss-submit" data-scope="single">${ __( 'Dismiss Issue', 'accessibility-checker' ) }</button>
				${ canDismissGlobal ? `<button type="button" class="edac-highlight-panel-description--button edac-highlight-dismiss-submit edac-highlight-dismiss-submit--global" data-scope="global">${ __( 'Dismiss Globally', 'accessibility-checker' ) }</button>` : '' }
			</div>
		</form>
	</div>`;
}

/**
 * Build the markup shown for an already-dismissed issue.
 *
 * @param {Object}  args                  Arguments.
 * @param {Object}  args.issue            The dismissed issue.
 * @param {Object}  args.reasons          Dismiss reasons keyed by slug.
 * @param {boolean} args.canDismiss       Whether the user may reopen this issue.
 * @param {boolean} args.canDismissGlobal Whether the user may remove a global dismissal.
 * @return {string} The markup.
 */
function buildDismissedMarkup( { issue, reasons, canDismiss, canDismissGlobal } ) {
	const isGlobal = isIssueGloballyDismissed( issue );
	const reasonLabel = reasons?.[ issue.ignre_reason ]?.label;

	const heading = reasonLabel
		// translators: %s: dismiss reason label e.g. "False positive".
		? sprintf( __( 'Issue Dismissed — %s', 'accessibility-checker' ), reasonLabel )
		: __( 'Issue Dismissed', 'accessibility-checker' );

	let meta = '';
	if ( isGlobal ) {
		meta += `<dt>${ __( 'Scope:', 'accessibility-checker' ) }</dt><dd>${ __( 'All pages', 'accessibility-checker' ) }</dd>`;
	}
	if ( issue.ignre_user_name ) {
		meta += `<dt>${ __( 'By:', 'accessibility-checker' ) }</dt><dd>${ escapeHtml( issue.ignre_user_name ) }</dd>`;
	}
	if ( issue.ignre_date ) {
		meta += `<dt>${ __( 'On:', 'accessibility-checker' ) }</dt><dd>${ escapeHtml( issue.ignre_date ) }</dd>`;
	}

	const commentHtml = issue.ignre_comment
		? `<div class="edac-highlight-dismissed-comment">
			<p class="edac-highlight-dismissed-comment-label">${ __( 'Reason for dismissal:', 'accessibility-checker' ) }</p>
			<div class="edac-highlight-dismissed-comment-body">${ escapeHtml( issue.ignre_comment ) }</div>
		</div>`
		: '';

	const canReopen = isGlobal ? canDismissGlobal : canDismiss;
	const reopenLabel = isGlobal
		? __( 'Remove Global Dismissal', 'accessibility-checker' )
		: __( 'Reopen Issue', 'accessibility-checker' );

	return `<div class="edac-highlight-panel-description-dismiss edac-highlight-panel-description-dismiss--dismissed">
		<div class="edac-highlight-dismissed-heading" role="heading" aria-level="4">${ escapeHtml( heading ) }</div>
		${ meta ? `<dl class="edac-highlight-dismissed-meta">${ meta }</dl>` : '' }
		${ commentHtml }
		<p class="edac-highlight-dismiss-error" role="alert"></p>
		${ canReopen ? `<button type="button" class="edac-highlight-panel-description--button edac-highlight-dismiss-reopen">${ reopenLabel }</button>` : '' }
	</div>`;
}

/**
 * Send a dismiss or reopen request to the REST API.
 *
 * @param {Object}  args           Arguments.
 * @param {string}  args.restUrl   Base REST URL for the plugin namespace.
 * @param {string}  args.restNonce REST nonce.
 * @param {string}  args.issueId   The issue id.
 * @param {boolean} args.dismiss   True to dismiss, false to reopen.
 * @param {string}  args.reason    Dismiss reason slug.
 * @param {string}  args.comment   Optional comment.
 * @param {boolean} args.global    Apply to every matching instance on every page.
 * @return {Promise<Object>} Resolves with the response body, rejects with an Error.
 */
export async function requestDismiss( { restUrl, restNonce, issueId, dismiss, reason = '', comment = '', global = false } ) {
	const response = await fetch( `${ restUrl }/dismiss-issue/${ encodeURIComponent( issueId ) }`, {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': restNonce,
		},
		body: JSON.stringify( {
			action: dismiss ? 'dismiss' : 'undismiss',
			reason: dismiss ? reason : '',
			comment: dismiss ? comment : '',
			ignore_global: dismiss && global ? 1 : 0,
			largeBatch: global,
		} ),
	} );

	let body = null;
	try {
		body = await response.json();
	} catch ( e ) {
		// Non-JSON response, handled below.
	}

	if ( ! response.ok || ! body?.success ) {
		throw new Error( body?.message || __( 'The issue could not be updated. Please try again.', 'accessibility-checker' ) );
	}

	return body;
}

/**
 * Update local issue state after a successful dismiss/reopen.
 *
 * For a global action every issue on the page with the same rule and markup is
 * updated too, matching what the server changed.
 *
 * @param {Array}   issues          All issues on the page.
 * @param {Object}  target          The issue that was acted on.
 * @param {Object}  args            Arguments.
 * @param {boolean} args.dismiss    True when dismissed, false when reopened.
 * @param {boolean} args.global     Whether the action was global.
 * @param {string}  args.reason     The reason sent.
 * @param {string}  args.comment    The comment sent.
 * @param {Object}  [args.response] The REST response body.
 * @return {Array} The issues that were updated, each with its previous rule_type.
 */
export function applyDismissToIssues( issues, target, { dismiss, global, reason = '', comment = '', response = {} } ) {
	const affected = global
		? issues.filter( ( issue ) => issue.slug === target.slug && issue.object === target.object )
		: [ target ];

	if ( ! affected.includes( target ) ) {
		affected.push( target );
	}

	return affected.map( ( issue ) => {
		const previousRuleType = issue.rule_type;
		if ( dismiss ) {
			issue.ignored = '1';
			issue.rule_type = 'ignored';
			issue.ignre_reason = response.ignre_reason || reason;
			issue.ignre_comment = comment;
			issue.ignre_user_name = response.ignre_user_name || '';
			issue.ignre_date = response.ignre_date || '';
			issue.ignre_global = global ? 1 : 0;
		} else {
			issue.ignored = '0';
			issue.rule_type = issue.base_rule_type || previousRuleType;
			issue.ignre_global = 0;
			issue.ignre_user_name = '';
			issue.ignre_date = '';
			// Keep reason and comment so the form is pre-filled if dismissed again.
		}
		return { issue, previousRuleType };
	} );
}
