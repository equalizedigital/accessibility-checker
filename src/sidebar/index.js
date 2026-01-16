/**
 * Accessibility Checker Gutenberg Sidebar
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import QuickAccessPanel from './components/QuickAccessPanel';
import SidebarContent from './components/SidebarContent';
import { AccessibilityDataProvider } from './context/AccessibilityDataContext';

/**
 * Sidebar content wrapper with context
 */
function SidebarWithContext() {
	const postId = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostId(), [] );

	return (
		<AccessibilityDataProvider postId={ postId }>
			<SidebarContent />
		</AccessibilityDataProvider>
	);
}

/**
 * Quick access panel wrapper with context
 */
function QuickAccessPanelWithContext() {
	const postId = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostId(), [] );

	return (
		<AccessibilityDataProvider postId={ postId }>
			<QuickAccessPanel />
		</AccessibilityDataProvider>
	);
}

/**
 * Root component that renders both sidebar and quick access panel
 */
function AccessibilityCheckerRoot() {
	return (
		<>
			<PluginSidebar
				name="accessibility-checker-sidebar"
				title={ __( 'Accessibility', 'accessibility-checker' ) }
				icon="universal-access"
			>
				<SidebarWithContext />
			</PluginSidebar>
			<QuickAccessPanelWithContext />
		</>
	);
}

// Register the combined component
if ( window.edac_sidebar_app && window.edac_sidebar_app.gutenbergEnabled ) {
	registerPlugin( 'accessibility-checker', {
		render: AccessibilityCheckerRoot,
	} );
}

