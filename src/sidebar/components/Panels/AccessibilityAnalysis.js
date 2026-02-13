/**
 * Accessibility Analysis Panel (Problems / Needs Review)
 */

import { __, sprintf } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { useAccessibilityCheckerData } from '../../hooks/useAccessibilityCheckerData';
import IssuesPanel from '../IssuesPanel';
import { renderPanelTitleWithIcon } from '../../utils/panelHelpers';

// Count active (non-ignored) issues in a set of rules.
const countActiveIssues = ( rules = [] ) => rules.reduce( ( sum, rule ) => {
	const activeIssues = ( rule?.details || [] ).filter(
		( issue ) => issue.ignre !== '1' && issue.ignre !== 1,
	);
	return sum + activeIssues.length;
}, 0 );

const AccessibilityAnalysis = () => {
	const { data, loading, error } = useAccessibilityCheckerData();

	const details = data?.details || {};
	const problems = useMemo( () => details.errors || [], [ details ] );
	const warnings = useMemo( () => details.warnings || [], [ details ] );

	// If we have no data (still loading) let parent loaders show.
	if ( loading || error ) {
		return null;
	}

	// Count total active (non-ignored) issues for icon display.
	const problemCount = useMemo( () => countActiveIssues( problems ), [ problems ] );

	// Count total active (non-ignored) warnings.
	const warningCount = useMemo( () => countActiveIssues( warnings ), [ warnings ] );

	// Calculate total issue count (problems + warnings).
	const totalIssueCount = problemCount + warningCount;

	// Determine which icon to show, error if any problems, warning otherwise.
	let iconName = null;
	if ( problemCount > 0 ) {
		iconName = 'error';
	} else {
		iconName = 'warning';
	}

	const tabs = [
		{
			name: 'problems',
			label: __( 'Problems', 'accessibility-checker' ),
			items: problems,
		},
		{
			name: 'warnings',
			label: __( 'Needs Review', 'accessibility-checker' ),
			items: warnings,
		},
	];

	return (
		<IssuesPanel
			panelId="accessibility-analysis"
			title={ renderPanelTitleWithIcon(
				iconName,
				__( 'Accessibility Analysis', 'accessibility-checker' ),
				totalIssueCount > 0 ? ` (${ totalIssueCount })` : '',
				totalIssueCount > 0 ? sprintf( __( '%d total issues', 'accessibility-checker' ), totalIssueCount ) : '',
			) }
			initialOpen={ false }
			tabs={ tabs }
			showIgnored={ false }
			className="edac-accessibility-analysis"
		/>
	);
};

export default AccessibilityAnalysis;
