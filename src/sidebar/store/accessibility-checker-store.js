/**
 * Accessibility Checker Data Store
 *
 * Custom WordPress data store for managing accessibility data state
 * Allows multiple components to subscribe to the same data source
 */

import { createReduxStore, register } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

const STORE_NAME = 'accessibility-checker/data';

// Initial state
const initialState = {
	data: null,
	loading: false,
	error: null,
	refreshing: false,
	postId: null,
};

// Actions
const actions = {
	setLoading( loading ) {
		return {
			type: 'SET_LOADING',
			loading,
		};
	},

	setRefreshing( refreshing ) {
		return {
			type: 'SET_REFRESHING',
			refreshing,
		};
	},

	setData( data ) {
		return {
			type: 'SET_DATA',
			data,
		};
	},

	setError( error ) {
		return {
			type: 'SET_ERROR',
			error,
		};
	},

	setPostId( postId ) {
		return {
			type: 'SET_POST_ID',
			postId,
		};
	},

	updateReadabilityData( readabilityData ) {
		return {
			type: 'UPDATE_READABILITY_DATA',
			readabilityData,
		};
	},

	fetchData( postId ) {
		return async ( { dispatch } ) => {
			if ( ! postId ) {
				dispatch( actions.setData( null ) );
				dispatch( actions.setLoading( false ) );
				dispatch( actions.setError( null ) );
				return;
			}

			dispatch( actions.setPostId( postId ) );
			dispatch( actions.setLoading( true ) );
			dispatch( actions.setError( null ) );

			try {
				const response = await apiFetch( {
					path: `/accessibility-checker/v1/sidebar-data/${ postId }`,
					method: 'GET',
				} );

				if ( response.success ) {
					dispatch( actions.setData( response.data ) );
				} else {
					dispatch( actions.setError( response.message || __( 'Failed to load accessibility data', 'accessibility-checker' ) ) );
				}
			} catch ( err ) {
				dispatch( actions.setError( err.message || __( 'Error loading accessibility data', 'accessibility-checker' ) ) );
			} finally {
				dispatch( actions.setLoading( false ) );
			}
		};
	},

	refetchData( postId ) {
		return async ( { dispatch } ) => {
			dispatch( actions.setRefreshing( true ) );
			await dispatch( actions.fetchData( postId ) );
			dispatch( actions.setRefreshing( false ) );
		};
	},
};

// Reducer
const reducer = ( state = initialState, action ) => {
	switch ( action.type ) {
		case 'SET_LOADING':
			return {
				...state,
				loading: action.loading,
			};
		case 'SET_REFRESHING':
			return {
				...state,
				refreshing: action.refreshing,
			};
		case 'SET_DATA':
			return {
				...state,
				data: action.data,
			};
		case 'SET_ERROR':
			return {
				...state,
				error: action.error,
			};
		case 'SET_POST_ID':
			return {
				...state,
				postId: action.postId,
			};
		case 'UPDATE_READABILITY_DATA':
			return {
				...state,
				data: {
					...state.data,
					readability: action.readabilityData,
				},
			};
		default:
			return state;
	}
};

// Selectors
const selectors = {
	getData( state ) {
		return state.data;
	},

	isLoading( state ) {
		return state.loading;
	},

	isRefreshing( state ) {
		return state.refreshing;
	},

	getError( state ) {
		return state.error;
	},

	getPostId( state ) {
		return state.postId;
	},

	getState( state ) {
		return state;
	},
};

// Create and register the store
const store = createReduxStore( STORE_NAME, {
	reducer,
	actions,
	selectors,
} );

register( store );

export default store;
export { STORE_NAME };

