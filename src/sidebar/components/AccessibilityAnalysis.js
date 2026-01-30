/**
 * Accessibility Analysis Panel (Problems / Needs Review)
 */

import { __ } from '@wordpress/i18n';
import { Panel, PanelBody, PanelRow, Button } from '@wordpress/components';
import { useState, useMemo } from '@wordpress/element';
import { chevronUp, chevronDown } from '@wordpress/icons';
import { useAccessibilityCheckerData } from '../hooks/useAccessibilityCheckerData';
import '../sass/components/accessibility-analysis.scss';

const TAB_PROBLEMS = 'problems';
const TAB_WARNINGS = 'warnings';

/**
 * Rule accordion - custom expandable section for each rule type
 *
 * @param {Object}   props            - Component props.
 * @param {Object}   props.rule       - Rule object.
 * @param {boolean}  props.isExpanded - Whether accordion is expanded.
 * @param {Function} props.onToggle   - Toggle handler function.
 */
const RuleAccordion = ( { rule, isExpanded, onToggle } ) => {
	// Get issues from rule.details array
	const issues = rule.details || [];
	const activeIssues = issues.filter( ( issue ) => issue.ignre === '0' || issue.ignre === 0 );
	const ignoredCount = issues.filter( ( issue ) => issue.ignre === '1' || issue.ignre === 1 ).length;

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
							<li key={ issue.id || index } className="edac-analysis__issue-item">
								{ issue.title || __( 'Issue', 'accessibility-checker' ) }
							</li>
						) ) }
					</ul>
				) }

				{ ignoredCount > 0 && (
					<p className="edac-analysis__ignored-notice">
						{ __( 'Ignored issues:', 'accessibility-checker' ) } { ignoredCount }
					</p>
				) }
			</div>
		</div>
	);
};

const AccessibilityAnalysis = () => {
	const { data, loading, error, refreshing } = useAccessibilityCheckerData();
	const [ activeTab, setActiveTab ] = useState( TAB_PROBLEMS );

	const details = data?.details || {};
	const problems = useMemo( () => details.errors || [], [ details ] );
	const warnings = useMemo( () => details.warnings || [], [ details ] );

	// If we have no data (still loading) let parent loaders show.
	if ( loading || error ) {
		return null;
	}

	const currentItems = activeTab === TAB_PROBLEMS ? problems : warnings;
	const hasItems = currentItems.length > 0;

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
						<ul className="edac-analysis__list">
							{ currentItems.map( ( issue ) => (
								<li key={ issue.id || issue.title } className="edac-analysis__item">
									<div className="edac-analysis__item-title">{ issue.title || __( 'Issue', 'accessibility-checker' ) }</div>
									{ issue.severity && (
										<span className={`edac-analysis__badge edac-analysis__badge--${ issue.severity.toLowerCase() }`}>
											{ issue.severity }
										</span>
									) }
									{ issue.link && (
										<a href={ issue.link } className="edac-analysis__link">
											{ __( 'View on page', 'accessibility-checker' ) }
										</a>
									) }
								</li>
							) ) }
						</ul>
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

