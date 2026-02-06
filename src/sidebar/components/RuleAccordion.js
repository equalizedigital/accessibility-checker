/**
 * Rule Accordion Component
 */

import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { chevronUp, chevronDown } from '@wordpress/icons';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { getSeverityBadgeProps } from '../utils/badgeHelpers';
import Badge from './Badge';
import IssueRow from './IssueRow';

/**
 * Get the "View on page" URL for an issue
 *
 * @param {Object} issue    - The issue object containing id.
 * @param {string} viewLink - The permalink or preview link.
 * @return {string|null} The URL to view the issue on the frontend.
 */
const getViewOnPageUrl = ( issue, viewLink ) => {
	const { highlightNonce } = window.edac_sidebar_app || {};

	if ( ! viewLink ) {
		return null;
	}

	const url = new URL( viewLink );
	url.searchParams.set( 'edac', issue.id );
	if ( highlightNonce ) {
		url.searchParams.set( 'edac_nonce', highlightNonce );
	}

	return url.toString();
};

/**
 * Severity badge component
 *
 * @param {Object}        props          - Component props.
 * @param {number|string} props.severity - Severity level.
 */
const SeverityBadge = ( { severity } ) => {
	const badgeProps = getSeverityBadgeProps( severity );

	if ( ! badgeProps ) {
		return null;
	}

	return (
		<Badge
			label={ badgeProps.label }
			type={ badgeProps.type }
		/>
	);
};

/**
 * Rule accordion - custom expandable section for each rule type
 *
 * @param {Object}   props             - Component props.
 * @param {Object}   props.rule        - Rule object.
 * @param {boolean}  props.isExpanded  - Whether accordion is expanded.
 * @param {boolean}  props.showIgnored - If true, show only ignored issues. If false, show only active issues.
 * @param {Function} props.onToggle    - Toggle handler function.
 */
const RuleAccordion = ( { rule, isExpanded, onToggle, showIgnored = false } ) => {
	// Get the appropriate view link from the editor store
	// Use preview link for unpublished posts, permalink for published posts
	const viewLink = useSelect( ( select ) => {
		const { getEditedPostPreviewLink, getPermalink, isCurrentPostPublished } = select( editorStore );
		return isCurrentPostPublished() ? getPermalink() : getEditedPostPreviewLink();
	}, [] );

	// Get issues from rule.details array and filter based on showIgnored flag
	const issues = rule.details || [];
	const displayedIssues = showIgnored
		? issues.filter( ( issue ) => issue.ignre === '1' || issue.ignre === 1 )
		: issues.filter( ( issue ) => issue.ignre === '0' || issue.ignre === 0 );

	// Get severity from rule
	const severity = rule?.severity;

	const handleIssueAction = ( action, issue ) => {
		// Handle the 'view' action to open the issue on the frontend
		if ( action === 'view' ) {
			const url = getViewOnPageUrl( issue, viewLink );
			if ( url ) {
				window.open( url, '_blank', 'noopener,noreferrer' );
			}
			return;
		}

		const openIssueModal = ( focusSection = null ) => {
			if ( window.edacIssueModal?.open ) {
				window.edacIssueModal.open( {
					issue,
					rule,
					focusSection,
				} );
			}
		};

		const focusableSections = [ 'code', 'ignore', 'fix' ];

		if ( action && focusableSections.includes( action ) ) {
			openIssueModal( action );
			return;
		}

		openIssueModal( null );
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
					{ rule.title } ({ rule.count || displayedIssues.length })
				</span>
				{ severity && <SeverityBadge severity={ severity } /> }
			</Button>

			<div
				className="edac-analysis__rule-content"
				aria-hidden={ ! isExpanded }
			>
				{ displayedIssues.length > 0 && (
					<>
						<p>
							<strong>
								{ __( 'WCAG:', 'accessibility-checker' ) }{' '}
								{ rule?.wcag_url && rule?.wcag && rule?.wcag_title ? (
									<a href={ rule.wcag_url } target="_blank" rel="noopener noreferrer">
										{ rule.wcag } { rule.wcag_title }
									</a>
								) : (
									rule?.wcag
								) }
							</strong>
						</p>
						<p
							dangerouslySetInnerHTML={ {
								__html: displayedIssues.length > 1 ? rule.summary_plural : rule.summary,
							} }
						/>
						{ rule?.info_url && (
							<p>
								<a href={ rule.info_url } target="_blank" rel="noopener noreferrer">
									{ __( 'How to Fix', 'accessibility-checker' ) }
								</a>
							</p>
						) }
						<ul className="edac-analysis__issue-list">
							{ displayedIssues.map( ( issue, index ) => (
								<IssueRow
									key={ issue.id || index }
									issue={ issue }
									rule={ rule }
									onAction={ handleIssueAction }
									showIgnored={ showIgnored }
								/>
							) ) }
						</ul>
					</>
				) }
			</div>
		</div>
	);
};

export default RuleAccordion;
