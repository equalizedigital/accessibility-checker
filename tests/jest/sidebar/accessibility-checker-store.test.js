import apiFetch from '@wordpress/api-fetch';
import store from '../../../src/sidebar/store/accessibility-checker-store';

jest.mock( '@wordpress/api-fetch', () => jest.fn(), { virtual: true } );
jest.mock( '@wordpress/data', () => ( {
	createReduxStore: ( name, config ) => ( {
		name,
		...config,
	} ),
	register: jest.fn(),
} ), { virtual: true } );
jest.mock( '@wordpress/i18n', () => ( {
	__: ( message ) => message,
} ), { virtual: true } );

const createActionContext = ( initialLoad ) => {
	let error = null;
	const select = {
		getData: jest.fn( () => null ),
		getError: jest.fn( () => error ),
		isInitialLoad: jest.fn( () => initialLoad ),
	};
	const dispatch = jest.fn( ( action ) => {
		if ( typeof action === 'function' ) {
			return action( { dispatch, select } );
		}

		if ( action.type === 'SET_ERROR' ) {
			error = action.error;
		}

		return action;
	} );

	return { dispatch, select };
};

describe( 'accessibility checker store refetchData', () => {
	beforeEach( () => {
		jest.useFakeTimers();
		apiFetch.mockReset();
	} );

	afterEach( () => {
		jest.useRealTimers();
	} );

	it( 'settles the first promise when a second call supersedes it', async () => {
		apiFetch.mockResolvedValue( {
			success: true,
			data: {},
		} );
		const context = createActionContext( false );

		const firstSettled = jest.fn();
		const firstPromise = store.actions.refetchData( 123 )( context );
		firstPromise.then( firstSettled );

		const secondPromise = store.actions.refetchData( 123 )( context );
		await jest.advanceTimersByTimeAsync( 0 );

		expect( firstSettled ).toHaveBeenCalledWith( { status: 'superseded' } );
		expect( apiFetch ).not.toHaveBeenCalled();

		await jest.advanceTimersByTimeAsync( 200 );
		await secondPromise;

		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not supersede an initial-load refresh after its timer has started', async () => {
		let resolveInitialFetch;
		apiFetch
			.mockImplementationOnce( () => new Promise( ( resolve ) => {
				resolveInitialFetch = resolve;
			} ) )
			.mockResolvedValueOnce( {
				success: true,
				data: {},
			} );
		const context = createActionContext( true );

		let firstResult;
		const firstRefresh = store.actions.refetchData( 123 )( context );
		firstRefresh.then( ( result ) => {
			firstResult = result;
		} );

		await jest.advanceTimersByTimeAsync( 200 );
		expect( apiFetch ).toHaveBeenCalledTimes( 1 );

		context.select.isInitialLoad.mockReturnValue( false );
		const latestRefresh = store.actions.refetchData( 123 )( context );
		await Promise.resolve();
		expect( firstResult ).toBeUndefined();

		resolveInitialFetch( {
			success: true,
			data: {},
		} );
		await firstRefresh;
		expect( firstResult ).toEqual( { status: 'success' } );

		await jest.advanceTimersByTimeAsync( 200 );
		await latestRefresh;
		expect( apiFetch ).toHaveBeenCalledTimes( 2 );
	} );

	it.each( [
		[ 'a successful background refresh', false, () => apiFetch.mockResolvedValue( { success: true, data: {} } ), 'success' ],
		[ 'an unsuccessful background refresh', false, () => apiFetch.mockResolvedValue( { success: false, message: 'Refresh failed' } ), 'error' ],
		[ 'a background refresh that throws', false, () => apiFetch.mockRejectedValue( new Error( 'Network error' ) ), 'error' ],
		[ 'a successful initial load', true, () => apiFetch.mockResolvedValue( { success: true, data: {} } ), 'success' ],
		[ 'an unsuccessful initial load', true, () => apiFetch.mockResolvedValue( { success: false, message: 'Load failed' } ), 'error' ],
	] )( 'resolves the status of %s', async ( _label, initialLoad, mockResponse, status ) => {
		const consoleWarn = jest.spyOn( console, 'warn' ).mockImplementation( () => {} );
		mockResponse();

		const refresh = store.actions.refetchData( 123 )( createActionContext( initialLoad ) );
		await jest.advanceTimersByTimeAsync( 200 );

		await expect( refresh ).resolves.toEqual( { status } );
		consoleWarn.mockRestore();
	} );
} );
