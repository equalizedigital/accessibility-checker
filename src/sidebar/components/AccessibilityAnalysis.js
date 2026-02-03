/**
 * Accessibility Analysis Panel (Problems / Needs Review)
 */

import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { useAccessibilityCheckerData } from '../hooks/useAccessibilityCheckerData';
import IssuesPanel from './IssuesPanel';

const AccessibilityAnalysis = () => {
	const { data, loading, error, refreshing } = useAccessibilityCheckerData();

	const details = data?.details || {};
	const problems = useMemo( () => details.errors || [], [ details ] );
	const warnings = useMemo( () => details.warnings || [], [ details ] );

	// If we have no data (still loading) let parent loaders show.
	if ( loading || error ) {
		return null;
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
			title={ __( 'Accessibility Analysis', 'accessibility-checker' ) }
			initialOpen={ false }
			tabs={ tabs }
			refreshing={ refreshing }
			showIgnored={ false }
			emptyMessages={ emptyMessages }
		/>
	);
};

export default AccessibilityAnalysis;
