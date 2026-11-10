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
	const select = {
		getData: jest.fn( () => null ),
		isInitialLoad: jest.fn( () => initialLoad ),
	};
	const dispatch = jest.fn( ( action ) => {
		if ( typeof action === 'function' ) {
			return action( { dispatch, select } );
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
		expect( firstResult ).toBeUndefined();

		await jest.advanceTimersByTimeAsync( 200 );
		await latestRefresh;
		expect( apiFetch ).toHaveBeenCalledTimes( 2 );
	} );
} );
