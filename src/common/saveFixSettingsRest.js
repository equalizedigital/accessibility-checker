import { __, sprintf } from '@wordpress/i18n';

export const saveFixSettings = ( fixSettingsContainer ) => {
	const settingsToSave = {};

	fixSettingsContainer.querySelectorAll( 'input, select, textarea' ).forEach( ( field ) => {
		// if the field changes then emit a custom event
		field.addEventListener( 'change', () => {
			document.dispatchEvent( new CustomEvent( 'edac-fix-settings-change' ) );
		} );

		const fixGroup = field.getAttribute( 'data-fix-slug' );
		if ( ! fixGroup ) {
			// a group is required to save. If it's not there, skip this field.
			return;
		}
		if ( settingsToSave[ fixGroup ] === undefined ) {
			settingsToSave[ fixGroup ] = {};
		}

		// Value to save for checkboxes differs to other field types.
		switch ( field.type ) {
			case 'checkbox':
				settingsToSave[ fixGroup ][ field.name ] = field.checked;
				break;

			default:
				settingsToSave[ fixGroup ][ field.name ] = field.value;
		}
	} );

	const fixButtons = fixSettingsContainer.querySelectorAll( 'button' );
	fixButtons.forEach( ( button ) => {
		// make all buttons disabled while saving
		button.disabled = true;
	} );

	fixSettingsContainer.classList.add( 'edac-fix-settings--saving' );
	const liveRegion = fixSettingsContainer.querySelector( '[aria-live]' );
	if ( liveRegion ) {
		liveRegion.innerText = __( 'Saving...', 'accessibility-checker' );
	}

	// make a rest call to save the settings
	fetch( '/wp-json/edac/v1/fixes/update/', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': window.edacSettings?.nonce ?? window.edac_script_vars?.restNonce ?? window.edacFrontendHighlighterApp?.restNonce,
		},
		body: JSON.stringify( settingsToSave ),
	} ).then(
		( response ) => {
			fixSettingsContainer.classList.remove( 'edac-fix-settings--saving' );
			fixButtons.forEach( ( button ) => {
				button.disabled = false;
			} );
			if ( response.ok ) {
				fixSettingsContainer.classList.remove( 'edac-fix-settings--saved--error' );
				fixSettingsContainer.classList.add( 'edac-fix-settings--saved--success' );
				// find the aria-live region and update the text
				if ( liveRegion ) {
					const editLink = window?.edacFrontendHighlighterApp?.editorLink || window?.edac_script_vars?.editorLink;
					if ( editLink ) {
						liveRegion.innerHTML = sprintf(
							__( 'Settings saved successfully. You must %svisit the editor%s and save the post to rescan and remove fixed issues from ', 'accessibility-checker' ) + 'Accessibility Checker' + __( ' reports.', 'accessibility-checker' ),
							`<a href="${ editLink }">`,
							'</a>'
						);
					} else {
						liveRegion.innerText = __( 'Settings saved successfully.', 'accessibility-checker' );
					}
				}
			} else {
				fixSettingsContainer.classList.add( 'edac-fix-settings--saved--error' );
				fixSettingsContainer.querySelector( '[aria-live]' ).innerText = __( 'Saving failed.', 'accessibility-checker' );
			}

			document.dispatchEvent( new CustomEvent( 'edac-fix-settings-saved', { detail: { success: response.ok } } ) );
		}
	);
};
