import { act } from 'react';
import apiFetch from '@wordpress/api-fetch';
import FixCard from '../../../src/issueModal/components/FixCard';
import { renderReact } from '../helpers/renderReact';

jest.mock( '@wordpress/api-fetch', () => ( {
	__esModule: true,
	default: jest.fn(),
} ), { virtual: true } );

jest.mock( '@wordpress/components', () => ( {
	Button: ( { children, type = 'button', disabled } ) => (
		<button type={ type } disabled={ disabled }>{ children }</button>
	),
	Spinner: () => <span className="mock-spinner" aria-hidden="true">...</span>,
	Notice: ( { children } ) => <div className="mock-notice">{ children }</div>,
	ToggleControl: ( { label, checked, onChange } ) => (
		<label>
			{ label }
			<input type="checkbox" checked={ checked } onChange={ ( event ) => onChange( event.target.checked ) } />
		</label>
	),
	TextControl: ( { id, value, onChange } ) => (
		<input id={ id } value={ value } onChange={ ( event ) => onChange( event.target.value ) } />
	),
	TextareaControl: ( { id, value, onChange } ) => (
		<textarea id={ id } value={ value } onChange={ ( event ) => onChange( event.target.value ) } />
	),
} ) );

const flushPromises = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

describe( 'FixCard', () => {
	beforeEach( () => {
		apiFetch.mockReset();
	} );

	test( 'uses the canonical namespace to load and save fix settings', async () => {
		apiFetch
			.mockResolvedValueOnce( {
				success: true,
				fix_slug: 'meta_viewport_scalable',
				fix_name: 'Meta Viewport Scalable',
				enabled: false,
				fields: {
					edac_fix_meta_viewport_scalable: {
						label: 'Enable fix',
						description: '',
						type: 'checkbox',
						value: '0',
					},
				},
			} )
			.mockResolvedValueOnce( { success: true } );

		const onSave = jest.fn();
		const { container, unmount } = renderReact(
			<FixCard slug="meta_viewport_scalable" onSave={ onSave } onError={ jest.fn() } />,
		);

		await act( async () => {
			await flushPromises();
		} );

		expect( apiFetch ).toHaveBeenNthCalledWith( 1, {
			path: '/accessibility-checker/v1/fix-fields/meta_viewport_scalable',
			method: 'GET',
		} );

		await act( async () => {
			container.querySelector( 'form' ).dispatchEvent(
				new Event( 'submit', { bubbles: true, cancelable: true } ),
			);
			await flushPromises();
		} );

		expect( apiFetch ).toHaveBeenNthCalledWith( 2, {
			path: '/accessibility-checker/v1/fixes/update',
			method: 'POST',
			data: {
				meta_viewport_scalable: {
					edac_fix_meta_viewport_scalable: false,
				},
			},
		} );
		expect( onSave ).toHaveBeenCalledTimes( 1 );

		unmount();
	} );
} );
