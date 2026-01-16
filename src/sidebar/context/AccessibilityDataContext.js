/**
 * Context for managing accessibility data state
 */

import { createContext, useContext, useState, useEffect } from '@wordpress/element';
import { useAccessibilityData } from '../hooks/useAccessibilityData';

const AccessibilityDataContext = createContext( null );

/**
 * Provider component for accessibility data
 *
 * @param {Object}      props          - Component props
 * @param {number}      props.postId   - The current post ID
 * @param {JSX.Element} props.children - Child components
 * @return {JSX.Element} Provider component
 */
export const AccessibilityDataProvider = ( { postId, children } ) => {
	const { data, loading, error, refetch } = useAccessibilityData( postId );
	const [ version, setVersion ] = useState( 0 );
	const [ refreshing, setRefreshing ] = useState( false );

	// Refresh when scan results have been saved (after post save completes).
	// The editor emits a custom event: 'edac_js_scan_save_complete'.
	useEffect( () => {
		if ( ! postId ) {
			return;
		}

		const handleScanSaveComplete = () => {
			setRefreshing( true );
			refetch();
			setVersion( ( v ) => v + 1 );
		};

		// Listen in both window and top to be resilient to different dispatch contexts.
		window.addEventListener( 'edac_js_scan_save_complete', handleScanSaveComplete );
		try {
			// Some code paths dispatch on top.
			top.addEventListener( 'edac_js_scan_save_complete', handleScanSaveComplete );
		} catch ( e ) {
			// Ignore if top is not accessible.
		}

		return () => {
			window.removeEventListener( 'edac_js_scan_save_complete', handleScanSaveComplete );
			try {
				top.removeEventListener( 'edac_js_scan_save_complete', handleScanSaveComplete );
			} catch ( e ) { /* noop */ }
		};
	}, [ postId, refetch ] );

	// When refetch completes (loading becomes false), clear refreshing flag.
	useEffect( () => {
		if ( ! loading && refreshing ) {
			setRefreshing( false );
		}
	}, [ loading, refreshing ] );

	const value = {
		data,
		loading,
		error,
		refetch,
		version,
		postId,
		refreshing,
	};

	return (
		<AccessibilityDataContext.Provider value={ value }>
			{ children }
		</AccessibilityDataContext.Provider>
	);
};

/**
 * Hook to access accessibility data from context
 *
 * @return {Object} Accessibility data context value
 */
export const useAccessibilityDataContext = () => {
	const context = useContext( AccessibilityDataContext );
	if ( ! context ) {
		throw new Error( 'useAccessibilityDataContext must be used within AccessibilityDataProvider' );
	}
	return context;
};
