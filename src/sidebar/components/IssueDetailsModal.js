/**
 * Issue Details Modal Component
 */

import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { Modal, Button } from '@wordpress/components';
import { useRef, useEffect } from '@wordpress/element';

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
 * @param {Object}      props.issue        - Issue object to display.
 * @param {Function}    props.onClose      - Close handler function.
 * @param {boolean}     props.isOpen       - Whether modal is open.
 * @param {string|null} props.focusSection - Section to focus on open (matches data-section attribute).
 */
export const IssueDetailsModal = ( { issue, onClose, isOpen, focusSection } ) => {
	const modalRef = useRef( null );

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
