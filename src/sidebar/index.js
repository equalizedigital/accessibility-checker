/**
 * Accessibility Checker Gutenberg Sidebar
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar } from '@wordpress/editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import QuickAccessPanel from './components/QuickAccessPanel';
import SidebarContent from './components/SidebarContent';
import { STORE_NAME } from './store/accessibility-checker-store';

/**
 * Main sidebar component
 */
function AccessibilityCheckerSidebar() {
	const postId = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostId(), [] );
	const { fetchData, refetchData } = useDispatch( STORE_NAME );
	const previousPostIdRef = useRef( null );

	// Fetch data only when postId changes and we haven't fetched it before
	useEffect( () => {
		if ( postId && postId !== previousPostIdRef.current ) {
			previousPostIdRef.current = postId;
			fetchData( postId );
		}
	}, [ postId, fetchData ] );

	// Listen for scan save complete event and refetch data
	useEffect( () => {
		const handleScanSaveComplete = () => {
			if ( postId ) {
				refetchData( postId );
			}
		};

		// Listen on both window and top for the event
		window.addEventListener( 'edac_js_scan_save_complete', handleScanSaveComplete );
		try {
			top.addEventListener( 'edac_js_scan_save_complete', handleScanSaveComplete );
		} catch ( e ) {
			// Ignore if top is not accessible
		}

		return () => {
			window.removeEventListener( 'edac_js_scan_save_complete', handleScanSaveComplete );
			try {
				top.removeEventListener( 'edac_js_scan_save_complete', handleScanSaveComplete );
			} catch ( e ) {
				// Ignore
			}
		};
	}, [ postId, refetchData ] );

	return (
		<PluginSidebar
			name="accessibility-checker-sidebar"
			title={ __( 'Accessibility', 'accessibility-checker' ) }
			icon="universal-access"
		>
			<SidebarContent />
		</PluginSidebar>
	);
}

/**
 * Quick access panel wrapper component
 */
function QuickAccessPanelWrapper() {
	return <QuickAccessPanel />;
}

// Register the combined component
if ( window.edac_sidebar_app && window.edac_sidebar_app.gutenbergEnabled ) {
	registerPlugin( 'accessibility-checker', {
		render: AccessibilityCheckerSidebar,
	} );

	registerPlugin( 'accessibility-checker-quick-access', {
		render: QuickAccessPanelWrapper,
	} );
}

