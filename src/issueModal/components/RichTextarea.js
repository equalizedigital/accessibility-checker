/**
 * Rich Text Textarea Component
 *
 * Plain text input using a textarea element.
 */

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
	// Build aria attributes for the textarea.
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

			<textarea
				value={ value || '' }
				onChange={ ( e ) => onChange( e.target.value ) }
				rows={ rows }
				disabled={ disabled }
				className="edac-rich-textarea"
				{ ...ariaAttrs }
			/>

			{ help && (
				<p id={ helpId } className="edac-rich-textarea-help">{ help }</p>
			) }
		</div>
	);
};

export default RichTextarea;
