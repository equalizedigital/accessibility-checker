export const saveFixSettings = ( fixSettingsContainer ) => {
	const settingsToSave = {};

	fixSettingsContainer.querySelectorAll( 'input, select, textarea' ).forEach( ( field ) => {
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

	fixSettingsContainer.classList.add( 'edac-highlight-panel-description-fix-settings--saving' );

	// make a rest call to save the settings
	fetch( '/wp-json/edac/v1/fixes/update/', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
		},
		body: JSON.stringify( settingsToSave ),
	} ).then(
		( response ) => {
			fixSettingsContainer.classList.remove( 'edac-highlight-panel-description-fix-settings--saving' );
			if ( response.ok ) {
				fixSettingsContainer.classList.remove( 'edac-highlight-panel-description-fix-settings--error' );
				fixSettingsContainer.classList.add( 'edac-highlight-panel-description-fix-settings--saved' );
			} else {
				fixSettingsContainer.classList.add( 'edac-highlight-panel-description-fix-settings--error' );
			}
		}
	);
};
