/**
 * Build a click handler for toggling a boolean preference with optimistic UI updates.
 *
 * The handler prevents concurrent saves and restores the previous value on save failure.
 *
 * @param {Object}   config                 - Handler configuration.
 * @param {Function} config.getChecked      - Returns the current boolean value.
 * @param {Function} config.isBlocked       - Returns true when interactions should be ignored.
 * @param {Function} config.setChecked      - Updates the local checked state.
 * @param {Function} config.setIsSaving     - Updates local saving state.
 * @param {Function} config.applyVisibility - Applies current visibility to the editor UI.
 * @param {Function} config.savePreference  - Persists the next value.
 * @return {Function} Async click handler.
 */
export const createPreferenceToggleHandler = ( {
	getChecked,
	isBlocked,
	setChecked,
	setIsSaving,
	applyVisibility,
	savePreference,
} ) => async () => {
	if ( isBlocked() ) {
		return;
	}

	const previousChecked = getChecked();
	const nextChecked = ! previousChecked;

	setChecked( nextChecked );
	setIsSaving( true );
	applyVisibility( nextChecked );

	try {
		await savePreference( nextChecked );
	} catch {
		setChecked( previousChecked );
		applyVisibility( previousChecked );
	} finally {
		setIsSaving( false );
	}
};

