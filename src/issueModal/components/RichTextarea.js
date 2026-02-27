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
	const lastValueRef = useRef( value );
	const savedSelectionRef = useRef( null );
	const [ linkUrl, setLinkUrl ] = useState( '' );
	const [ showLinkPopover, setShowLinkPopover ] = useState( false );
	const linkButtonMouseDownRef = useRef( false );
	const popoverRef = useRef( null );

	// Initialize content only once
	useEffect( () => {
		if ( editorRef.current && ! isInitializedRef.current ) {
			editorRef.current.innerHTML = value;
			isInitializedRef.current = true;
			lastValueRef.current = value;
		}
	}, [ value ] );

	// Update content when external value changes and editor isn't focused
	useEffect( () => {
		if ( ! editorRef.current ) {
			return;
		}
		if ( document.activeElement === editorRef.current ) {
			return;
		}
		if ( value !== lastValueRef.current ) {
			editorRef.current.innerHTML = value || '';
			lastValueRef.current = value;
		}
	}, [ value ] );

	const saveSelection = () => {
		const selection = window.getSelection();
		if ( selection.rangeCount > 0 ) {
			savedSelectionRef.current = selection.getRangeAt( 0 );
			return true;
		}
		return false;
	};

	const restoreSelection = () => {
		const selection = window.getSelection();
		if ( savedSelectionRef.current ) {
			try {
				selection.removeAllRanges();
				selection.addRange( savedSelectionRef.current );
				return true;
			} catch ( e ) {
				return false;
			}
		}
		return false;
	};

	const applyFormatting = ( tag ) => {
		document.execCommand( tag, false, null );
		editorRef.current?.focus();
		updateValue();
	};

	const isValidUrl = ( url ) => {
		const trimmed = url.trim();
		// Block dangerous protocols like javascript:, data:, vbscript:, etc.
		const allowedProtocols = /^(https?:|mailto:)/i;
		// Also allow protocol-relative URLs and relative paths.
		if ( /^\/[^/]/.test( trimmed ) || /^\/\//.test( trimmed ) ) {
			return true;
		}
		// If it has a protocol, it must be in the allowed list.
		if ( /^[a-z][a-z0-9+.-]*:/i.test( trimmed ) ) {
			return allowedProtocols.test( trimmed );
		}
		// No protocol — treat as a relative URL or bare domain (safe).
		return true;
	};

	const handleAddLink = () => {
		if ( ! linkUrl.trim() ) {
			return;
		}

		if ( ! isValidUrl( linkUrl ) ) {
			return;
		}

		// Restore the selection before applying the link
		if ( ! restoreSelection() ) {
			editorRef.current?.focus();
		}

		// Use execCommand to create the link properly in contentEditable
		document.execCommand( 'createLink', false, linkUrl );

		setLinkUrl( '' );
		setShowLinkPopover( false );
		updateValue();
	};

	const handleLinkButtonClick = () => {
		if ( ! showLinkPopover ) {
			// Save selection before opening popover
			saveSelection();
		}
		setShowLinkPopover( ! showLinkPopover );
	};

	const updateValue = () => {
		if ( editorRef.current ) {
			const nextValue = editorRef.current.innerHTML;
			lastValueRef.current = nextValue;
			onChange( nextValue );
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
					onClick={ handleLinkButtonClick }
					onMouseDown={ () => {
						// Only set this flag when the popover is already open, so
						// the Popover's onClose handler can yield to the button's
						// onClick toggle instead of closing it a second time.
						if ( showLinkPopover ) {
							linkButtonMouseDownRef.current = true;
						}
					} }
					disabled={ disabled }
					size="small"
					aria-expanded={ showLinkPopover }
				/>
				{ showLinkPopover && linkButtonRef.current && (
					<Popover
						anchor={ linkButtonRef.current }
						onClose={ () => {
							// If the close was triggered by clicking the link button itself,
							// let the button's onClick toggle handler manage the state instead.
							if ( linkButtonMouseDownRef.current ) {
								linkButtonMouseDownRef.current = false;
								return;
							}
							setShowLinkPopover( false );
							// Only restore focus to the link button when focus is still
							// inside the popover (e.g., keyboard/Escape close). If the
							// user clicked outside, focus has already moved to their target
							// and we should not steal it back.
							if ( popoverRef.current?.contains( document.activeElement ) ) {
								linkButtonRef.current?.focus();
							}
						} }
						placement="bottom"
					>
						<div ref={ popoverRef } className="edac-rich-textarea-link-popover">
							<input
								id="edac-link-input"
								type="url"
								placeholder={ __( 'https://example.com', 'accessibility-checker' ) }
								aria-label={ __( 'Link URL', 'accessibility-checker' ) }
								value={ linkUrl }
								onChange={ ( e ) => setLinkUrl( e.target.value ) }
								onKeyDown={ ( e ) => {
									if ( e.key === 'Escape' ) {
										e.stopPropagation();
										linkButtonMouseDownRef.current = false;
										setShowLinkPopover( false );
										linkButtonRef.current?.focus();
									} else if ( e.key === 'Enter' ) {
										e.preventDefault();
										handleAddLink();
									}
								} }
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
