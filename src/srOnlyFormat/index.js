/**
 * Screen Reader Only text format for the block editor.
 *
 * Registers a RichText format type that wraps selected text in a visually
 * hidden span, keeping it accessible to screen readers while hiding it visually.
 */

import { registerFormatType, toggleFormat } from '@wordpress/rich-text';
import { RichTextToolbarButton } from '@wordpress/block-editor';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { initializeSrOnlyVisibilityPreference } from './utils/visibility';

const name = 'edac/sr-only';
const title = __( 'Screen Reader Only', 'accessibility-checker' );

initializeSrOnlyVisibilityPreference( 'show_sr_text_in_editor' );

const SrOnlyEdit = ( { isActive, value, onChange, onFocus } ) => {
	const onToggle = () => {
		onChange( toggleFormat( value, { type: name } ) );
	};

	const onClick = () => {
		onToggle();
		onFocus();
	};

	return createElement( RichTextToolbarButton, {
		icon: 'hidden',
		title,
		onClick,
		isActive,
	} );
};

registerFormatType( name, {
	title,
	tagName: 'span',
	className: 'text-format-sr-only',
	edit: SrOnlyEdit,
} );
