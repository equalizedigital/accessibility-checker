/**
 * Rule Accordion Component
 */

import { __ } from '@wordpress/i18n';
import { Button, DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { chevronUp, chevronDown, moreVertical, seen, code, check, tool } from '@wordpress/icons';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import IssueDetailsModal from './IssueDetailsModal';

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
 * Convert numeric severity to text label
 *
 * @param {number|string} severity - Severity value (1-4 or string).
 * @return {string} Severity label.
 */
const getSeverityLabel = ( severity ) => {
	// If already a string, return it
	if ( typeof severity === 'string' ) {
		return severity;
	}

	// Convert numeric severity to label
	const severityMap = {
		1: __( 'Critical', 'accessibility-checker' ),
		2: __( 'High', 'accessibility-checker' ),
		3: __( 'Medium', 'accessibility-checker' ),
		4: __( 'Low', 'accessibility-checker' ),
	};

	return severityMap[ severity ] || '';
};

/**
 * Severity badge component
 *
 * @param {Object}        props          - Component props.
 * @param {number|string} props.severity - Severity level.
 */
const SeverityBadge = ( { severity } ) => {
	const severityLabel = getSeverityLabel( severity );
	const severityKey = severityLabel.toLowerCase();

	return (
		<span className={ `edac-analysis__badge edac-analysis__badge--${ severityKey }` }>
			{ severityLabel }
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
	const [ focusSection, setFocusSection ] = useState( null );

	// Get the appropriate view link from the editor store
	// Use preview link for unpublished posts, permalink for published posts
	const viewLink = useSelect( ( select ) => {
		const { getEditedPostPreviewLink, getPermalink, isCurrentPostPublished } = select( editorStore );
		return isCurrentPostPublished() ? getPermalink() : getEditedPostPreviewLink();
	}, [] );

	// Get issues from rule.details array
	const issues = rule.details || [];
	const activeIssues = issues.filter( ( issue ) => issue.ignre === '0' || issue.ignre === 0 );
	const ignoredIssues = issues.filter( ( issue ) => issue.ignre === '1' || issue.ignre === 1 );
	const ignoredCount = ignoredIssues.length;

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

		// Handle the 'details' action that will open a modal
		if ( action === 'details' ) {
			setFocusSection( null );
			setSelectedIssue( issue );
			return;
		}

		// Handle the 'code' action to open modal with code section focused
		if ( action === 'code' ) {
			setFocusSection( 'code' );
			setSelectedIssue( issue );
			return;
		}

		// eslint-disable-next-line no-console
		console.log( `Action: ${ action }`, issue );
		// TODO: Implement remaining actions (ignore, fix)
	};

	const closeModal = () => {
		setSelectedIssue( null );
		setFocusSection( null );
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
								__html: activeIssues.length > 1 ? rule.summary_plural : rule.summary,
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
							{ activeIssues.map( ( issue, index ) => (
								<IssueRow
									key={ issue.id || index }
									issue={ issue }
									onAction={ handleIssueAction }
								/>
							) ) }
						</ul>
					</>
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
				focusSection={ focusSection }
			/>
		</div>
	);
};

export default RuleAccordion;
