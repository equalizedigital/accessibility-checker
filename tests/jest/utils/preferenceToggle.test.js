import { createPreferenceToggleHandler } from '../../../src/srOnlyFormat/utils/preferenceToggle';

describe( 'createPreferenceToggleHandler', () => {
	it( 'blocks concurrent saves while a request is in flight', async () => {
		let checked = false;
		let isSaving = false;
		let resolveSave;

		const savePreference = jest.fn(
			() =>
				new Promise( ( resolve ) => {
					resolveSave = resolve;
				} )
		);

		const handleClick = createPreferenceToggleHandler( {
			getChecked: () => checked,
			isBlocked: () => isSaving,
			setChecked: ( next ) => {
				checked = next;
			},
			setIsSaving: ( next ) => {
				isSaving = next;
			},
			applyVisibility: jest.fn(),
			savePreference,
		} );

		const firstClick = handleClick();
		const secondClick = handleClick();

		expect( savePreference ).toHaveBeenCalledTimes( 1 );
		await secondClick;

		resolveSave();
		await firstClick;
	} );

	it( 'reverts optimistic state when save fails', async () => {
		let checked = true;
		let isSaving = false;
		const setChecked = jest.fn( ( next ) => {
			checked = next;
		} );
		const setIsSaving = jest.fn( ( next ) => {
			isSaving = next;
		} );
		const applyVisibility = jest.fn();

		const handleClick = createPreferenceToggleHandler( {
			getChecked: () => checked,
			isBlocked: () => isSaving,
			setChecked,
			setIsSaving,
			applyVisibility,
			savePreference: jest.fn( async () => {
				throw new Error( 'Save failed' );
			} ),
		} );

		await handleClick();

		expect( setChecked ).toHaveBeenNthCalledWith( 1, false );
		expect( setChecked ).toHaveBeenNthCalledWith( 2, true );
		expect( applyVisibility ).toHaveBeenNthCalledWith( 1, false );
		expect( applyVisibility ).toHaveBeenNthCalledWith( 2, true );
		expect( setIsSaving ).toHaveBeenNthCalledWith( 1, true );
		expect( setIsSaving ).toHaveBeenNthCalledWith( 2, false );
	} );
} );
