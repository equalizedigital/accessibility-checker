/**
 * Accessibility Analysis Panel (Problems / Needs Review)
 */

import { __ } from '@wordpress/i18n';
import { Panel, PanelBody, PanelRow, Button, DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { useState, useMemo } from '@wordpress/element';
import { chevronUp, chevronDown, moreVertical, seen, code, check, tool } from '@wordpress/icons';
import { useAccessibilityCheckerData } from '../hooks/useAccessibilityCheckerData';
import '../sass/components/accessibility-analysis.scss';

const TAB_PROBLEMS = 'problems';
const TAB_WARNINGS = 'warnings';

/**
 * Single issue row with actions dropdown
 *
 * @param {Object}   props          - Component props.
 * @param {Object}   props.issue    - Issue object.
 * @param {Function} props.onAction - Action handler function.
 */
const IssueRow = ( { issue, onAction } ) => {
	return (
		<li className="edac-analysis__issue-row">
			<button
				type="button"
				className="edac-analysis__issue-link"
				onClick={ () => onAction( 'details', issue ) }
			>
				{ __( 'Issue', 'accessibility-checker' ) } #{ issue.id }
			</button>
			<DropdownMenu
				icon={ moreVertical }
				label={ __( 'Issue actions', 'accessibility-checker' ) }
				className="edac-analysis__issue-menu"
			>
				{ ( { onClose } ) => (
					<MenuGroup>
						<MenuItem
							icon={ seen }
							onClick={ () => {
								onAction( 'view', issue );
								onClose();
							} }
						>
							{ __( 'View on page', 'accessibility-checker' ) }
						</MenuItem>
						<MenuItem
							icon={ code }
							onClick={ () => {
								onAction( 'code', issue );
								onClose();
							} }
						>
							{ __( 'Show code', 'accessibility-checker' ) }
						</MenuItem>
						<MenuItem
							icon={ check }
							onClick={ () => {
								onAction( 'ignore', issue );
								onClose();
							} }
						>
							{ __( 'Not an Issue', 'accessibility-checker' ) }
						</MenuItem>
						<MenuItem
							icon={ tool }
							onClick={ () => {
								onAction( 'fix', issue );
								onClose();
							} }
						>
							{ __( 'Apply fix', 'accessibility-checker' ) }
						</MenuItem>
					</MenuGroup>
				) }
			</DropdownMenu>
		</li>
	);
};

/**
 * Rule accordion - custom expandable section for each rule type
 *
 * @param {Object}   props            - Component props.
 * @param {Object}   props.rule       - Rule object.
 * @param {boolean}  props.isExpanded - Whether accordion is expanded.
 * @param {Function} props.onToggle   - Toggle handler function.
 */
const RuleAccordion = ( { rule, isExpanded, onToggle } ) => {
	const [ showIgnored, setShowIgnored ] = useState( false );

	// Get issues from rule.details array
	const issues = rule.details || [];
	const activeIssues = issues.filter( ( issue ) => issue.ignre === '0' || issue.ignre === 0 );
	const ignoredIssues = issues.filter( ( issue ) => issue.ignre === '1' || issue.ignre === 1 );
	const ignoredCount = ignoredIssues.length;

	const handleIssueAction = ( action, issue ) => {
		// eslint-disable-next-line no-console
		console.log( `Action: ${ action }`, issue );
		// TODO: Implement actual actions
	};

	return (
		<div className="edac-analysis__rule">
			<Button
				className="edac-analysis__rule-toggle"
				onClick={ onToggle }
				aria-expanded={ isExpanded }
				icon={ isExpanded ? chevronUp : chevronDown }
				iconPosition="right"
			>
				<span className="edac-analysis__rule-title">
					{ rule.title } ({ rule.count || activeIssues.length })
				</span>
			</Button>

			<div
				className="edac-analysis__rule-content"
				aria-hidden={ ! isExpanded }
			>
				{ activeIssues.length > 0 && (
					<ul className="edac-analysis__issue-list">
						{ activeIssues.map( ( issue, index ) => (
							<IssueRow
								key={ issue.id || index }
								issue={ issue }
								onAction={ handleIssueAction }
							/>
						) ) }
					</ul>
				) }

				{ ignoredCount > 0 && (
					<button
						type="button"
						onClick={ () => setShowIgnored( ! showIgnored ) }
						className="edac-analysis__show-ignored"
					>
						{ __( 'Show issues marked "Not an Issue"', 'accessibility-checker' ) } ({ ignoredCount })
					</button>
				) }

				{ showIgnored && ignoredIssues.length > 0 && (
					<ul className="edac-analysis__issue-list edac-analysis__ignored-issues">
						{ ignoredIssues.map( ( issue, index ) => (
							<IssueRow
								key={ issue.id || `ignored-${ index }` }
								issue={ issue }
								onAction={ handleIssueAction }
							/>
						) ) }
					</ul>
				) }
			</div>
		</div>
	);
};

const AccessibilityAnalysis = () => {
	const { data, loading, error, refreshing } = useAccessibilityCheckerData();
	const [ activeTab, setActiveTab ] = useState( TAB_PROBLEMS );
	const [ expandedRules, setExpandedRules ] = useState( {} );

	const details = data?.details || {};
	const problems = useMemo( () => details.errors || [], [ details ] );
	const warnings = useMemo( () => details.warnings || [], [ details ] );

	// If we have no data (still loading) let parent loaders show.
	if ( loading || error ) {
		return null;
	}

	const currentItems = activeTab === TAB_PROBLEMS ? problems : warnings;
	const hasItems = currentItems.length > 0;

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
				<PanelRow className="edac-analysis__tabs" role="tablist" aria-label={ __( 'Accessibility issues', 'accessibility-checker' ) }>
					<Button
						variant="tertiary"
						isPressed={ activeTab === TAB_PROBLEMS }
						onClick={ () => setActiveTab( TAB_PROBLEMS ) }
						role="tab"
						aria-selected={ activeTab === TAB_PROBLEMS }
						className="edac-analysis__tab"
					>
						{ __( 'Problems', 'accessibility-checker' ) }
						<span className="edac-analysis__count">{ problems.length }</span>
					</Button>
					<Button
						variant="tertiary"
						isPressed={ activeTab === TAB_WARNINGS }
						onClick={ () => setActiveTab( TAB_WARNINGS ) }
						role="tab"
						aria-selected={ activeTab === TAB_WARNINGS }
						className="edac-analysis__tab"
					>
						{ __( 'Needs Review', 'accessibility-checker' ) }
						<span className="edac-analysis__count">{ warnings.length }</span>
					</Button>
				</PanelRow>

				<div className="edac-analysis__panel" role="tabpanel">
					{ refreshing && (
						<p className="edac-analysis__message">{ __( 'Updating accessibility data...', 'accessibility-checker' ) }</p>
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
										onToggle={ () => toggleRule( ruleId ) }
									/>
								);
							} ) }
						</div>
					) }
					{ ! refreshing && ! hasItems && (
						<p className="edac-analysis__message">
							{ activeTab === TAB_PROBLEMS
								? __( 'No problems found.', 'accessibility-checker' )
								: __( 'No items to review.', 'accessibility-checker' ) }
						</p>
					) }
				</div>
			</PanelBody>
		</Panel>
	);
};

export default AccessibilityAnalysis;

