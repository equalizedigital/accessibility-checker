/**
 * Accessibility Analysis Panel (Problems / Needs Review)
 */

import { __ } from '@wordpress/i18n';
import { Panel, PanelBody } from '@wordpress/components';
import { useState, useMemo } from '@wordpress/element';
import { useAccessibilityCheckerData } from '../hooks/useAccessibilityCheckerData';
import AccessibilityAnalysisTabs from './AccessibilityAnalysisTabs';
import RuleAccordion from './RuleAccordion';
import '../sass/components/accessibility-analysis.scss';
import '../sass/components/issue-details-modal.scss';

const AccessibilityAnalysis = () => {
	const { data, loading, error, refreshing } = useAccessibilityCheckerData();
	const [ expandedRules, setExpandedRules ] = useState( {} );

	const details = data?.details || {};
	const problems = useMemo( () => details.errors || [], [ details ] );
	const warnings = useMemo( () => details.warnings || [], [ details ] );

	// If we have no data (still loading) let parent loaders show.
	if ( loading || error ) {
		return null;
	}

	const toggleRule = ( ruleId ) => {
		setExpandedRules( ( prev ) => ( {
			...prev,
			[ ruleId ]: ! prev[ ruleId ],
		} ) );
	};

	return (
		<Panel className="edac-analysis-panel">
			<PanelBody
				title={ __( 'Accessibility Analysis', 'accessibility-checker' ) }
				initialOpen={ false }
			>
				<AccessibilityAnalysisTabs
					problems={ problems }
					warnings={ warnings }
					refreshing={ refreshing }
					expandedRules={ expandedRules }
					onToggleRule={ toggleRule }
					RuleAccordion={ RuleAccordion }
				/>
			</PanelBody>
		</Panel>
	);
};

export default AccessibilityAnalysis;

