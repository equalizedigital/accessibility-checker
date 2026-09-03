import apiFetch from '@wordpress/api-fetch';
import { createReduxStore } from '@wordpress/data';
import '../../../src/sidebar/store/accessibility-checker-store';

jest.mock( '@wordpress/api-fetch', () => jest.fn(), { virtual: true } );

jest.mock( '@wordpress/data', () => ( {
	createReduxStore: jest.fn( ( name, config ) => ( { name, config } ) ),
	register: jest.fn(),
} ), { virtual: true } );

const { actions } = createReduxStore.mock.calls[ 0 ][ 1 ];

const createActionRunner = ( initialLoad = false ) => {
	const state = {
		data: null,
		error: null,
		initialLoad,
	};

	const select = {
		getData: jest.fn( () => state.data ),
		getError: jest.fn( () => state.error ),
		isInitialLoad: jest.fn( () => state.initialLoad ),
	};

	const dispatch = jest.fn( ( action ) => {
		if ( typeof action === 'function' ) {
			return action( { dispatch, select } );
		}

		switch ( action.type ) {
			case 'SET_DATA':
				state.data = action.data;
				break;
			case 'SET_ERROR':
				state.error = action.error;
				break;
			case 'SET_INITIAL_LOAD':
				state.initialLoad = action.initialLoad;
				break;
			default:
				break;
		}

		return action;
	} );

	return { dispatch, select };
};

const runRefetch = async ( { initialLoad = false, postId = 42 } = {} ) => {
	const runner = createActionRunner( initialLoad );
	const result = actions.refetchData( postId )( runner );
	jest.advanceTimersByTime( 200 );
	return result;
};

describe( 'accessibility checker store refresh results', () => {
	let consoleWarn;

	beforeEach( () => {
		jest.useFakeTimers();
		jest.clearAllMocks();
		consoleWarn = jest.spyOn( console, 'warn' ).mockImplementation( () => {} );
	} );

	afterEach( () => {
		consoleWarn.mockRestore();
		jest.clearAllTimers();
		jest.useRealTimers();
	} );

	test( 'returns true after a successful background refresh', async () => {
		apiFetch.mockResolvedValue( {
			success: true,
			data: { details: {} },
		} );

		await expect( runRefetch() ).resolves.toBe( true );
	} );

	test( 'returns false after an unsuccessful background refresh', async () => {
		apiFetch.mockResolvedValue( {
			success: false,
			message: 'Refresh failed',
		} );

		await expect( runRefetch() ).resolves.toBe( false );
	} );

	test( 'returns false when a background refresh throws', async () => {
		apiFetch.mockRejectedValue( new Error( 'Network error' ) );

		await expect( runRefetch() ).resolves.toBe( false );
	} );

	test( 'returns the initial fetch result when data has not loaded yet', async () => {
		apiFetch.mockResolvedValue( {
			success: true,
			data: { details: {} },
		} );

		await expect( runRefetch( { initialLoad: true } ) ).resolves.toBe( true );
	} );
} );
