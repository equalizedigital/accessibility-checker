/**
 * Accessibility Checker Gutenberg Sidebar
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import QuickAccessPanel from './components/QuickAccessPanel';
import { useAccessibilityData } from './hooks/useAccessibilityData';

const { select } = wp.data;

/**
 * Main sidebar component
 */
function AccessibilityCheckerSidebar() {
	const postId = select( 'core/editor' ).getCurrentPostId();
	const { data, loading, error } = useAccessibilityData( postId );

	return (
		<PluginSidebar
			name="accessibility-checker-sidebar"
			title={ __( 'Accessibility', 'accessibility-checker' ) }
			icon="universal-access"
		>
			<div>
				<p>{ __( 'Sidebar content goes here', 'accessibility-checker' ) }</p>
			</div>
		</PluginSidebar>
	);
}

// Register the sidebar
if ( window.edac_sidebar_app && window.edac_sidebar_app.gutenbergEnabled ) {
	registerPlugin( 'accessibility-checker', {
		render: AccessibilityCheckerSidebar,
	} );

	registerPlugin( 'accessibility-checker-quick-access', {
		render: QuickAccessPanel,
	} );
}

