import { act } from 'react';
import UserMetaCheckboxControl from '../../../src/srOnlyFormat/components/UserMetaCheckboxControl';
import { fetchUserMetaValue } from '../../../src/srOnlyFormat/utils/visibility';
import { renderReact } from '../helpers/renderReact';

const mockCheckboxControl = jest.fn( () => null );

jest.mock( '@wordpress/components', () => ( {
	CheckboxControl: ( props ) => mockCheckboxControl( props ),
} ) );

jest.mock( '@wordpress/api-fetch', () => jest.fn(), { virtual: true } );

jest.mock( '../../../src/srOnlyFormat/utils/visibility', () => ( {
	applySrOnlyVisibility: jest.fn(),
	ensureSrOnlyVisibilityObserver: jest.fn(),
	fetchUserMetaValue: jest.fn( () => Promise.resolve( true ) ),
} ) );

describe( 'UserMetaCheckboxControl', () => {
	let consoleError;

	beforeEach( () => {
		mockCheckboxControl.mockClear();
		fetchUserMetaValue.mockClear();
		consoleError = jest.spyOn( console, 'error' ).mockImplementation( () => {} );
	} );

	afterEach( () => {
		consoleError.mockRestore();
		document.body.innerHTML = '';
	} );

	it( 'does not use defaultProps, which React 19 ignores on function components', () => {
		expect( UserMetaCheckboxControl.defaultProps ).toBeUndefined();
	} );

	it( 'renders with no props using empty defaults and loads the empty meta key', async () => {
		let view;
		await act( async () => {
			view = renderReact( <UserMetaCheckboxControl /> );
		} );

		expect( fetchUserMetaValue ).toHaveBeenCalledWith( '' );
		expect( mockCheckboxControl.mock.calls[ 0 ][ 0 ].label ).toBe( '' );
		expect( consoleError ).not.toHaveBeenCalled();
		view.unmount();
	} );

	it( 'uses the label and meta key it is given', async () => {
		let view;
		await act( async () => {
			view = renderReact( <UserMetaCheckboxControl label="Show text" metaKey="edac_show" /> );
		} );

		expect( fetchUserMetaValue ).toHaveBeenCalledWith( 'edac_show' );
		expect( mockCheckboxControl.mock.calls[ 0 ][ 0 ].label ).toBe( 'Show text' );
		view.unmount();
	} );

	it( 'calls a provided onChange with the loaded value', async () => {
		const onChange = jest.fn();
		let view;
		await act( async () => {
			view = renderReact( <UserMetaCheckboxControl label="Show text" metaKey="edac_show" onChange={ onChange } /> );
		} );

		expect( onChange ).toHaveBeenCalledWith( true );
		view.unmount();
	} );
} );
