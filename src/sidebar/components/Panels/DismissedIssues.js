/**
 * Dismissed Issues Panel
 *
 * Displays issues that have been dismissed.
 */

import { __ } from '@wordpress/i18n';
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
