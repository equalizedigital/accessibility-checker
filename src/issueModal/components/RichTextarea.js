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
	const [ isBold, setIsBold ] = useState( false );
	const [ isItalic, setIsItalic ] = useState( false );

	// Persist the selection so toolbar clicks can restore it after focus moves.
	useEffect( () => {
		const onSelectionChange = () => {
			if (
				document.activeElement === editorRef.current ||
				editorRef.current?.contains( document.activeElement )
			) {
				saveSelection();
			}
		};

		document.addEventListener( 'selectionchange', onSelectionChange );
		return () => document.removeEventListener( 'selectionchange', onSelectionChange );
	}, [] );

	// Capture-phase native listener to intercept formatting shortcuts before
	// browser extensions or other global listeners can steal them.
	useEffect( () => {
		const editor = editorRef.current;
		if ( ! editor ) {
			return;
		}

		const onKeyDownCapture = ( e ) => {
			if ( ( e.ctrlKey || e.metaKey ) && ( e.key === 'b' || e.key === 'i' || e.key === 'k' ) ) {
				e.stopImmediatePropagation();
			}
		};

		editor.addEventListener( 'keydown', onKeyDownCapture, true );
		return () => editor.removeEventListener( 'keydown', onKeyDownCapture, true );
	}, [] );

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
		// Only restore focus and selection when the editor doesn't have focus
		// (i.e. when called from a toolbar button click). Keyboard shortcuts
		// fire from within the editor where focus and selection are already correct.
		if ( document.activeElement !== editorRef.current ) {
			editorRef.current?.focus();
			restoreSelection();
		}

		const selection = window.getSelection();
		const hasSelection = selection && ! selection.isCollapsed;

		document.execCommand( tag, false, null );

		if ( hasSelection ) {
			// Text was selected — formatting applied to selection only.
			if ( selection.rangeCount > 0 ) {
				selection.collapseToEnd();
			}
			// The caret is now inside the formatted element (e.g. <strong>)
			// so the browser still considers future typing to be in that
			// format. Toggle the command again to turn it off.
			document.execCommand( tag, false, null );

			saveSelection();
			updateValue();
		} else {
			// No selection — toggle format mode for future typing.
			// Don't call updateValue() here — no DOM change has happened yet
			// and triggering a re-render can reset the browser's format toggle.
			if ( tag === 'bold' ) {
				setIsBold( ( prev ) => ! prev );
			}
			if ( tag === 'italic' ) {
				setIsItalic( ( prev ) => ! prev );
			}
			saveSelection();
		}
	};

	const handleAddLink = () => {
		if ( ! linkUrl.trim() ) {
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
		if ( ( e.ctrlKey || e.metaKey ) && e.key === 'b' ) {
			e.preventDefault();
			e.stopPropagation();
			applyFormatting( 'bold' );
		} else if ( ( e.ctrlKey || e.metaKey ) && e.key === 'i' ) {
			e.preventDefault();
			e.stopPropagation();
			applyFormatting( 'italic' );
		} else if ( ( e.ctrlKey || e.metaKey ) && e.key === 'k' ) {
			e.preventDefault();
			e.stopPropagation();
			saveSelection();
			setShowLinkPopover( ( prev ) => ! prev );
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
					isPressed={ isBold }
				/>
				<Button
					icon={ formatItalic }
					label={ __( 'Italic (Ctrl+I)', 'accessibility-checker' ) }
					onClick={ () => applyFormatting( 'italic' ) }
					disabled={ disabled }
					size="small"
					isPressed={ isItalic }
				/>
				<Button
					ref={ linkButtonRef }
					icon={ link }
					label={ __( 'Link (Ctrl+K)', 'accessibility-checker' ) }
					onClick={ handleLinkButtonClick }
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
