/**
 * Accessibility Checker Gutenberg Sidebar
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar } from '@wordpress/editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import QuickAccessPanel from './components/QuickAccessPanel';
import SidebarContent from './components/SidebarContent';
import SidebarTitleMenu from './components/SidebarTitleMenu';
import { STORE_NAME } from './store/accessibility-checker-store';
import AccessibilityCheckerIcon from '../../assets/images/accessibility-checker-icon.svg';

/**
 * Main sidebar component
 */
function AccessibilityCheckerSidebar() {
	const postId = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostId(), [] );
	const backgroundRefresh = useSelect( ( select ) => select( STORE_NAME ).isBackgroundRefresh(), [] );
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

	// Listen for metabox readability updates and refetch data
	useEffect( () => {
		const handleMetaboxReadabilityUpdate = () => {
			if ( postId ) {
				refetchData( postId );
			}
		};

		window.addEventListener( 'edac-metabox-readability-updated', handleMetaboxReadabilityUpdate );

		return () => {
			window.removeEventListener( 'edac-metabox-readability-updated', handleMetaboxReadabilityUpdate );
		};
	}, [ postId, refetchData ] );

	// Listen for clear issues from classic metabox and refetch data
	useEffect( () => {
		const handleClearedIssues = () => {
			if ( postId ) {
				refetchData( postId );
			}
		};

		document.addEventListener( 'edac-cleared-issues', handleClearedIssues );

		return () => {
			document.removeEventListener( 'edac-cleared-issues', handleClearedIssues );
		};
	}, [ postId, refetchData ] );

	return (
		<PluginSidebar
			name="accessibility-checker-sidebar"
			title={
				<span className="edac-sidebar__title">
					{ __( 'Accessibility Checker', 'accessibility-checker' ) }
					{ backgroundRefresh && <Spinner className="edac-sidebar__title-spinner" /> }
					<SidebarTitleMenu
						postId={ postId }
						refetchData={ refetchData }
					/>
				</span>
			}
			icon={ <AccessibilityCheckerIcon style={ { width: '24px', height: '24px' } } /> }
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
