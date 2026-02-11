/**
 * Accessibility Analysis Panel (Problems / Needs Review)
 */

import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { useAccessibilityCheckerData } from '../../hooks/useAccessibilityCheckerData';
import IssuesPanel from '../IssuesPanel';
import { renderPanelTitleWithIcon } from '../../utils/panelHelpers';

const AccessibilityAnalysis = () => {
	const { data, loading, error, refreshing } = useAccessibilityCheckerData();

	const details = data?.details || {};
	const problems = useMemo( () => details.errors || [], [ details ] );
	const warnings = useMemo( () => details.warnings || [], [ details ] );

	// If we have no data (still loading) let parent loaders show.
	if ( loading || error ) {
		return null;
	}

	// Count total active (non-ignored) issues for icon display.
	const problemCount = useMemo( () => {
		return problems.reduce( ( sum, rule ) => {
			const activeIssues = ( rule.details || [] ).filter(
				( issue ) => issue.ignre !== '1' && issue.ignre !== 1,
			);
			return sum + activeIssues.length;
		}, 0 );
	}, [ problems ] );

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

	const emptyMessages = {
		problems: __( 'No problems found.', 'accessibility-checker' ),
		warnings: __( 'No items to review.', 'accessibility-checker' ),
	};

	return (
		<IssuesPanel
			title={ renderPanelTitleWithIcon(
				iconName,
				__( 'Accessibility Analysis', 'accessibility-checker' ),
			) }
			initialOpen={ false }
			tabs={ tabs }
			refreshing={ refreshing }
			showIgnored={ false }
			emptyMessages={ emptyMessages }
			className="edac-accessibility-analysis"
		/>
	);
};

export default AccessibilityAnalysis;
