/**
 * Accessibility Checker Quick Access Panel
 */

import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { PanelRow, Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const ACCESSIBILITY_CHECKER_SIDEBAR_NAME = 'accessibility-checker/accessibility-checker-sidebar';

/**
 * Quick access panel component
 */
const AccessibilityCheckerQuickAccess = () => {
	const { openGeneralSidebar } = useDispatch( 'core/edit-post' );

	const openAccessibilitySidebar = useCallback( () => {
		openGeneralSidebar( ACCESSIBILITY_CHECKER_SIDEBAR_NAME );
	}, [ openGeneralSidebar ] );

	return (
		<PluginDocumentSettingPanel
			name="accessibility-checker-quick-access"
			title={ __( 'Accessibility Checker', 'accessibility-checker' ) }
			initialOpen={ true }
		>
			<PanelRow>
				<div style={ { width: '100%' } }>
					<p>{ __( 'Check and fix accessibility issues in your content.', 'accessibility-checker' ) }</p>
					<Button
						variant="secondary"
						onClick={ openAccessibilitySidebar }
						style={ { width: '100%', justifyContent: 'center' } }
					>
						{ __( 'Open Accessibility Panel', 'accessibility-checker' ) }
					</Button>
				</div>
			</PanelRow>
		</PluginDocumentSettingPanel>
	);
};

export default AccessibilityCheckerQuickAccess;

