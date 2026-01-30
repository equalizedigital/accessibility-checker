/**
 * Issue Details Modal Component
 */

import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { Modal, Button, Panel, PanelBody, TextareaControl, Spinner, Notice } from '@wordpress/components';
import { useRef, useEffect, useState } from '@wordpress/element';

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
 * Dismiss an issue via AJAX
 *
 * @param {string} issueId - The issue ID to dismiss.
 * @param {string} comment - Optional comment for the dismissal.
 * @return {Promise} Promise that resolves with the response data.
 */
const dismissIssue = async ( issueId, comment = '' ) => {
	const { ajaxUrl, ajaxNonce } = window.edac_sidebar_app || {};

	if ( ! ajaxUrl || ! ajaxNonce ) {
		throw new Error( __( 'Missing configuration', 'accessibility-checker' ) );
	}

	const formData = new FormData();
	formData.append( 'action', 'edac_insert_ignore_data' );
	formData.append( 'nonce', ajaxNonce );
	formData.append( 'ids[]', issueId );
	formData.append( 'ignore_action', 'enable' );
	formData.append( 'ignore_type', 'Issue' );
	formData.append( 'comment', comment );

	const response = await fetch( ajaxUrl, {
		method: 'POST',
		body: formData,
	} );

	const data = await response.json();

	if ( ! data.success ) {
		throw new Error( data.data?.message || __( 'Failed to dismiss issue', 'accessibility-checker' ) );
	}

	return JSON.parse( data.data );
};

/**
 * Un-dismiss (restore) an issue via AJAX
 *
 * @param {string} issueId - The issue ID to restore.
 * @return {Promise} Promise that resolves with the response data.
 */
const undismissIssue = async ( issueId ) => {
	const { ajaxUrl, ajaxNonce } = window.edac_sidebar_app || {};

	if ( ! ajaxUrl || ! ajaxNonce ) {
		throw new Error( __( 'Missing configuration', 'accessibility-checker' ) );
	}

	const formData = new FormData();
	formData.append( 'action', 'edac_insert_ignore_data' );
	formData.append( 'nonce', ajaxNonce );
	formData.append( 'ids[]', issueId );
	formData.append( 'ignore_action', 'disable' );
	formData.append( 'ignore_type', 'Issue' );

	const response = await fetch( ajaxUrl, {
		method: 'POST',
		body: formData,
	} );

	const data = await response.json();

	if ( ! data.success ) {
		throw new Error( data.data?.message || __( 'Failed to restore issue', 'accessibility-checker' ) );
	}

	return JSON.parse( data.data );
};

/**
 * Issue Details Modal
 *
 * @param {Object}      props              - Component props.
 * @param {Object}      props.issue        - Issue object to display.
 * @param {Function}    props.onClose      - Close handler function.
 * @param {boolean}     props.isOpen       - Whether modal is open.
 * @param {string|null} props.focusSection - Section to focus on open (matches data-section attribute).
 * @param {Function}    props.onIgnore     - Callback when issue is ignored.
 */
