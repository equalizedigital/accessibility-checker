/**
 * Custom hook for fetching accessibility data
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Fetch accessibility data for a post
 *
 * @param {number} postId - The post ID to fetch data for
 * @return {Object} Object containing data, loading state, and error
 */
export const useAccessibilityData = ( postId ) => {
	const [ data, setData ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );

	useEffect( () => {
		if ( ! postId ) {
			setLoading( false );
			return;
		}

		setLoading( true );
		setError( null );

		apiFetch( {
			path: `/accessibility-checker/v1/sidebar-data/${ postId }`,
			method: 'GET',
		} )
			.then( ( response ) => {
				if ( response.success ) {
					setData( response.data );
				} else {
					setError( response.message || __( 'Failed to load accessibility data', 'accessibility-checker' ) );
				}
			} )
			.catch( ( err ) => {
				setError( err.message || __( 'Error loading accessibility data', 'accessibility-checker' ) );
			} )
			.finally( () => {
				setLoading( false );
			} );
	}, [ postId ] );

	return { data, loading, error };
};

