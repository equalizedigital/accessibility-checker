/**
 * Generic Issues Panel Component
 *
 * Reusable panel for displaying accessibility issues with tabs.
 * Can be configured for different contexts (e.g., active issues, dismissed issues).
 */

import { __, sprintf } from '@wordpress/i18n';
import { Panel, PanelBody, TabPanel } from '@wordpress/components';
import { useState, useMemo } from '@wordpress/element';
import RuleAccordion from './RuleAccordion';
import '../sass/components/accessibility-analysis.scss';
import '../sass/components/accessibility-analysis-tabs.scss';

/**
 * Issues Panel Component
 *
 * @param {Object}  props             - Component props.
 * @param {string}  props.title       - Panel title.
 * @param {boolean} props.initialOpen - Whether panel is initially open.
 * @param {Array}   props.tabs        - Array of tab configurations with rule items.
 * @param {boolean} props.refreshing  - Whether data is refreshing.
 * @param {boolean} props.showIgnored - If true, show only ignored issues. If false, show only non-ignored.
 * @param {string}  props.className   - Additional CSS class.
 */
const IssuesPanel = ( {
	title,
	initialOpen = false,
	tabs = [],
	refreshing = false,
	showIgnored = null,
	className = '',
} ) => {
	const [ expandedRules, setExpandedRules ] = useState( {} );

	const toggleRule = ( ruleId ) => {
		setExpandedRules( ( prev ) => ( {
			...prev,
			[ ruleId ]: ! prev[ ruleId ],
		} ) );
	};

	// Filter rules by their issues' ignored status
	const filterRulesByIgnoredStatus = ( rules ) => {
		if ( showIgnored === null || showIgnored === undefined ) {
			return rules;
		}

		return rules
			.map( ( rule ) => {
				// Filter details by ignored status
				const filteredDetails = ( rule.details || [] ).filter( ( issue ) => {
					const isIgnored = issue.ignre === '1' || issue.ignre === 1;
					return showIgnored ? isIgnored : ! isIgnored;
				} );

				// Only include rule if it has matching issues
				if ( filteredDetails.length === 0 ) {
					return null;
				}

				return {
					...rule,
					details: filteredDetails,
					count: filteredDetails.length,
				};
			} )
			.filter( Boolean );
	};

	// Build tabs with counts
	const tabsWithCounts = useMemo( () => {
		return tabs.map( ( tab ) => {
			let rules = tab.items || [];

			// Apply ignored status filter
			rules = filterRulesByIgnoredStatus( rules );

			const count = rules.reduce(
				( sum, rule ) => sum + ( rule.details?.length || 0 ),
				0,
			);

			return {
				...tab,
				rules,
				count,
				title: (
					<>
						{ tab.label }
						{ count > 0 && (
							<>
								<span className="edac-analysis__count" aria-hidden="true">
									({ count })
								</span>
								<span className="screen-reader-text">
									, { sprintf( __( '%d total', 'accessibility-checker' ), count ) }
								</span>
							</>
						) }
					</>
				),
				className: 'edac-analysis__tab',
			};
		} );
	}, [ tabs, showIgnored ] );

	// Prefer the first non-empty tab as the initial selection
	const initialTab = useMemo( () => {
		const nonEmptyTab = tabsWithCounts.find( ( tab ) => tab.count > 0 );
		return nonEmptyTab?.name || tabsWithCounts[ 0 ]?.name;
	}, [ tabsWithCounts ] );

	const renderTabContent = ( tab ) => {
		const currentTab = tabsWithCounts.find( ( t ) => t.name === tab.name );
		const rules = currentTab?.rules || [];
		const hasRules = rules.length > 0;

		return (
			<div className="edac-analysis__panel" role="tabpanel">
				{ refreshing && (
					<p className="edac-analysis__message">
						{ __( 'Updating accessibility data...', 'accessibility-checker' ) }
					</p>
				) }
				{ ! refreshing && hasRules && (
					<div className="edac-analysis__rules">
						{ rules.map( ( rule ) => {
							const ruleId = rule.slug || rule.id || rule.title;
							return (
								<RuleAccordion
									key={ ruleId }
									rule={ rule }
									isExpanded={ expandedRules[ ruleId ] || false }
									onToggle={ () => toggleRule( ruleId ) }
									showIgnored={ showIgnored }
								/>
							);
						} ) }
					</div>
				) }
				{ ! refreshing && ! hasRules && (
					<p className="edac-analysis__message">
						{ showIgnored
							? __( 'No dismissed issues.', 'accessibility-checker' )
							: __( 'No issues found.', 'accessibility-checker' ) }
					</p>
				) }
			</div>
		);
	};

	return (
		<Panel className={ `edac-analysis-panel ${ className }` }>
			<PanelBody title={ title } initialOpen={ initialOpen }>
				{ tabsWithCounts.length > 0 && (
					<TabPanel
						className="edac-analysis__tabs"
						tabs={ tabsWithCounts }
						initialTabName={ initialTab }
						selectOnMove={ false }
					>
						{ renderTabContent }
					</TabPanel>
				) }
			</PanelBody>
		</Panel>
	);
};

export default IssuesPanel;
