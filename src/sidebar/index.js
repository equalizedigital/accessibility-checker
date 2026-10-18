/**
 * Accessibility Checker Gutenberg Sidebar
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar } from '@wordpress/editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner, Button } from '@wordpress/components';
import { starFilled, starEmpty } from '@wordpress/icons';
import QuickAccessPanel from './components/QuickAccessPanel';
import SidebarContent from './components/SidebarContent';
import SidebarTitleMenu from './components/SidebarTitleMenu';
import { STORE_NAME } from './store/accessibility-checker-store';
import AccessibilityCheckerIcon from '../../assets/images/accessibility-checker-icon.svg';

const SIDEBAR_NAME = 'accessibility-checker-sidebar';
const SIDEBAR_IDENTIFIER = `accessibility-checker/${ SIDEBAR_NAME }`;

/**
 * Pin/unpin toggle. A custom PluginSidebar `header` replaces core's default
 * header contents, which is where core renders this control, so it is
 * re-created here using the same interface store.
 */
function PinToggle() {
	const isPinned = useSelect( ( select ) => select( 'core/interface' ).isItemPinned( 'core', SIDEBAR_IDENTIFIER ), [] );
	const { pinItem, unpinItem } = useDispatch( 'core/interface' );

	return (
		<Button
			className="edac-sidebar__pin-toggle"
			icon={ isPinned ? starFilled : starEmpty }
			label={ isPinned
				? __( 'Unpin from toolbar', 'accessibility-checker' )
				: __( 'Pin to toolbar', 'accessibility-checker' ) }
			onClick={ () => ( isPinned ? unpinItem( 'core', SIDEBAR_IDENTIFIER ) : pinItem( 'core', SIDEBAR_IDENTIFIER ) ) }
			isPressed={ isPinned }
			size="compact"
		/>
	);
}

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

	const sidebarTitle = __( 'Accessibility Checker', 'accessibility-checker' );

	return (
		<PluginSidebar
			name={ SIDEBAR_NAME }
			title={ sidebarTitle }
			header={
				// A custom header keeps the actions menu and spinner outside
				// the header's <h2>, so screen readers don't announce the menu
				// button as a heading. The plain-text `title` is still used by
				// the small-screen header and the Plugins menu, and is the
				// rendered fallback if `header` is ever unsupported.
				<div className="edac-sidebar__header">
					<h2 className="edac-sidebar__header-title">
						{ sidebarTitle }
					</h2>
					{ backgroundRefresh && <Spinner className="edac-sidebar__title-spinner" /> }
					<SidebarTitleMenu
						postId={ postId }
						refetchData={ refetchData }
					/>
					<PinToggle />
				</div>
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
