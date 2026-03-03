/**
 * Rich Text Textarea Component
 *
 * Plain text input using a contenteditable element.
 */

import { useRef, useEffect } from '@wordpress/element';
import './rich-textarea.scss';

/**
 * Rich Text Textarea Component
 *
 * @param {Object}   props          - Component props.
 * @param {string}   props.value    - Current text value (HTML string).
 * @param {Function} props.onChange - Change handler.
 * @param {string}   props.label    - Field label.
 * @param {string}   props.labelId  - ID for aria-labelledby (optional, used if label exists).
 * @param {string}   props.help     - Help text.
 * @param {string}   props.helpId   - ID for aria-describedby (optional, used if help exists).
 * @param {number}   props.rows     - Number of rows.
 * @param {boolean}  props.disabled - Whether field is disabled.
 */
export const RichTextarea = ( { value, onChange, label, labelId, help, helpId, rows = 3, disabled = false } ) => {
	const editorRef = useRef( null );
	const isInitializedRef = useRef( false );
	const lastValueRef = useRef( value );

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

	const updateValue = () => {
		if ( editorRef.current ) {
			const next = editorRef.current.innerHTML;
			lastValueRef.current = next;
			onChange( next );
		}
	};

	// Build aria attributes for the contenteditable div.
	const ariaAttrs = {};
	if ( label && labelId ) {
		ariaAttrs[ 'aria-labelledby' ] = labelId;
	}
	if ( help && helpId ) {
		ariaAttrs[ 'aria-describedby' ] = helpId;
	}

	return (
		<div className="edac-rich-textarea-wrapper">
			{ label && (
				<label id={ labelId } className="edac-rich-textarea-label">{ label }</label>
			) }

			<div
				ref={ editorRef }
				contentEditable={ ! disabled }
				suppressContentEditableWarning
				onInput={ updateValue }
				onBlur={ updateValue }
				className="edac-rich-textarea"
				style={ { minHeight: `${ rows * 24 }px` } }
				{ ...ariaAttrs }
			/>

			{ help && (
				<p id={ helpId } className="edac-rich-textarea-help">{ help }</p>
			) }
		</div>
	);
};

export default RichTextarea;
