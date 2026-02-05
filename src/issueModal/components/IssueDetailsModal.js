/**
 * Issue Details Modal Component
 *
 * Standalone version for use outside the sidebar.
 */

import { __, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { Modal, Button, Panel, PanelBody, Spinner, Notice, RadioControl } from '@wordpress/components';
import { useRef, useEffect, useState, useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import IssueImage, { extractImageUrls } from './IssueImage';
import RichTextarea from './RichTextarea';
import FixPanel from './FixPanel';
import { toggleIssueDismiss } from '../api';
import { getSeverityLabel } from '../../sidebar/utils/severityHelpers';
import { setPendingRescan, setPendingRefetch } from '../index';

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
	const [ comment, setComment ] = useState( '' );
	const [ dismissReason, setDismissReason ] = useState( 'false_positive' );
	const [ isSubmitting, setIsSubmitting ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ successNotice, setSuccessNotice ] = useState( null );
	const [ isDismissPanelOpen, setIsDismissPanelOpen ] = useState( false );
	const [ isIgnored, setIsIgnored ] = useState( false );

	// Initialize state when modal opens with a NEW issue
	useEffect( () => {
		if ( isOpen && issue && initializedIssueId.current !== issue.id ) {
			initializedIssueId.current = issue.id;
			setComment( issue.ignre_comment ? decodeEntities( issue.ignre_comment ) : '' );
			setDismissReason( 'false_positive' );
			setError( null );
			setSuccessNotice( null );
			setIsSubmitting( false );
			// Open dismiss panel if focusSection is 'dismiss'
			setIsDismissPanelOpen( focusSection === 'dismiss' );
			// Set initial ignored state from issue data
			setIsIgnored( issue.ignre === '1' || issue.ignre === 1 );
		}
	}, [ isOpen, issue?.id ] ); // ONLY depend on isOpen and issue.id, NOT focusSection

	// Handle focus section changes separately
	useEffect( () => {
		if ( isOpen && focusSection === 'dismiss' ) {
			setIsDismissPanelOpen( true );
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

	const handleToggleIgnore = async ( ignore ) => {
		setIsSubmitting( true );
		setError( null );
		setSuccessNotice( null );
		try {
			await toggleIssueDismiss( issue.id, ignore, ignore ? dismissReason : '', ignore ? comment : '' );
			setIsIgnored( ignore );
			setSuccessNotice( ignore
				? __( 'Issue dismissed successfully.', 'accessibility-checker' )
				: __( 'Issue restored successfully.', 'accessibility-checker' ),
			);
			setPendingRefetch( true );
			if ( onIgnore ) {
				onIgnore( issue, ignore );
			}
		} catch ( err ) {
			setError( err.message );
		} finally {
			setIsSubmitting( false );
		}
	};

	// Get the appropriate view link from the editor store
	// Use preview link for unpublished posts, permalink for published posts
	const viewLink = useSelect( ( select ) => {
		const { getEditedPostPreviewLink, getPermalink, isCurrentPostPublished } = select( editorStore );
		return isCurrentPostPublished() ? getPermalink() : getEditedPostPreviewLink();
	}, [] );

	const viewUrl = issue ? getViewOnPageUrl( issue, viewLink ) : null;
	const severityLabel = getSeverityLabel( rule?.severity || issue?.severity );

	const handleFixSettingsUpdated = () => {
		setPendingRescan( true );
	};

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

					{ /* How to Fix Link */ }
					{ rule?.info_url && (
						<p className="edac-analysis__issue-help" data-section="help">
							<a href={ rule.info_url } target="_blank" rel="noopener noreferrer">
								{ __( 'How to Fix', 'accessibility-checker' ) }
							</a>
						</p>
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
							onFixSettingsUpdated={ handleFixSettingsUpdated }
						/>
					) }

					{ /* Dismiss Issue Panel */ }
					<Panel className="edac-analysis__dismiss-panel" data-section="dismiss">
						<PanelBody
							title={ isIgnored ? __( 'Issue Dismissed', 'accessibility-checker' ) : __( 'Dismiss Issue', 'accessibility-checker' ) }
							opened={ isDismissPanelOpen }
							onToggle={ () => setIsDismissPanelOpen( ! isDismissPanelOpen ) }
						>
							{ successNotice && (
								<Notice
									status="success"
									isDismissible={ true }
									onRemove={ () => setSuccessNotice( null ) }
								>
									{ successNotice }
								</Notice>
							) }
							{ error && (
								<Notice
									status="error"
									isDismissible={ true }
									onRemove={ () => setError( null ) }
								>
									{ error }
								</Notice>
							) }
							{ isIgnored ? (
								<>
									<p className="edac-analysis__dismissed-info">
										{ __( 'This issue has been dismissed and will not appear in active issues.', 'accessibility-checker' ) }
									</p>
									{ issue?.ignre_comment && (
										<div className="edac-analysis__dismissed-comment">
											<strong>{ __( 'Ignore Comment:', 'accessibility-checker' ) }</strong>
											<p
												dangerouslySetInnerHTML={ { __html: decodeEntities( issue.ignre_comment ) } }
											/>
										</div>
									) }
									<Button
										variant="secondary"
										onClick={ () => handleToggleIgnore( false ) }
										disabled={ isSubmitting }
										className="edac-analysis__dismiss-button"
									>
										{ isSubmitting ? (
											<>
												<Spinner />
												{ __( 'Restoring...', 'accessibility-checker' ) }
											</>
										) : (
											__( 'Restore Issue', 'accessibility-checker' )
										) }
									</Button>
								</>
							) : (
								<>
									<RadioControl
										label={ __( 'Dismiss issue as:', 'accessibility-checker' ) }
										selected={ dismissReason }
										options={ [
											{
												label: __( 'False positive', 'accessibility-checker' ),
												value: 'false_positive',
												description: __( 'The scanner flagged this, but it does not apply to this content.', 'accessibility-checker' ),
											},
											{
												label: __( 'Remediated', 'accessibility-checker' ),
												value: 'remediated',
												description: __( 'The issue has been fixed, but the page has not been rescanned yet.', 'accessibility-checker' ),
											},
											{
												label: __( 'Intentional', 'accessibility-checker' ),
												value: 'intentional',
												description: __( 'Reviewed and verified to meet accessibility requirements.', 'accessibility-checker' ),
											},
										] }
										onChange={ setDismissReason }
									/>
									<RichTextarea
										label={ __( 'Comment (optional)', 'accessibility-checker' ) }
										help={ __( 'Add a note explaining why this issue is being dismissed. Supports bold, italic, and links.', 'accessibility-checker' ) }
										value={ comment }
										onChange={ setComment }
										rows={ 3 }
										disabled={ isSubmitting }
									/>
									<Button
										variant="secondary"
										onClick={ () => handleToggleIgnore( true ) }
										disabled={ isSubmitting }
										className="edac-analysis__dismiss-button"
									>
										{ isSubmitting ? (
											<>
												<Spinner />
												{ __( 'Dismissing...', 'accessibility-checker' ) }
											</>
										) : (
											__( 'Dismiss Issue', 'accessibility-checker' )
										) }
									</Button>
								</>
							) }
						</PanelBody>
					</Panel>
				</div>

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
								variant="secondary"
								onClick={ () => window.open( viewUrl, '_blank', 'noopener,noreferrer' ) }
								className="edac-analysis__issue-sidebar-button"
							>
								{ __( 'View on Page', 'accessibility-checker' ) }
							</Button>
						) }
					</div>
				</div>
			</div>
			<div className="edac-analysis__issue-modal-footer">
				<Button variant="secondary" onClick={ onClose }>
					{ __( 'Close', 'accessibility-checker' ) }
				</Button>
			</div>
		</Modal>
	);
};

export default IssueDetailsModal;