export const IssueDetailsModal = ( { issue, onClose, isOpen, focusSection, onIgnore } ) => {
	const modalRef = useRef( null );
	const initializedIssueId = useRef( null );
	const pendingRefetch = useRef( false );
	const [ comment, setComment ] = useState( '' );
	const [ isSubmitting, setIsSubmitting ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ successNotice, setSuccessNotice ] = useState( null );
	const [ isDismissPanelOpen, setIsDismissPanelOpen ] = useState( false );
	const [ isIgnored, setIsIgnored ] = useState( false );

	// Reset state only when modal opens with a NEW issue (different ID)
	// Dispatch pending refetch event when modal closes
	useEffect( () => {
		if ( isOpen && issue && initializedIssueId.current !== issue.id ) {
			initializedIssueId.current = issue.id;
			setComment( '' );
			setError( null );
			setSuccessNotice( null );
			setIsSubmitting( false );
			// Open dismiss panel if focusSection is 'dismiss'
			setIsDismissPanelOpen( focusSection === 'dismiss' );
			// Set initial ignored state from issue data
			setIsIgnored( issue.ignre === '1' || issue.ignre === 1 );
		}
		// When modal closes, dispatch pending refetch event if needed
		if ( ! isOpen ) {
			if ( pendingRefetch.current ) {
				// Dispatch event to notify other components (sidebar status, old metabox)
				const event = new CustomEvent( 'edac-ignore-updated', {
					detail: {
						pending: true,
					},
				} );
				window.dispatchEvent( event );
				pendingRefetch.current = false;
			}
			initializedIssueId.current = null;
		}
	}, [ isOpen, issue?.id, focusSection, issue?.ignre ] );

	// Focus the specified section when modal opens
	useEffect( () => {
		if ( ! isOpen || ! focusSection ) {
			return;
		}

		// Use setTimeout to ensure the modal is fully rendered
		const timeoutId = setTimeout( () => {
			const section = modalRef.current?.querySelector( `[data-section="${ focusSection }"]` );
			if ( section ) {
				section.scrollIntoView( { behavior: 'smooth', block: 'center' } );
				// Try to focus a focusable element within the section, or the section itself
				const focusable = section.querySelector( 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])' );
				if ( focusable ) {
					focusable.focus();
				}
			}
		}, 100 );

		return () => clearTimeout( timeoutId );
	}, [ isOpen, focusSection ] );

	if ( ! isOpen || ! issue ) {
		return null;
	}

	const handleDismiss = async () => {
		setIsSubmitting( true );
		setError( null );
		setSuccessNotice( null );

		try {
			await dismissIssue( issue.id, comment );
			setIsIgnored( true );
			setSuccessNotice( __( 'Issue dismissed successfully.', 'accessibility-checker' ) );

			// Queue refetch for when modal closes (to avoid re-render closing the modal)
			pendingRefetch.current = true;

			if ( onIgnore ) {
				onIgnore( issue, true );
			}
		} catch ( err ) {
			setError( err.message );
		} finally {
			setIsSubmitting( false );
		}
	};

	const handleUndismiss = async () => {
		setIsSubmitting( true );
		setError( null );
		setSuccessNotice( null );

		try {
			await undismissIssue( issue.id );
			setIsIgnored( false );
			setSuccessNotice( __( 'Issue restored successfully.', 'accessibility-checker' ) );

			// Queue refetch for when modal closes (to avoid re-render closing the modal)
			pendingRefetch.current = true;

			if ( onIgnore ) {
				onIgnore( issue, false );
			}
		} catch ( err ) {
			setError( err.message );
		} finally {
			setIsSubmitting( false );
		}
	};

	return (
		<Modal
			title={ __( 'Issue Details', 'accessibility-checker' ) }
			onRequestClose={ onClose }
			className="edac-analysis__issue-modal"
		>
			<div className="edac-analysis__issue-modal-content" ref={ modalRef }>
				<div data-section="issue-id">
					<p>
						<strong>{ __( 'Issue ID:', 'accessibility-checker' ) }</strong> { issue.id }
					</p>
				</div>
				{ issue.description && (
					<div data-section="description">
						<p>
							<strong>{ __( 'Description:', 'accessibility-checker' ) }</strong> { issue.description }
						</p>
					</div>
				) }
				{ issue.object && (
					<div className="edac-analysis__code-wrapper" data-section="code">
						<strong>{ __( 'Element:', 'accessibility-checker' ) }</strong>
						<CodeMirrorViewer value={ decodeEntities( issue.object ) } />
					</div>
				) }

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
								<Button
									variant="secondary"
									onClick={ handleUndismiss }
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
								<TextareaControl
									label={ __( 'Comment (optional)', 'accessibility-checker' ) }
									help={ __( 'Add a note explaining why this issue is being dismissed.', 'accessibility-checker' ) }
									value={ comment }
									onChange={ setComment }
									rows={ 3 }
									disabled={ isSubmitting }
								/>
								<Button
									variant="secondary"
									onClick={ handleDismiss }
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
			<div className="edac-analysis__issue-modal-footer">
				<Button variant="secondary" onClick={ onClose }>
					{ __( 'Close', 'accessibility-checker' ) }
				</Button>
			</div>
		</Modal>
	);
};

export default IssueDetailsModal;
