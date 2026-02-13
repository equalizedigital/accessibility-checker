/**
 * Generic Issues Panel Component
 *
 * Reusable panel for displaying accessibility issues with tabs.
 * Can be configured for different contexts (e.g., active issues, dismissed issues).
 */

import { __, sprintf } from '@wordpress/i18n';
import { Panel, PanelBody, TabPanel } from '@wordpress/components';
import { useState, useMemo, useEffect, useRef } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { STORE_NAME } from '../store/accessibility-checker-store';
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
 * @param {boolean} props.showIgnored - If true, show only ignored issues. If false, show only non-ignored.
 * @param {string}  props.className   - Additional CSS class.
 * @param {string}  props.panelId     - Unique identifier for this panel (for state persistence).
 */
const IssuesPanel = ( {
	title,
	initialOpen = false,
	tabs = [],
	showIgnored = null,
	className = '',
	panelId = 'default',
} ) => {
	const [ expandedRules, setExpandedRules ] = useState( {} );

	// Get panel expanded state, active tab, and focus tracking from store
	const { isPanelExpanded, activeTabName, backgroundRefresh, lastFocusedIssue } = useSelect( ( select ) => ( {
		isPanelExpanded: select( STORE_NAME ).isExpandedPanel( panelId ),
		activeTabName: select( STORE_NAME ).getActiveTab( panelId ),
		backgroundRefresh: select( STORE_NAME ).isBackgroundRefresh(),
		lastFocusedIssue: select( STORE_NAME ).getLastFocusedIssue(),
	} ), [ panelId ] );

	const { setExpandedPanel, setActiveTab } = useDispatch( STORE_NAME );

	// Track previous background refresh state
	const prevBackgroundRefresh = useRef( backgroundRefresh );

	// Use store state if available, otherwise use initialOpen
	const isOpen = isPanelExpanded !== false ? isPanelExpanded : initialOpen;

	// Handle panel toggle
	const handlePanelToggle = () => {
		setExpandedPanel( panelId, ! isOpen );
	};

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
		return tabs
			.map( ( tab ) => {
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
							<span className="edac-analysis__count" aria-hidden="true">
								({ count })
							</span>
							<span className="screen-reader-text">
								, { sprintf( __( '%d total', 'accessibility-checker' ), count ) }
							</span>
						</>
					),
					className: 'edac-analysis__tab',
				};
			} )
			.filter( ( tab ) => tab.rules.length > 0 );
	}, [ tabs, showIgnored ] );

	// Handle focus restoration when background refresh completes
	// This is especially important when a focused rule no longer exists (e.g., dismissed)
	useEffect( () => {
		// If background refresh just completed and there was a focused issue
		if ( prevBackgroundRefresh.current && ! backgroundRefresh && lastFocusedIssue ) {
			let focusedRuleExistsInThisPanel = false;

			// Check if the focused rule currently exists in this panel
			for ( const tab of tabsWithCounts ) {
				const ruleExists = tab.rules.some( ( rule ) => {
					const ruleId = `${ rule.slug }_${ showIgnored ? 'ignored' : 'active' }`;
					return ruleId === lastFocusedIssue;
				} );

				if ( ruleExists ) {
					focusedRuleExistsInThisPanel = true;
					break;
				}
			}

			// Check if the lastFocusedIssue suffix matches this panel's type
			const expectedSuffix = showIgnored ? '_ignored' : '_active';
			const focusedRuleWasInThisPanel = lastFocusedIssue.endsWith( expectedSuffix );

			if ( ! focusedRuleExistsInThisPanel && focusedRuleWasInThisPanel ) {
				// Determine which tab the user was in - use active tab state
				const currentActiveTab = activeTabName || tabsWithCounts[ 0 ]?.name;

				// Use requestAnimationFrame to ensure DOM has updated
				requestAnimationFrame( () => {
					// Focus the tab button to keep user in same tab context
					const tabButton = document.querySelector(
						`.${ className.split( ' ' )[ 0 ] } .edac-analysis__tabs button[id$="-${ currentActiveTab }"]`,
					);
					if ( tabButton ) {
						tabButton.focus();
						return;
					}

					// As a last resort, focus the panel header
					const panelHeader = document.querySelector(
						`.${ className.split( ' ' )[ 0 ] } .components-panel__body-toggle`,
					);
					if ( panelHeader ) {
						panelHeader.focus();
					}
				} );
			}
			// If rule still exists in this panel, RuleAccordion will handle its own focus restoration
		}
		prevBackgroundRefresh.current = backgroundRefresh;
	}, [ backgroundRefresh, lastFocusedIssue, tabsWithCounts, showIgnored, className, panelId, activeTabName ] );

	if ( tabsWithCounts.length === 0 ) {
		return null;
	}

	const renderTabContent = ( tab ) => {
		const currentTab = tabsWithCounts.find( ( t ) => t.name === tab.name );
		const rules = currentTab?.rules || [];
		const hasRules = rules.length > 0;

		return (
			<div className="edac-analysis__panel" role="tabpanel">
				{ hasRules && (
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
			</div>
		);
	};

	return (
		<Panel className={ `edac-analysis-panel ${ className }` }>
			<PanelBody
				title={ title }
				initialOpen={ initialOpen }
				opened={ isOpen }
				onToggle={ handlePanelToggle }
			>
				<TabPanel
					className="edac-analysis__tabs"
					tabs={ tabsWithCounts }
					initialTabName={ activeTabName || tabsWithCounts[ 0 ]?.name }
					onSelect={ ( tabName ) => setActiveTab( panelId, tabName ) }
					selectOnMove={ false }
				>
					{ renderTabContent }
				</TabPanel>
			</PanelBody>
		</Panel>
	);
};

export default IssuesPanel;
