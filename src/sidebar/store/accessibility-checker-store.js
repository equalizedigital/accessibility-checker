/**
 * Accessibility Checker Data Store
 *
 * Custom WordPress data store for managing accessibility data state
 * Allows multiple components to subscribe to the same data source
 *
 * Features:
 * - Initial load vs background refresh states
 * - Shallow data comparison to prevent unnecessary re-renders
 * - UI state management in memory (resets on page reload)
 * - Debounced refresh to prevent rapid successive updates
 */

import { createReduxStore, register } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

const STORE_NAME = 'accessibility-checker/data';

// Shallow comparison of two objects
const shallowEqual = ( obj1, obj2 ) => {
	if ( obj1 === obj2 ) {
		return true;
	}

	if ( ! obj1 || ! obj2 || typeof obj1 !== 'object' || typeof obj2 !== 'object' ) {
		return false;
	}

	const keys1 = Object.keys( obj1 );
	const keys2 = Object.keys( obj2 );

	if ( keys1.length !== keys2.length ) {
		return false;
	}

	for ( const key of keys1 ) {
		if ( obj1[ key ] !== obj2[ key ] ) {
			return false;
		}
	}

	return true;
};

// Initial state
const initialState = {
	data: null,
	loading: false, // Only true on initial load
	initialLoad: true, // Tracks if we've loaded data at least once
	backgroundRefresh: false, // True during background refresh
	error: null,
	refreshing: false, // Deprecated but kept for backwards compatibility
	postId: null,
	uiState: {
		expandedPanels: {},
		activeTabs: {},
		expandedRules: {},
		lastFocusedIssue: null,
	},
};

// Debounce timer
let debounceTimer = null;

// Actions
const actions = {
	setLoading( loading ) {
		return {
			type: 'SET_LOADING',
			loading,
		};
	},

	setInitialLoad( initialLoad ) {
		return {
			type: 'SET_INITIAL_LOAD',
			initialLoad,
		};
	},

	setBackgroundRefresh( backgroundRefresh ) {
		return {
			type: 'SET_BACKGROUND_REFRESH',
			backgroundRefresh,
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

	setDataIfDifferent( data ) {
		return ( { select, dispatch } ) => {
			const currentData = select.getData();
			// Only update if data is different (shallow comparison)
			if ( currentData && shallowEqual( currentData, data ) ) {
				// Data is the same, no update needed
				return;
			}
			dispatch( actions.setData( data ) );
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

	setExpandedPanel( panelId, isExpanded ) {
		return {
			type: 'SET_EXPANDED_PANEL',
			panelId,
			isExpanded,
		};
	},

	setActiveTab( panelId, tabId ) {
		return {
			type: 'SET_ACTIVE_TAB',
			panelId,
			tabId,
		};
	},

	setExpandedRule( ruleId, isExpanded ) {
		return {
			type: 'SET_EXPANDED_RULE',
			ruleId,
			isExpanded,
		};
	},

	setLastFocusedIssue( issueId ) {
		return {
			type: 'SET_LAST_FOCUSED_ISSUE',
			issueId,
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
					dispatch( actions.setInitialLoad( false ) );
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
		return async ( { dispatch, select } ) => {
			// Clear any existing debounce timer
			if ( debounceTimer ) {
				clearTimeout( debounceTimer );
			}

			// Debounce the refetch by 200ms
			return new Promise( ( resolve ) => {
				debounceTimer = setTimeout( async () => {
					// If this is the initial load, use regular fetch
					if ( select.isInitialLoad() ) {
						await dispatch( actions.fetchData( postId ) );
						resolve();
						return;
					}

					// Background refresh - don't block UI
					dispatch( actions.setBackgroundRefresh( true ) );
					dispatch( actions.setRefreshing( true ) ); // Backwards compatibility

					try {
						const response = await apiFetch( {
							path: `/accessibility-checker/v1/sidebar-data/${ postId }`,
							method: 'GET',
						} );

						if ( response.success ) {
							// Compare and only update if different
							dispatch( actions.setDataIfDifferent( response.data ) );
						} else {
							// Don't show errors during background refresh unless critical
							// eslint-disable-next-line no-console
							console.warn( 'Background refresh failed:', response.message );
						}
					} catch ( err ) {
						// Silent fail for background refresh
						// eslint-disable-next-line no-console
						console.warn( 'Background refresh error:', err.message );
					} finally {
						dispatch( actions.setBackgroundRefresh( false ) );
						dispatch( actions.setRefreshing( false ) );
					}

					resolve();
				}, 200 );
			} );
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
		case 'SET_INITIAL_LOAD':
			return {
				...state,
				initialLoad: action.initialLoad,
			};
		case 'SET_BACKGROUND_REFRESH':
			return {
				...state,
				backgroundRefresh: action.backgroundRefresh,
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
		case 'SET_EXPANDED_PANEL':
			return {
				...state,
				uiState: {
					...state.uiState,
					expandedPanels: {
						...state.uiState.expandedPanels,
						[ action.panelId ]: action.isExpanded,
					},
				},
			};
		case 'SET_ACTIVE_TAB':
			return {
				...state,
				uiState: {
					...state.uiState,
					activeTabs: {
						...state.uiState.activeTabs,
						[ action.panelId ]: action.tabId,
					},
				},
			};
		case 'SET_EXPANDED_RULE':
			return {
				...state,
				uiState: {
					...state.uiState,
					expandedRules: {
						...state.uiState.expandedRules,
						[ action.ruleId ]: action.isExpanded,
					},
				},
			};
		case 'SET_LAST_FOCUSED_ISSUE':
			return {
				...state,
				uiState: {
					...state.uiState,
					lastFocusedIssue: action.issueId,
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

	isInitialLoad( state ) {
		return state.initialLoad;
	},

	isBackgroundRefresh( state ) {
		return state.backgroundRefresh;
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

	getUIState( state ) {
		return state.uiState;
	},

	isExpandedPanel( state, panelId ) {
		const value = state.uiState.expandedPanels[ panelId ];
		// If value is undefined (never set), return default based on panel
		if ( value === undefined ) {
			// Accessibility Status should be open by default
			return panelId === 'accessibility-status';
		}
		return value;
	},

	getActiveTab( state, panelId ) {
		return state.uiState.activeTabs[ panelId ] ?? null;
	},

	isExpandedRule( state, ruleId ) {
		return state.uiState.expandedRules[ ruleId ] ?? false;
	},

	getLastFocusedIssue( state ) {
		return state.uiState.lastFocusedIssue;
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

