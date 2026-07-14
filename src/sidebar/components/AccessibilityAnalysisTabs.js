/**
 * Accessibility Analysis Tabs
 */

import { __ } from '@wordpress/i18n';
import { TabPanel } from '@wordpress/components';
import '../sass/components/accessibility-analysis-tabs.scss';

const TAB_PROBLEMS = 'problems';
const TAB_WARNINGS = 'warnings';

const AccessibilityAnalysisTabs = ( {
	problems,
	warnings,
	refreshing,
	expandedRules,
	onToggleRule,
	RuleAccordion,
} ) => {
	const totalProblems = problems.reduce( ( sum, rule ) => sum + ( rule.count || rule.details?.length || 0 ), 0 );
	const totalWarnings = warnings.reduce( ( sum, rule ) => sum + ( rule.count || rule.details?.length || 0 ), 0 );

	const tabs = [
		{
			name: TAB_PROBLEMS,
			title: (
				<>
					{ __( 'Problems', 'accessibility-checker' ) }
					<span className="edac-analysis__count">{ totalProblems }</span>
				</>
			),
			className: 'edac-analysis__tab',
		},
		{
			name: TAB_WARNINGS,
			title: (
				<>
					{ __( 'Needs Review', 'accessibility-checker' ) }
					<span className="edac-analysis__count">{ totalWarnings }</span>
				</>
			),
			className: 'edac-analysis__tab',
		},
	];

	const renderTabContent = ( tab ) => {
		const currentItems = tab.name === TAB_PROBLEMS ? problems : warnings;
		const hasItems = currentItems.length > 0;

		return (
			<div className="edac-analysis__panel" role="tabpanel">
				{ refreshing && (
					<p className="edac-analysis__message">
						{ __( 'Updating accessibility data...', 'accessibility-checker' ) }
					</p>
				) }
				{ ! refreshing && hasItems && (
					<div className="edac-analysis__rules">
						{ currentItems.map( ( rule ) => {
							const ruleId = rule.slug || rule.id || rule.title;
							return (
								<RuleAccordion
									key={ ruleId }
									rule={ rule }
									isExpanded={ expandedRules[ ruleId ] || false }
									onToggle={ () => onToggleRule( ruleId ) }
								/>
							);
						} ) }
					</div>
				) }
				{ ! refreshing && ! hasItems && (
					<p className="edac-analysis__message">
						{ tab.name === TAB_PROBLEMS
							? __( 'No problems found.', 'accessibility-checker' )
							: __( 'No items to review.', 'accessibility-checker' ) }
					</p>
				) }
			</div>
		);
	};

	return (
		<TabPanel
			className="edac-analysis__tabs"
			tabs={ tabs }
			initialTabName={ TAB_PROBLEMS }
			selectOnMove={ false }
		>
			{ renderTabContent }
		</TabPanel>
	);
};

export default AccessibilityAnalysisTabs;
