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
 * Formatting button definitions.
 */
const FORMAT_BUTTONS = [
	{ command: 'bold', icon: formatBold, label: __( 'Bold (Ctrl+B)', 'accessibility-checker' ), shortcutKey: 'b' },
	{ command: 'italic', icon: formatItalic, label: __( 'Italic (Ctrl+I)', 'accessibility-checker' ), shortcutKey: 'i' },
	{ command: 'underline', icon: formatUnderline, label: __( 'Underline (Ctrl+U)', 'accessibility-checker' ), shortcutKey: 'u' },
];

/**
 * Rich Text Textarea Component
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
	const linkButtonMouseDownRef = useRef( false );
	const popoverRef = useRef( null );

	const [ linkUrl, setLinkUrl ] = useState( '' );
	const [ showLinkPopover, setShowLinkPopover ] = useState( false );

	// Pressed state for formatting toggle mode (no selection).
	const [ pressed, setPressed ] = useState( { bold: false, italic: false, underline: false } );

	useEffect( () => {
		if ( editorRef.current && ! isInitializedRef.current ) {
			editorRef.current.innerHTML = value;
			isInitializedRef.current = true;
			lastValueRef.current = value;
		}
	}, [ value ] );

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

	useEffect( () => {
		const editor = editorRef.current;
		if ( ! editor ) {
			return;
		}
		const guard = ( e ) => {
			if ( ( e.ctrlKey || e.metaKey ) && [ 'b', 'i', 'u', 'k' ].includes( e.key ) ) {
				e.stopImmediatePropagation();
			}
		};
		editor.addEventListener( 'keydown', guard, true );
		return () => editor.removeEventListener( 'keydown', guard, true );
	}, [] );

	// ── Selection helpers ──────────────────────────────────────────────

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

	const updateValue = () => {
		if ( editorRef.current ) {
			const next = editorRef.current.innerHTML;
			lastValueRef.current = next;
			onChange( next );
		}
	};

	/**
	 * Apply formatting. The editor still has focus because formatting
	 * buttons use onMouseDown + preventDefault to keep it.
	 *
	 * - **Text selected:** format it, then exit the format context so
	 *   subsequent typing is plain.
	 * - **No selection:** toggle format mode for future typing and flip
	 *   the toolbar button's pressed state.
	 *
	 * @param {string} command execCommand name ('bold' | 'italic' | 'underline').
	 */
	const applyFormatting = ( command ) => {
		const sel = window.getSelection();
		const hasTextSelected = sel && ! sel.isCollapsed;

		document.execCommand( command, false, null );

		if ( hasTextSelected ) {
			// Collapse to end so the user can keep typing.
			if ( sel.rangeCount > 0 ) {
				sel.collapseToEnd();
			}
			// The caret is now inside the formatted element. Toggle the
			// command once more so subsequent typing exits the format.
			document.execCommand( command, false, null );

			// Clear all toggle states — we're done formatting a selection.
			setPressed( { bold: false, italic: false, underline: false } );
			updateValue();
		} else {
			// Toggle mode — read the browser's actual state for every
			// format so all buttons stay in sync. The browser may have
			// implicitly changed state for other commands.
			setPressed( {
				bold: document.queryCommandState( 'bold' ),
				italic: document.queryCommandState( 'italic' ),
				underline: document.queryCommandState( 'underline' ),
			} );
		}
	};

	// ── Link handling ──────────────────────────────────────────────────

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

	const handleKeyDown = ( e ) => {
		if ( ! ( e.ctrlKey || e.metaKey ) ) {
			return;
		}

		// Formatting shortcuts.
		const btn = FORMAT_BUTTONS.find( ( b ) => b.shortcutKey === e.key );
		if ( btn ) {
			e.preventDefault();
			e.stopPropagation();
			applyFormatting( btn.command );
			return;
		}

		// Link shortcut.
		if ( e.key === 'k' ) {
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
				{ FORMAT_BUTTONS.map( ( { command, icon, label: btnLabel } ) => (
					<Button
						key={ command }
						icon={ icon }
						label={ btnLabel }
						disabled={ disabled }
						size="small"
						isPressed={ pressed[ command ] }
						onMouseDown={ ( e ) => {
							// Prevent the button from stealing focus from the
							// editor so execCommand always has the right context.
							e.preventDefault();
							applyFormatting( command );
						} }
					/>
				) ) }
				<Button
					ref={ linkButtonRef }
					icon={ link }
					label={ __( 'Link (Ctrl+K)', 'accessibility-checker' ) }
					onClick={ handleLinkButtonClick }
					onMouseDown={ () => {
						// When the popover is open, flag that the close came
						// from this button so onClose can yield to onClick.
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
							if ( linkButtonMouseDownRef.current ) {
								linkButtonMouseDownRef.current = false;
								return;
							}
							setShowLinkPopover( false );
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
