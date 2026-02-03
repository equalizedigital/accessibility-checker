/**
 * Generic Issues Panel Component
 *
 * Reusable panel for displaying accessibility issues with tabs.
 * Can be configured for different contexts (e.g., active issues, ignored issues).
 */

import { __ } from '@wordpress/i18n';
import { Panel, PanelBody, TabPanel } from '@wordpress/components';
import { useState, useMemo } from '@wordpress/element';
import RuleAccordion from './RuleAccordion';
import '../sass/components/accessibility-analysis.scss';
import '../sass/components/accessibility-analysis-tabs.scss';

/**
 * Issues Panel Component
 *
 * @param {Object}   props               - Component props.
 * @param {string}   props.title         - Panel title.
 * @param {boolean}  props.initialOpen   - Whether panel is initially open.
 * @param {Array}    props.tabs          - Array of tab configurations.
 * @param {boolean}  props.refreshing    - Whether data is refreshing.
 * @param {boolean}  props.showIgnored   - If true, show only ignored issues. If false, show only non-ignored. If null/undefined, show all.
 * @param {Function} props.filterIssues  - Optional function to filter issues per tab.
 * @param {Object}   props.emptyMessages - Custom empty state messages per tab.
 * @param {string}   props.className     - Additional CSS class.
 */
const IssuesPanel = ( {
	title,
	initialOpen = false,
	tabs = [],
	refreshing = false,
	showIgnored = null,
	filterIssues = null,
	emptyMessages = {},
	className = '',
} ) => {
	const [ expandedRules, setExpandedRules ] = useState( {} );

	const toggleRule = ( ruleId ) => {
		setExpandedRules( ( prev ) => ( {
			...prev,
			[ ruleId ]: ! prev[ ruleId ],
		} ) );
	};

	// Filter items by ignored status if showIgnored is specified
	const filterByIgnoredStatus = ( items ) => {
		if ( showIgnored === null || showIgnored === undefined ) {
			return items;
		}

		return items
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
			let items = tab.items || [];

			// First apply ignored status filter if specified
			items = filterByIgnoredStatus( items );

			// Then apply custom filter if provided
			if ( filterIssues && tab.name ) {
				items = filterIssues( items, tab.name );
			}

			const count = items.reduce(
				( sum, rule ) => sum + ( rule.count || rule.details?.length || 0 ),
				0,
			);

			return {
				...tab,
				items,
				count,
				title: (
					<>
						{ tab.label }
						<span className="edac-analysis__count">{ count }</span>
					</>
				),
				className: 'edac-analysis__tab',
			};
		} );
	}, [ tabs, showIgnored, filterIssues ] );

	const renderTabContent = ( tab ) => {
		const currentTab = tabsWithCounts.find( ( t ) => t.name === tab.name );
		const items = currentTab?.items || [];
		const hasItems = items.length > 0;
		const emptyMessage = emptyMessages[ tab.name ] || __( 'No items found.', 'accessibility-checker' );

		return (
			<div className="edac-analysis__panel" role="tabpanel">
				{ refreshing && (
					<p className="edac-analysis__message">
						{ __( 'Updating accessibility data...', 'accessibility-checker' ) }
					</p>
				) }
				{ ! refreshing && hasItems && (
					<div className="edac-analysis__rules">
						{ items.map( ( rule ) => {
							const ruleId = rule.slug || rule.id || rule.title;
							return (
								<RuleAccordion
									key={ ruleId }
									rule={ rule }
									isExpanded={ expandedRules[ ruleId ] || false }
									onToggle={ () => toggleRule( ruleId ) }
								/>
							);
						} ) }
					</div>
				) }
				{ ! refreshing && ! hasItems && (
					<p className="edac-analysis__message">{ emptyMessage }</p>
				) }
			</div>
		);
	};

	return (
		<Panel className={ `edac-analysis-panel ${ className }` }>
			<PanelBody title={ title } initialOpen={ initialOpen }>
				<TabPanel
					className="edac-analysis__tabs"
					tabs={ tabsWithCounts }
					initialTabName={ tabsWithCounts[ 0 ]?.name }
					selectOnMove={ false }
				>
					{ renderTabContent }
				</TabPanel>
			</PanelBody>
		</Panel>
	);
};

export default IssuesPanel;
