/**
 * Rich Text Textarea Component
 *
 * Supports basic HTML formatting: bold, italic, underline, and links.
 */

import { __ } from '@wordpress/i18n';
import { Button, Popover } from '@wordpress/components';
import { useRef, useState, useEffect } from '@wordpress/element';
import { formatBold, formatItalic, link } from '@wordpress/icons';
import './rich-textarea.scss';

/**
 * WordPress doesn't ship a formatUnderline icon, so we define one inline.
 */
const formatUnderline = (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
		<path d="M7 18v1.5h10V18H7zM5.5 4v6.5c0 3.6 2.9 6.5 6.5 6.5s6.5-2.9 6.5-6.5V4H17v6.5c0 2.8-2.2 5-5 5s-5-2.2-5-5V4H5.5z" />
	</svg>
);

/**
 * Formatting shortcut definitions keyed by the character that triggers them.
 */
const FORMATTING_SHORTCUTS = {
	b: 'bold',
	i: 'italic',
	u: 'underline',
};

/**
 * Rich Text Textarea Component
 *
 * Supports basic HTML formatting: bold, italic, underline, and links.
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
	const [ isUnderline, setIsUnderline ] = useState( false );

	/**
	 * Map of execCommand names to their React state setters.
	 */
	const FORMAT_SETTERS = {
		bold: setIsBold,
		italic: setIsItalic,
		underline: setIsUnderline,
	};

	// ── Selection persistence ──────────────────────────────────────────
	// Continuously save the selection while the editor has focus so that
	// toolbar button clicks (which steal focus) can restore it.

	useEffect( () => {
		const onSelectionChange = () => {
			if (
				document.activeElement === editorRef.current ||
				editorRef.current?.contains( document.activeElement )
			) {
				saveSelection();
				setIsBold( document.queryCommandState( 'bold' ) );
				setIsItalic( document.queryCommandState( 'italic' ) );
				setIsUnderline( document.queryCommandState( 'underline' ) );
			}
		};
		document.addEventListener( 'selectionchange', onSelectionChange );
		return () => document.removeEventListener( 'selectionchange', onSelectionChange );
	}, [] );

	// ── Capture-phase shortcut guard ───────────────────────────────────
	// Intercept formatting shortcuts on the native capture phase before
	// browser extensions or other global listeners can steal them.

	useEffect( () => {
		const editor = editorRef.current;
		if ( ! editor ) {
			return;
		}
		const onKeyDownCapture = ( e ) => {
			const key = e.key.toLowerCase();
			if ( ( e.ctrlKey || e.metaKey ) && ( FORMATTING_SHORTCUTS[ key ] || key === 'k' ) ) {
				e.preventDefault();
			}
		};
		editor.addEventListener( 'keydown', onKeyDownCapture, true );
		return () => editor.removeEventListener( 'keydown', onKeyDownCapture, true );
	}, [] );

	// ── Content initialisation ─────────────────────────────────────────

	useEffect( () => {
		if ( editorRef.current && ! isInitializedRef.current ) {
			editorRef.current.innerHTML = value;
			isInitializedRef.current = true;
			lastValueRef.current = value;
		}
	}, [ value ] );

	// Sync content when an external value change arrives while the editor
	// is not focused (e.g. undo from a parent form).
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

	// ── Helpers ────────────────────────────────────────────────────────

	const saveSelection = () => {
		const sel = window.getSelection();
		if ( sel.rangeCount > 0 ) {
			savedSelectionRef.current = sel.getRangeAt( 0 );
			return true;
		}
		return false;
	};

	const restoreSelection = () => {
		const sel = window.getSelection();
		if ( savedSelectionRef.current ) {
			try {
				sel.removeAllRanges();
				sel.addRange( savedSelectionRef.current );
				return true;
			} catch {
				return false;
			}
		}
		return false;
	};

	/**
	 * Make sure the editor has focus and the last-known selection is
	 * restored. This is a no-op when the editor already has focus
	 * (e.g. when invoked from a keyboard shortcut).
	 */
	const ensureEditorFocus = () => {
		if ( document.activeElement !== editorRef.current ) {
			editorRef.current?.focus();
			restoreSelection();
		}
	};

	const updateValue = () => {
		if ( editorRef.current ) {
			const nextValue = editorRef.current.innerHTML;
			lastValueRef.current = nextValue;
			onChange( nextValue );
		}
	};

	const isValidUrl = ( url ) => {
		const trimmed = url.trim();
		const allowedProtocols = /^(https?:|mailto:)/i;
		if ( /^\/[^/]/.test( trimmed ) || /^\/\//.test( trimmed ) ) {
			return true;
		}
		if ( /^[a-z][a-z0-9+.-]*:/i.test( trimmed ) ) {
			return allowedProtocols.test( trimmed );
		}
		return true;
	};

	// ── Formatting ─────────────────────────────────────────────────────

	/**
	 * Apply formatting. Behaviour differs based on whether text is selected:
	 *
	 * - **Selection exists:** format the selected text only. The browser's
	 *   format mode is toggled off afterwards so subsequent typing is plain.
	 * - **No selection (caret only):** toggle the browser's format mode for
	 *   future typing and update the pressed state on the toolbar button.
	 *
	 * @param {string} command The execCommand name ('bold' | 'italic' | 'underline').
	 */
	const applyFormatting = ( command ) => {
		ensureEditorFocus();

		const sel = window.getSelection();
		const hasTextSelected = sel && ! sel.isCollapsed;

		// Let the browser apply the formatting.
		document.execCommand( command, false, null );

		if ( hasTextSelected ) {
			// Collapse the selection so the user can keep typing.
			if ( sel.rangeCount > 0 ) {
				sel.collapseToEnd();
			}
			// The caret now sits inside the new formatting element. Toggle
			// the command once more so subsequent typing exits the format.
			document.execCommand( command, false, null );

			saveSelection();
			updateValue();
		} else {
			// Toggle mode — update the toolbar button's pressed state.
			// Do NOT call updateValue() because the DOM hasn't changed yet
			// and a re-render can reset the browser's pending format toggle.
			const setter = FORMAT_SETTERS[ command ];
			if ( setter ) {
				setter( ( prev ) => ! prev );
			}
			saveSelection();
		}
	};

	// ── Link handling ──────────────────────────────────────────────────

	const handleAddLink = () => {
		if ( ! linkUrl.trim() || ! isValidUrl( linkUrl ) ) {
			return;
		}

		if ( ! restoreSelection() ) {
			editorRef.current?.focus();
		}

		document.execCommand( 'createLink', false, linkUrl );

		setLinkUrl( '' );
		setShowLinkPopover( false );
		updateValue();
	};

	const handleLinkButtonClick = () => {
		if ( ! showLinkPopover ) {
			saveSelection();
		}
		setShowLinkPopover( ! showLinkPopover );
	};

	// ── Event handlers ─────────────────────────────────────────────────

	const handleKeyDown = ( e ) => {
		if ( ! ( e.ctrlKey || e.metaKey ) ) {
			return;
		}

		const key = e.key.toLowerCase();
		const command = FORMATTING_SHORTCUTS[ key ];
		if ( command ) {
			e.preventDefault();
			e.stopPropagation();
			applyFormatting( command );
			return;
		}

		if ( key === 'k' ) {
			e.preventDefault();
			e.stopPropagation();
			saveSelection();
			setShowLinkPopover( ( prev ) => ! prev );
		}
	};

	// ── Render ─────────────────────────────────────────────────────────

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
					icon={ formatUnderline }
					label={ __( 'Underline (Ctrl+U)', 'accessibility-checker' ) }
					onClick={ () => applyFormatting( 'underline' ) }
					disabled={ disabled }
					size="small"
					isPressed={ isUnderline }
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
								onKeyDown={ ( e ) => e.key === 'Enter' && handleAddLink() }
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
				onInput={ updateValue }
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
