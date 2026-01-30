/**
 * Accessibility Analysis Panel (Problems / Needs Review)
 */

import { __ } from '@wordpress/i18n';
import { Panel, PanelBody, TabPanel, Button, DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { useState, useMemo } from '@wordpress/element';
import { chevronUp, chevronDown, moreVertical, seen, code, check, tool } from '@wordpress/icons';
import { useAccessibilityCheckerData } from '../hooks/useAccessibilityCheckerData';
import IssueDetailsModal from './IssueDetailsModal';
import '../sass/components/accessibility-analysis.scss';
import '../sass/components/issue-details-modal.scss';

const TAB_PROBLEMS = 'problems';
const TAB_WARNINGS = 'warnings';

/**
 * Severity badge component
 *
 * @param {Object} props          - Component props.
 * @param {string} props.severity - Severity level.
 */
const SeverityBadge = ( { severity } ) => {
	const severityKey = typeof severity === 'string' ? severity.toLowerCase() : '';
	return (
		<span className={ `edac-analysis__badge edac-analysis__badge--${ severityKey }` }>
			{ severity }
		</span>
	);
};

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
	const [ selectedIssue, setSelectedIssue ] = useState( null );

	// Get issues from rule.details array
	const issues = rule.details || [];
	const activeIssues = issues.filter( ( issue ) => issue.ignre === '0' || issue.ignre === 0 );
	const ignoredIssues = issues.filter( ( issue ) => issue.ignre === '1' || issue.ignre === 1 );
	const ignoredCount = ignoredIssues.length;

	// Determine severity label
	const severityRaw = rule?.severity;
	const severity = typeof severityRaw === 'string'
		? severityRaw
		: ( severityRaw?.label || severityRaw?.value || '' );

	const handleIssueAction = ( action, issue ) => {
		// eslint-disable-next-line no-console
		console.log( `Action: ${ action }`, issue );
		// TODO: Implement actual actions

		// handle the 'details' action that will open a modal and pass in the issue details
		if ( action === 'details' ) {
			setSelectedIssue( issue );
		}
	};

	const closeModal = () => {
		setSelectedIssue( null );
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
				{ severity && <SeverityBadge severity={ severity } /> }
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

			<IssueDetailsModal
				issue={ selectedIssue }
				onClose={ closeModal }
				isOpen={ !! selectedIssue }
			/>
		</div>
	);
};

const AccessibilityAnalysis = () => {
	const { data, loading, error, refreshing } = useAccessibilityCheckerData();
	const [ expandedRules, setExpandedRules ] = useState( {} );

	const details = data?.details || {};
	const problems = useMemo( () => details.errors || [], [ details ] );
	const warnings = useMemo( () => details.warnings || [], [ details ] );

	// Calculate totals
	const totalProblems = problems.reduce( ( sum, rule ) => sum + ( rule.count || rule.details?.length || 0 ), 0 );
	const totalWarnings = warnings.reduce( ( sum, rule ) => sum + ( rule.count || rule.details?.length || 0 ), 0 );

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
						{ tab.name === TAB_PROBLEMS
							? __( 'No problems found.', 'accessibility-checker' )
							: __( 'No items to review.', 'accessibility-checker' ) }
					</p>
				) }
			</div>
		);
	};

	return (
		<Panel className="edac-analysis-panel">
			<PanelBody
				title={ __( 'Accessibility Analysis', 'accessibility-checker' ) }
				initialOpen={ false }
			>
				<TabPanel
					className="edac-analysis__tabs"
					tabs={ tabs }
					initialTabName={ TAB_PROBLEMS }
					selectOnMove={ false }
				>
					{ renderTabContent }
				</TabPanel>
			</PanelBody>
		</Panel>
	);
};

export default AccessibilityAnalysis;

