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

const DismissedIssues = () => {
	const { data, loading, error, refreshing } = useAccessibilityCheckerData();

	// If we have no data (still loading) let parent loaders show.
	if ( loading || error ) {
		return null;
	}

	const details = data?.details || {};
	const allErrors = details.errors || [];
	const allWarnings = details.warnings || [];

	// Count dismissed (ignored) issues
	const dismissedProblemsCount = useMemo( () => {
		return allErrors.reduce( ( sum, rule ) => {
			const dismissedIssues = ( rule.details || [] ).filter(
				( issue ) => issue.ignre === '1' || issue.ignre === 1,
			);
			return sum + dismissedIssues.length;
		}, 0 );
	}, [ allErrors ] );

	const dismissedWarningsCount = useMemo( () => {
		return allWarnings.reduce( ( sum, rule ) => {
			const dismissedIssues = ( rule.details || [] ).filter(
				( issue ) => issue.ignre === '1' || issue.ignre === 1,
			);
			return sum + dismissedIssues.length;
		}, 0 );
	}, [ allWarnings ] );

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

	const emptyMessages = {
		'dismissed-problems': __( 'No dismissed problems.', 'accessibility-checker' ),
		'dismissed-warnings': __( 'No dismissed items to review.', 'accessibility-checker' ),
	};

	// Build title with info icon
	const panelTitle = renderPanelTitleWithIcon(
		'info',
		__( 'Dismissed Issues', 'accessibility-checker' ),
		totalDismissedCount > 0 ? ` (${ totalDismissedCount })` : '',
		totalDismissedCount > 0 ? sprintf( __( '%d total', 'accessibility-checker' ), totalDismissedCount ) : '',
	);

	return (
		<IssuesPanel
			title={ panelTitle }
			initialOpen={ false }
			tabs={ tabs }
			refreshing={ refreshing }
			showIgnored={ true }
			emptyMessages={ emptyMessages }
			className="edac-dismissed-issues-panel"
		/>
	);
};

export default DismissedIssues;
