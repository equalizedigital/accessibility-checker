/**
 * Accessibility Checker Pre-Publish Panel
 *
 * Shown in the Gutenberg pre-publish checklist. For free users it shows an
 * upgrade notice. For Pro users it shows a warning (soft mode) or an error
 * (hard mode) when accessibility issues exist. Saving is never blocked —
 * a save is required to trigger a new scan so issues can be cleared.
 */

import { __, _n, _x, sprintf } from '@wordpress/i18n';
import { PluginPrePublishPanel } from '@wordpress/editor';
import { Notice } from '@wordpress/components';
import { useAccessibilityCheckerData } from '../hooks/useAccessibilityCheckerData';

/**
 * Pre-publish panel component for the Gutenberg block editor.
 *
 * @return {JSX.Element|null} The panel element or null when nothing should render.
 */
const PrePublishPanel = () => {
	const { data } = useAccessibilityCheckerData();

	const settings = window.edac_sidebar_app || {};
	const isPro = !! settings.isPro;
	const blockPublish = !! settings.blockPublish;
	const blockPublishMode = settings.blockPublishMode || 'soft';
	const blockPublishOnErrors = !! settings.blockPublishOnErrors;
	const blockPublishOnWarnings = !! settings.blockPublishOnWarnings;
	const userCanBypass = !! settings.userCanBypassPublishBlock;
	const proUpgradeUrl = settings.proUpgradeUrl || 'https://equalizedigital.com/accessibility-checker/pricing/';

	const hasScanData = data?.summary !== undefined && data.summary !== null;
	const errorCount = hasScanData
		? ( ( data.summary?.errors || 0 ) + ( data.summary?.contrast_errors || 0 ) )
		: 0;
	const warningCount = hasScanData ? ( data.summary?.warnings || 0 ) : 0;

	// Determine whether there are issues that should trigger enforcement.
	const shouldEnforce = isPro && blockPublish && ! userCanBypass && hasScanData;
	const hasBlockingErrors = blockPublishOnErrors && errorCount > 0;
	const hasBlockingWarnings = blockPublishOnWarnings && warningCount > 0;
	const hasBlockingIssues = shouldEnforce && ( hasBlockingErrors || hasBlockingWarnings );

	// --- Free upgrade notice ---
	if ( ! isPro ) {
		return (
			<PluginPrePublishPanel
				title={ __( 'Accessibility Checker', 'accessibility-checker' ) }
				initialOpen={ false }
			>
				<p>
					{ __( 'Block publishing when accessibility issues are found with', 'accessibility-checker' ) }{ ' ' }
					<a
						href={ proUpgradeUrl }
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'Accessibility Checker Pro', 'accessibility-checker' ) }
					</a>
					{ '.' }
				</p>
			</PluginPrePublishPanel>
		);
	}

	// Feature disabled or user can bypass — render nothing for Pro users.
	if ( ! shouldEnforce ) {
		return null;
	}

	// Post not yet scanned — no data to reason about, allow publish.
	if ( ! hasScanData ) {
		return null;
	}

	// No blocking issues — render nothing.
	if ( ! hasBlockingIssues ) {
		return null;
	}

	// Build counts summary for the notice.
	const parts = [];
	if ( hasBlockingErrors ) {
		parts.push(
			sprintf(
				// translators: %d: number of error-level issues
				_n( '%d error', '%d errors', errorCount, 'accessibility-checker' ),
				errorCount,
			),
		);
	}
	if ( hasBlockingWarnings ) {
		parts.push(
			sprintf(
				// translators: %d: number of warning-level issues
				_n( '%d warning', '%d warnings', warningCount, 'accessibility-checker' ),
				warningCount,
			),
		);
	}

	// translators: used between list items (e.g. "3 errors, 1 warning")
	const issueSummary = parts.join( _x( ', ', 'list separator', 'accessibility-checker' ) );

	const noticeMessage =
		blockPublishMode === 'hard'
			? sprintf(
				// translators: %s: issue summary such as "3 errors" or "2 errors, 1 warning"
				__( 'Publishing is blocked. This content has %s. Fix or ignore the issues in Accessibility Checker, then save the post to re-scan before publishing.', 'accessibility-checker' ),
				issueSummary,
			)
			: sprintf(
				// translators: %s: issue summary such as "3 errors" or "2 errors, 1 warning"
				__( 'This content has %s. Review accessibility issues before publishing.', 'accessibility-checker' ),
				issueSummary,
			);

	return (
		<PluginPrePublishPanel
			title={ __( 'Accessibility Issues Found', 'accessibility-checker' ) }
			initialOpen={ true }
		>
			<Notice
				status={ blockPublishMode === 'hard' ? 'error' : 'warning' }
				isDismissible={ false }
			>
				{ noticeMessage }
			</Notice>
		</PluginPrePublishPanel>
	);
};

export default PrePublishPanel;
