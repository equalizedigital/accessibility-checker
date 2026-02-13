/**
 * Dismissed Issues Panel
 *
 * Displays issues that have been dismissed.
 */

import { __, sprintf } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { useAccessibilityCheckerData } from '../../hooks/useAccessibilityCheckerData';
import IssuesPanel from '../IssuesPanel';
import { renderPanelTitleWithIcon } from '../../utils/panelHelpers';

// Count dismissed (ignored) issues across a set of rules.
const countDismissedIssues = ( rules = [] ) => rules.reduce( ( sum, rule ) => {
	const dismissedIssues = ( rule?.details || [] ).filter(
		( issue ) => issue.ignre === '1' || issue.ignre === 1,
	);
	return sum + dismissedIssues.length;
}, 0 );

const DismissedIssues = () => {
	const { data, loading, error } = useAccessibilityCheckerData();

	// If we have no data (still loading) let parent loaders show.
	if ( loading || error ) {
		return null;
	}

	const details = data?.details || {};
	const allErrors = details.errors || [];
	const allWarnings = details.warnings || [];

	// Count dismissed (ignored) issues
	const dismissedProblemsCount = useMemo( () => countDismissedIssues( allErrors ), [ allErrors ] );

	const dismissedWarningsCount = useMemo( () => countDismissedIssues( allWarnings ), [ allWarnings ] );

	// Calculate total dismissed issue count
	const totalDismissedCount = dismissedProblemsCount + dismissedWarningsCount;

	const tabs = [
		{
			name: 'dismissed-problems',
			label: __( 'Problems', 'accessibility-checker' ),
			items: allErrors,
		},
		{
			name: 'dismissed-warnings',
			label: __( 'Needs Review', 'accessibility-checker' ),
			items: allWarnings,
		},
	];

	// Build title with info icon
	const panelTitle = renderPanelTitleWithIcon(
		'info',
		__( 'Dismissed Issues', 'accessibility-checker' ),
		totalDismissedCount > 0 ? ` (${ totalDismissedCount })` : '',
		totalDismissedCount > 0 ? sprintf( __( '%d total', 'accessibility-checker' ), totalDismissedCount ) : '',
	);

	return (
		<IssuesPanel
			panelId="dismissed-issues"
			title={ panelTitle }
			initialOpen={ false }
			tabs={ tabs }
			showIgnored={ true }
			className="edac-dismissed-issues-panel"
		/>
	);
};

export default DismissedIssues;
