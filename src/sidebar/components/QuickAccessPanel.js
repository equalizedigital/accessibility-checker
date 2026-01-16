/**
 * Accessibility Checker Quick Access Panel
 */

import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { PanelRow, Button } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useIsPostEditor } from '../hooks/useIsPostEditor';
import '../sass/components/quick-access-panel.scss';

const ACCESSIBILITY_CHECKER_SIDEBAR_NAME = 'accessibility-checker/accessibility-checker-sidebar';

/**
 * Quick access panel component
 */
const QuickAccessPanel = () => {
	// Check if we're in the post editor context.
	const isPostEditor = useIsPostEditor();
	const { data, loading } = useAccessibilityDataContext();

	// Use the interface store instead of edit-post.
	const { enableComplementaryArea } = useDispatch( 'core/interface' );

	const openAccessibilitySidebar = useCallback( () => {
		if ( isPostEditor && enableComplementaryArea ) {
			// The complementary area for plugin sidebars uses this format
			enableComplementaryArea( 'core/edit-post', ACCESSIBILITY_CHECKER_SIDEBAR_NAME );
		}
	}, [ isPostEditor, enableComplementaryArea ] );

	// Don't render in FSE/site editor contexts.
	if ( ! isPostEditor ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="accessibility-checker-quick-access"
			title={ __( 'Accessibility Checker', 'accessibility-checker' ) }
			initialOpen
		>
			<PanelRow className="edac-quick-access-panel__container">
				<p className="edac-quick-access-panel__description">
					{ __( 'Check and fix accessibility issues in your content.', 'accessibility-checker' ) }
				</p>
				<Button
					variant="secondary"
					onClick={ openAccessibilitySidebar }
					className="edac-quick-access-panel__button"
				>
					{ __( 'Open Accessibility Panel', 'accessibility-checker' ) }
				</Button>
			</PanelRow>
		</PluginDocumentSettingPanel>
	);
};

export default QuickAccessPanel;

