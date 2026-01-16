/**
 * Custom hook for fetching accessibility data
 */

import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Fetch accessibility data for a post
 *
 * @param {number} postId - The post ID to fetch data for
 * @return {Object} Object containing data, loading state, error, and refetch function
 */
export const useAccessibilityData = ( postId ) => {
	const [ state, setState ] = useState( { data: null, loading: true, error: null } );
	const isMountedRef = useRef( true );
	const [ refetchTrigger, setRefetchTrigger ] = useState( 0 );

	useEffect( () => {
		return () => {
			isMountedRef.current = false;
		};
	}, [] );

	// Refetch function exposed to the provider
	const refetch = useCallback( () => {
		setRefetchTrigger( ( prev ) => prev + 1 );
	}, [] );

	// Fetch data whenever postId or refetchTrigger changes
	useEffect( () => {
		if ( ! postId ) {
			setState( { data: null, loading: false, error: null } );
			return;
		}

		setState( ( prev ) => ( { ...prev, loading: true, error: null } ) );

		apiFetch( {
			path: `/accessibility-checker/v1/sidebar-data/${ postId }`,
			method: 'GET',
		} )
			.then( ( response ) => {
				const newState = {
					data: response.success ? response.data : null,
					error: response.success ? null : ( response.message || __( 'Failed to load accessibility data', 'accessibility-checker' ) ),
					loading: false,
				};

				if ( isMountedRef.current ) {
					setState( newState );
				}
			} )
			.catch( ( err ) => {
				const newState = {
					data: null,
					error: err.message || __( 'Error loading accessibility data', 'accessibility-checker' ),
					loading: false,
				};

				if ( isMountedRef.current ) {
					setState( newState );
				}
			} );
	}, [ postId, refetchTrigger ] );

	return { ...state, refetch };
};

