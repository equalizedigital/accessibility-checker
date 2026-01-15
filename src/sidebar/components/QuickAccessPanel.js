/**
 * Accessibility Checker Quick Access Panel
 */

import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { PanelRow, Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import '../sass/components/quick-access-panel.scss';

const ACCESSIBILITY_CHECKER_SIDEBAR_NAME = 'accessibility-checker/accessibility-checker-sidebar';

/**
 * Quick access panel component
 */
const QuickAccessPanel = () => {
	const { openGeneralSidebar } = useDispatch( 'core/edit-post' );

	const openAccessibilitySidebar = useCallback( () => {
		openGeneralSidebar( ACCESSIBILITY_CHECKER_SIDEBAR_NAME );
	}, [ openGeneralSidebar ] );

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

