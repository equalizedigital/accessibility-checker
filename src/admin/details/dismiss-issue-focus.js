/**
 * Focus helpers for a dismissed issue in the server-rendered details panel.
 */

const RULE_PANEL_SELECTOR = '.edac-details-rule-records';
const RULE_BUTTON_SELECTOR = '.edac-details-rule-title-arrow';
const RULE_TITLE_SELECTOR = '.edac-details-rule-title';
const ISSUE_RECORD_SELECTOR = '.edac-details-rule-records-record';
const ISSUE_RECORD_ID_PREFIX = 'edac-details-rule-records-record-';
const ISSUE_RECORD_IGNORE_PREFIX = 'edac-details-rule-records-record-ignore-';
const ISSUE_RECORD_DISMISS_BUTTON = '.edac-details-rule-records-record-ignore-submit';

/**
 * Find the rule button that controls a panel.
 *
 * @param {Document|Element} root    The root to search.
 * @param {string}           panelId The controlled panel ID.
 * @return {HTMLElement|null} The matching rule display button.
 */
const findRuleDisplayBtn = ( root, panelId ) => {
	if ( ! panelId ) {
		return null;
	}

	const rulePanelDisplayButtons = Array.from( root.querySelectorAll( RULE_BUTTON_SELECTOR ) );
	const rulePanelDisplayButton = rulePanelDisplayButtons.find( ( button ) => button.getAttribute( 'aria-controls' ) === panelId ) || null;

	return rulePanelDisplayButton;
};

/**
 * Capture stable focus context for the issue being dismissed or reopened.
 *
 * @param {HTMLElement} submitButton The dismiss or reopen submit button.
 * @return {Object|null} The dismissal focus context.
 */
export const captureDismissIssueFocusContext = ( submitButton ) => {
	const issueRecord = submitButton?.closest( ISSUE_RECORD_SELECTOR );
	const rulePanel = issueRecord?.closest( RULE_PANEL_SELECTOR );
	const issueId = submitButton?.dataset?.id;

	if ( ! issueRecord || ! rulePanel || ! issueId ) {
		return null;
	}

	return {
		issueId: String( issueId ),
		rulePanelId: rulePanel.id,
	};
};

/**
 * Find an issue's dismiss button by its issue ID.
 *
 * @param {Document}    root    The document containing the metabox.
 * @param {string|null} issueId The issue ID.
 * @return {HTMLElement|null} The issue button.
 */
const findIssueDismissBtn = ( root, issueId ) => {
	const issue = root.getElementById( ISSUE_RECORD_ID_PREFIX + issueId );

	const dismissBtn = issue?.querySelector( ISSUE_RECORD_DISMISS_BUTTON ) || null;

	return dismissBtn;
};

/**
 * Expand the acted rule and restore focus after its markup is replaced.
 *
 * @param {Object|null} context The dismissal focus context.
 * @param {Document}    root    The document containing the metabox.
 * @return {HTMLElement|null} The focused element, or null.
 */
export const restoreDismissIssueFocus = ( context, root = document ) => {
	if ( ! context ) {
		return null;
	}

	const rulePanel = root.getElementById( context.rulePanelId );
	const ruleDisplayBtn = findRuleDisplayBtn( root, context.rulePanelId );
	const rulePanelIgnore = root.getElementById( ISSUE_RECORD_IGNORE_PREFIX + context.issueId );

	if ( rulePanel ) {
		rulePanel.style.display = 'block';

		// Add active class to title and update aria on button
		ruleDisplayBtn?.closest( RULE_TITLE_SELECTOR )?.classList.add( 'active' );
		ruleDisplayBtn?.setAttribute( 'aria-expanded', 'true' );
	}

	if ( rulePanelIgnore ) {
		rulePanelIgnore.style.display = 'block';
	}

	const focusTarget = findIssueDismissBtn( root, context.issueId );

	focusTarget?.focus();

	return focusTarget;
};
