/**
 * Issue Modal API utilities
 */

import apiFetch from '@wordpress/api-fetch';

/**
 * Toggle issue ignore status via REST API
 *
 * @param {string}  issueId      - The issue ID to dismiss/restore.
 * @param {boolean} ignore       - True to dismiss, false to restore.
 * @param {string}  reason       - The reason for dismissing the issue.
 * @param {string}  comment      - Optional comment for the dismissal.
 * @param {boolean} ignoreGlobal - True to dismiss all instances of this issue across all pages (Pro only).
 * @return {Promise} Promise that resolves with the response data.
 */
export const toggleIssueDismiss = async ( issueId, ignore = true, reason = '', comment = '', ignoreGlobal = false ) => {
	return apiFetch( {
		path: `/accessibility-checker/v1/dismiss-issue/${ issueId }`,
		method: 'POST',
		data: {
			action: ignore ? 'dismiss' : 'undismiss',
			reason: ignore ? reason : '',
			comment: ignore ? comment : '',
			ignore_global: ignore && ignoreGlobal ? 1 : 0,
			largeBatch: ignore && ignoreGlobal,
		},
	} );
};
