/**
 * Screen Reader Only text format for the block editor.
 *
 * Registers a RichText format type that wraps selected text in a visually
 * hidden span, keeping it accessible to screen readers while hiding it visually.
 *
 * In the Full Site Editor, also registers a "More" menu item so users can
 * toggle the "always show" preference without the Accessibility Checker sidebar.
 */

import { registerFormatType, toggleFormat } from '@wordpress/rich-text';
import { RichTextToolbarButton } from '@wordpress/block-editor';
import { createElement, useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { PluginMoreMenuItem } from '@wordpress/editor';
import apiFetch from '@wordpress/api-fetch';
import { initializeSrOnlyVisibilityPreference, applySrOnlyVisibility, fetchUserMetaValue } from './utils/visibility';
import { createPreferenceToggleHandler } from './utils/preferenceToggle';

const name = 'edac/sr-only';
const title = __( 'Screen Reader Only', 'accessibility-checker' );

initializeSrOnlyVisibilityPreference( 'show_sr_text_in_editor' );

const SrOnlyEdit = ( { isActive, value, onChange, onFocus } ) => {
	const onToggle = () => {
		onChange( toggleFormat( value, { type: name } ) );
	};

	const onClick = () => {
		onToggle();
		onFocus?.();
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

/**
 * Menu item for the Full Site Editor "Options" (three-dot) menu.
 *
 * Renders a toggleable "Always show screen reader text" entry so users editing
 * in FSE can control the visibility preference without the Accessibility
 * Checker sidebar (which is only available in the post editor).
 */
const SrOnlyFormatMenuItem = () => {
	const [ checked, setChecked ] = useState( false );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ isLoading, setIsLoading ] = useState( true );

	useEffect( () => {
		let isMounted = true;

		fetchUserMetaValue( 'show_sr_text_in_editor' )
			.then( ( value ) => {
				if ( isMounted ) {
					setChecked( value );
					setIsLoading( false );
				}
			} )
			.catch( () => {
				if ( isMounted ) {
					setIsLoading( false );
				}
			} );

		return () => {
			isMounted = false;
		};
	}, [] );

	const handleClick = createPreferenceToggleHandler( {
		getChecked: () => checked,
		isBlocked: () => isLoading || isSaving,
		setChecked,
		setIsSaving,
		applyVisibility: applySrOnlyVisibility,
		savePreference: ( nextChecked ) =>
			apiFetch( {
				path: '/wp/v2/users/me',
				method: 'POST',
				data: { meta: { show_sr_text_in_editor: nextChecked } },
			} ),
	} );

	return createElement(
		PluginMoreMenuItem,
		{
			icon: checked ? 'visibility' : 'hidden',
			onClick: handleClick,
			disabled: isLoading || isSaving,
		},
		__( 'Always show screen reader text', 'accessibility-checker' )
	);
};

if ( window.edacSrOnlyFormat?.isFSE ) {
	registerPlugin( 'edac-sr-only-format-menu', {
		render: SrOnlyFormatMenuItem,
	} );
}
