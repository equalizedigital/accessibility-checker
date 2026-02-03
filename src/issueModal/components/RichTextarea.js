/**
 * Rich Text Textarea Component
 *
 * Supports basic HTML formatting: bold, italic, and links.
 */

import { __ } from '@wordpress/i18n';
import { Button, Popover } from '@wordpress/components';
import { useRef, useState, useEffect } from '@wordpress/element';
import { formatBold, formatItalic, link } from '@wordpress/icons';
import './rich-textarea.scss';

/**
 * Rich Text Textarea Component
 *
 * Supports basic HTML formatting: bold, italic, and links.
 *
 * @param {Object}   props          - Component props.
 * @param {string}   props.value    - Current text value (HTML string).
 * @param {Function} props.onChange - Change handler.
 * @param {string}   props.label    - Field label.
 * @param {string}   props.help     - Help text.
 * @param {number}   props.rows     - Number of rows.
 * @param {boolean}  props.disabled - Whether field is disabled.
 */
export const RichTextarea = ( { value, onChange, label, help, rows = 3, disabled = false } ) => {
	const editorRef = useRef( null );
	const linkButtonRef = useRef( null );
	const isInitializedRef = useRef( false );
	const [ linkUrl, setLinkUrl ] = useState( '' );
	const [ showLinkPopover, setShowLinkPopover ] = useState( false );

	// Initialize content only once
	useEffect( () => {
		if ( editorRef.current && ! isInitializedRef.current ) {
			editorRef.current.innerHTML = value;
			isInitializedRef.current = true;
		}
	}, [ value ] );

	const applyFormatting = ( tag ) => {
		document.execCommand( tag, false, null );
		editorRef.current?.focus();
		updateValue();
	};

	const handleAddLink = () => {
		if ( ! linkUrl.trim() ) {
			return;
		}

		const selection = window.getSelection();
		if ( selection.rangeCount === 0 ) {
			return;
		}

		const range = selection.getRangeAt( 0 );
		const linkEl = document.createElement( 'a' );
		linkEl.href = linkUrl;
		linkEl.textContent = range.toString() || linkUrl;

		try {
			range.insertNode( linkEl );
		} catch ( e ) {
			document.execCommand( 'createLink', false, linkUrl );
		}

		setLinkUrl( '' );
		setShowLinkPopover( false );
		updateValue();
		editorRef.current?.focus();
	};

	const updateValue = () => {
		if ( editorRef.current ) {
			onChange( editorRef.current.innerHTML );
		}
	};

	const handleInput = () => {
		updateValue();
	};

	const handleKeyDown = ( e ) => {
		// Allow standard keyboard shortcuts
		if ( ( e.ctrlKey || e.metaKey ) && e.key === 'b' ) {
			e.preventDefault();
			applyFormatting( 'bold' );
		} else if ( ( e.ctrlKey || e.metaKey ) && e.key === 'i' ) {
			e.preventDefault();
			applyFormatting( 'italic' );
		}
	};

	return (
		<div className="edac-rich-textarea-wrapper">
			{ label && (
				<label className="edac-rich-textarea-label">{ label }</label>
			) }

			<div className="edac-rich-textarea-toolbar">
				<Button
					icon={ formatBold }
					label={ __( 'Bold (Ctrl+B)', 'accessibility-checker' ) }
					onClick={ () => applyFormatting( 'bold' ) }
					disabled={ disabled }
					size="small"
				/>
				<Button
					icon={ formatItalic }
					label={ __( 'Italic (Ctrl+I)', 'accessibility-checker' ) }
					onClick={ () => applyFormatting( 'italic' ) }
					disabled={ disabled }
					size="small"
				/>
				<Button
					ref={ linkButtonRef }
					icon={ link }
					label={ __( 'Link', 'accessibility-checker' ) }
					onClick={ () => setShowLinkPopover( ! showLinkPopover ) }
					disabled={ disabled }
					size="small"
					isPressed={ showLinkPopover }
				/>
				{ showLinkPopover && linkButtonRef.current && (
					<Popover
						anchor={ linkButtonRef.current }
						onClose={ () => setShowLinkPopover( false ) }
						placement="bottom"
					>
						<div className="edac-rich-textarea-link-popover">
							<input
								type="url"
								placeholder={ __( 'https://example.com', 'accessibility-checker' ) }
								value={ linkUrl }
								onChange={ ( e ) => setLinkUrl( e.target.value ) }
								onKeyPress={ ( e ) => e.key === 'Enter' && handleAddLink() }
								className="edac-rich-textarea-link-input"
								autoFocus
							/>
							<Button
								variant="primary"
								onClick={ handleAddLink }
								size="small"
							>
								{ __( 'Add', 'accessibility-checker' ) }
							</Button>
						</div>
					</Popover>
				) }
			</div>

			<div
				ref={ editorRef }
				contentEditable={ ! disabled }
				suppressContentEditableWarning
				onInput={ handleInput }
				onKeyDown={ handleKeyDown }
				onBlur={ updateValue }
				className="edac-rich-textarea"
				style={ { minHeight: `${ rows * 24 }px` } }
			/>

			{ help && (
				<p className="edac-rich-textarea-help">{ help }</p>
			) }
		</div>
	);
};

export default RichTextarea;
