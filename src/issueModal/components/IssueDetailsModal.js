/**
 * Issue Details Modal Component
 *
 * Standalone version for use outside the sidebar.
 */

import { __, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { Modal, Button } from '@wordpress/components';
import { useRef, useEffect, useState, useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import IssueImage, { extractImageUrls } from './IssueImage';
import FixPanel from './FixPanel';
import DismissPanel from './DismissPanel';
import { getSeverityLabel } from '../../sidebar/utils/severityHelpers';

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
 * CodeMirror HTML viewer component
 *
 * @param {Object} props       - Component props.
 * @param {string} props.value - HTML code to display.
 */
const CodeMirrorViewer = ( { value } ) => {
	const textareaRef = useRef( null );
	const editorRef = useRef( null );

	useEffect( () => {
		if ( ! textareaRef.current || ! window.wp?.codeEditor ) {
			return;
		}

		// Initialize CodeMirror
		const settings = window.wp.codeEditor.defaultSettings || {};
		const editorSettings = {
			...settings,
			codemirror: {
				...settings.codemirror,
				mode: 'htmlmixed',
				readOnly: true,
				lineNumbers: true,
				lineWrapping: true,
			},
		};

		editorRef.current = window.wp.codeEditor.initialize( textareaRef.current, editorSettings );

		// Cleanup on unmount
		return () => {
			if ( editorRef.current?.codemirror ) {
				editorRef.current.codemirror.toTextArea();
			}
		};
	}, [] );

	// Update content when value changes
	useEffect( () => {
		if ( editorRef.current?.codemirror ) {
			editorRef.current.codemirror.setValue( value || '' );
		}
	}, [ value ] );

	return (
		<textarea
			ref={ textareaRef }
			defaultValue={ value }
			className="edac-analysis__code-textarea"
		/>
	);
};

/**
 * Issue Details Modal
 *
 * @param {Object}      props              - Component props.
 * @param {Object}      props.issue        - Issue object to display (individual issue from details array).
 * @param {Object}      props.rule         - Rule object containing metadata (title, summary, wcag, etc.).
 * @param {Function}    props.onClose      - Close handler function.
 * @param {boolean}     props.isOpen       - Whether modal is open.
 * @param {string|null} props.focusSection - Section to focus on open (matches data-section attribute).
 * @param {Function}    props.onIgnore     - Callback when issue is ignored.
 */
export const IssueDetailsModal = ( { issue, rule, onClose, isOpen, focusSection, onIgnore } ) => {
	const modalRef = useRef( null );
	const initializedIssueId = useRef( null );
	const [ isDismissPanelOpen, setIsDismissPanelOpen ] = useState( false );
	const [ isFixPanelOpen, setIsFixPanelOpen ] = useState( false );

	// Initialize state when modal opens with a NEW issue
	useEffect( () => {
		if ( isOpen && issue && initializedIssueId.current !== issue.id ) {
			initializedIssueId.current = issue.id;
			// Open dismiss panel if focusSection is 'dismiss'
			const shouldOpenDismiss = focusSection === 'dismiss';
			const shouldOpenFix = focusSection === 'fix';
			setIsDismissPanelOpen( shouldOpenDismiss );
			setIsFixPanelOpen( shouldOpenFix );
		}
	}, [ isOpen, issue?.id, focusSection ] );

	// Handle focus section changes separately
	useEffect( () => {
		if ( isOpen && focusSection === 'dismiss' ) {
			setIsDismissPanelOpen( true );
		}
		if ( isOpen && focusSection === 'fix' ) {
			setIsFixPanelOpen( true );
		}
	}, [ isOpen, focusSection ] );

	// Focus the specified section when modal opens
	useEffect( () => {
		if ( ! isOpen || ! focusSection ) {
			return;
		}

		// Use double requestAnimationFrame to ensure the DOM is fully painted and ready
		let rafId;
		const focusElement = () => {
			rafId = requestAnimationFrame( () => {
				rafId = requestAnimationFrame( () => {
					const section = modalRef.current?.querySelector( `[data-section="${ focusSection }"]` );
					if ( section ) {
						section.scrollIntoView( { behavior: 'smooth', block: 'center' } );
						// Try to focus a focusable element within the section, or the section itself
						const focusable = section.querySelector( 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])' );
						if ( focusable ) {
							focusable.focus();
						}
					}
				} );
			} );
		};

		focusElement();

		return () => cancelAnimationFrame( rafId );
	}, [ isOpen, focusSection ] );

	// Extract image URLs from the issue markup (must be before early return for hooks rules)
	const imageUrls = useMemo( () => extractImageUrls( issue?.object ), [ issue?.object ] );

	// Get the count of issues for this rule to determine singular/plural summary
	const issueCount = rule?.details?.length || 1;
	const summary = issueCount > 1 ? rule?.summary_plural : rule?.summary;

	if ( ! isOpen || ! issue ) {
		return null;
	}

	// Get the appropriate view link from the editor store
	// Use preview link for unpublished posts, permalink for published posts
	const viewLink = useSelect( ( select ) => {
		const { getEditedPostPreviewLink, getPermalink, isCurrentPostPublished } = select( editorStore );
		return isCurrentPostPublished() ? getPermalink() : getEditedPostPreviewLink();
	}, [] );

	const viewUrl = issue ? getViewOnPageUrl( issue, viewLink ) : null;
	const severityLabel = getSeverityLabel( rule?.severity || issue?.severity );

	// Don't render if there's no issue data
	if ( ! issue || ! rule ) {
		return null;
	}

	return (
		<Modal
			// translators: %s is the issue ID number
			title={ sprintf( __( 'Issue #%s', 'accessibility-checker' ), issue.id ) }
			onRequestClose={ onClose }
			className="edac-analysis__issue-modal"
		>
			<div className="edac-analysis__issue-modal-content" ref={ modalRef }>

				<div className="edac-analysis__issue-modal-right">
					<div className="edac-analysis__issue-sidebar" data-section="sidebar">
						<h3 className="edac-analysis__issue-sidebar-title">
							{ __( 'Issue Details', 'accessibility-checker' ) }
						</h3>
						<ul className="edac-analysis__issue-sidebar-list">
							{ rule?.rule_type && (
								<li className="edac-analysis__issue-sidebar-item">
									<span className="edac-analysis__issue-sidebar-label">
										{ __( 'Type', 'accessibility-checker' ) }
									</span>
									<span className="edac-analysis__issue-sidebar-value">
										{ rule.rule_type }
									</span>
								</li>
							) }
							{ severityLabel && (
								<li className="edac-analysis__issue-sidebar-item">
									<span className="edac-analysis__issue-sidebar-label">
										{ __( 'Severity', 'accessibility-checker' ) }
									</span>
									<span className="edac-analysis__issue-sidebar-value">
										{ severityLabel }
									</span>
								</li>
							) }
							{ issue?.landmark && (
								<li className="edac-analysis__issue-sidebar-item">
									<span className="edac-analysis__issue-sidebar-label">
										{ __( 'Landmark', 'accessibility-checker' ) }
									</span>
									<span className="edac-analysis__issue-sidebar-value">
										{ issue.landmark }
									</span>
								</li>
							) }
						</ul>
						{ viewUrl && (
							<Button
								variant="primary"
								onClick={ () => window.open( viewUrl, '_blank', 'noopener,noreferrer' ) }
								className="edac-analysis__issue-sidebar-button"
							>
								{ __( 'View on Page', 'accessibility-checker' ) }
							</Button>
						) }
					</div>
				</div>

				<div className="edac-analysis__issue-modal-left">
					{ /* Rule Title */ }
					{ rule?.title && (
						<h2 className="edac-analysis__issue-title" data-section="title">
							{ rule.title }
						</h2>
					) }

					{ /* WCAG Reference */ }
					{ rule?.wcag && (
						<p className="edac-analysis__issue-wcag" data-section="wcag">
							<strong>{ __( 'WCAG:', 'accessibility-checker' ) }</strong>{ ' ' }
							{ rule.wcag_url ? (
								<a href={ rule.wcag_url } target="_blank" rel="noopener noreferrer">
									{ rule.wcag } { rule.wcag_title }
								</a>
							) : (
								<>{ rule.wcag } { rule.wcag_title }</>
							) }
						</p>
					) }

					{ /* Summary */ }
					{ summary && (
						<p
							className="edac-analysis__issue-summary"
							data-section="summary"
							dangerouslySetInnerHTML={ { __html: summary } }
						/>
					) }

					{ /* How to Fix - Show detailed info for Pro, or just link for Free */ }
					{ window.edac_editor_app?.pro === '1' ? (
						<>
							{ rule?.why_it_matters && (
								<div className="edac-analysis__issue-why-it-matters" data-section="why-it-matters">
									<h3>{ __( 'Why It Matters', 'accessibility-checker' ) }</h3>
									<p dangerouslySetInnerHTML={ { __html: rule.why_it_matters } } />
								</div>
							) }
							{ rule?.how_to_fix && (
								<div className="edac-analysis__issue-how-to-fix" data-section="how-to-fix">
									<h3>{ __( 'How to Fix', 'accessibility-checker' ) }</h3>
									<p dangerouslySetInnerHTML={ { __html: rule.how_to_fix } } />
								</div>
							) }
							{ rule?.info_url && (
								<p className="edac-analysis__issue-help" data-section="help">
									<a href={ rule.info_url } target="_blank" rel="noopener noreferrer">
										{ __( 'More Detailed Documentation', 'accessibility-checker' ) }
									</a>
								</p>
							) }
						</>
					) : (
						rule?.info_url && (
							<p className="edac-analysis__issue-help" data-section="help">
								<a href={ rule.info_url } target="_blank" rel="noopener noreferrer">
									{ __( 'How to Fix', 'accessibility-checker' ) }
								</a>
							</p>
						)
					) }

					{ /* Affected Code */ }
					{ issue.object && (
						<div className="edac-analysis__code-wrapper" data-section="code">
							<h3>{ __( 'Affected Code', 'accessibility-checker' ) }</h3>
							<CodeMirrorViewer value={ decodeEntities( issue.object ) } />
						</div>
					) }

					{ /* Image Preview - only show if markup contains images */ }
					{ imageUrls.length > 0 && (
						<div className="edac-analysis__image-wrapper" data-section="image">
							<h3>{ __( 'Image', 'accessibility-checker' ) }</h3>
							<IssueImage markup={ issue.object } />
						</div>
					) }

					{ /* Fix Issue Panel - Only show if fixes are available and user has permission */ }
					{ isOpen && rule?.fixes?.length > 0 && window.edac_sidebar_app?.canManageSettings && (
						<FixPanel
							rule={ rule }
							issue={ issue }
							isOpen={ isFixPanelOpen }
							onToggle={ () => setIsFixPanelOpen( ! isFixPanelOpen ) }
						/>
					) }

					{ /* Dismiss Issue Panel */ }
					<DismissPanel
						issue={ issue }
						isOpen={ isDismissPanelOpen }
						onToggle={ () => setIsDismissPanelOpen( ! isDismissPanelOpen ) }
						onIgnore={ onIgnore }
					/>
				</div>
			</div>
		</Modal>
	);
};

export default IssueDetailsModal;
