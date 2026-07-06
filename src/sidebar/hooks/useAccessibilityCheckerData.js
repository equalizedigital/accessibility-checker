/**
 * Hook to use accessibility checker data store
 */

import { useSelect, useDispatch } from '@wordpress/data';
import { STORE_NAME } from '../store/accessibility-checker-store';

/**
 * Hook to subscribe to accessibility data from the store
 *
 * Note: Data fetching is handled by the root component (index.js)
 * This hook only provides access to the store data
 *
 * @return {Object} Object containing data, loading, error, refreshing states and refetch function
 */
export const useAccessibilityCheckerData = () => {
	const postId = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostId(), [] );

	const { data, loading, error, refreshing, initialLoad, backgroundRefresh } = useSelect(
		( select ) => ( {
			data: select( STORE_NAME ).getData(),
			loading: select( STORE_NAME ).isLoading(),
			error: select( STORE_NAME ).getError(),
			refreshing: select( STORE_NAME ).isRefreshing(),
			initialLoad: select( STORE_NAME ).isInitialLoad(),
			backgroundRefresh: select( STORE_NAME ).isBackgroundRefresh(),
		} ),
		[],
	);

	const {
		refetchData,
		updateReadabilityData,
		setExpandedPanel,
		setActiveTab,
		setExpandedRule,
		setLastFocusedIssue,
	} = useDispatch( STORE_NAME );

	// Wrap refetchData to automatically include postId
	const refetch = () => {
		if ( postId ) {
			refetchData( postId );
		}
	};

	return {
		data,
		loading,
		error,
		refreshing,
		initialLoad,
		backgroundRefresh,
		refetch,
		updateReadabilityData,
		setExpandedPanel,
		setActiveTab,
		setExpandedRule,
		setLastFocusedIssue,
	};
};

