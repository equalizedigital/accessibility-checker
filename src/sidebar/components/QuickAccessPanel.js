/**
 * Accessibility Checker Quick Access Panel
 */

import { __, _n } from '@wordpress/i18n';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { PanelRow, Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { useIsPostEditor } from '../hooks/useIsPostEditor';
import { useAccessibilityDataContext } from '../context/AccessibilityDataContext';
import '../sass/components/spinner.scss';
import '../sass/components/quick-access-panel.scss';

const ACCESSIBILITY_CHECKER_SIDEBAR_NAME = 'accessibility-checker/accessibility-checker-sidebar';

/**
 * Quick access panel component
 */
const QuickAccessPanel = () => {
	// Check if we're in the post editor context.
	const isPostEditor = useIsPostEditor();
	const { data, loading, refreshing } = useAccessibilityDataContext();

	// Use the interface store instead of edit-post.
	const { enableComplementaryArea } = useDispatch( 'core/interface' );

	const openAccessibilitySidebar = useCallback( () => {
		if ( isPostEditor && enableComplementaryArea ) {
			enableComplementaryArea( 'core/edit-post', ACCESSIBILITY_CHECKER_SIDEBAR_NAME );
		}
	}, [ isPostEditor, enableComplementaryArea ] );

	// Don't render in FSE/site editor contexts.
	if ( ! isPostEditor ) {
		return null;
	}

	// Calculate error and warning counts from data
	const errorCount = data?.summary?.errors || 0;
	const warningCount = data?.summary?.warnings || 0;

	// Determine which content to display
	let panelContent;
	if ( loading ) {
		panelContent = (
			<p className="edac-quick-access-panel__loading">
				<span className="edac-spinner">
					<span className="spinner is-active" />
				</span>
				{ __( 'Loading accessibility results...', 'accessibility-checker' ) }
			</p>
		);
	} else if ( refreshing ) {
		panelContent = (
			<p className="edac-quick-access-panel__refreshing">
				<span className="edac-spinner">
					<span className="spinner is-active" />
				</span>
				{ __( 'Updating accessibility data...', 'accessibility-checker' ) }
			</p>
		);
	} else if ( errorCount > 0 || warningCount > 0 ) {
		panelContent = (
			<p className="edac-quick-access-panel__summary">
				{ __( 'You have ', 'accessibility-checker' ) }
				<strong>{ errorCount }</strong>
				{ _n(
					' problem to address',
					' problems to address',
					errorCount,
					'accessibility-checker',
				) }
				{ ( warningCount > 0 ) && (
					<>
						{ __( ' and ', 'accessibility-checker' ) }
						<strong>{ warningCount }</strong>
						{ _n(
							' issue that needs review',
							' issues that need review',
							warningCount,
							'accessibility-checker',
						) }
					</>
				) }
				{ __( '.', 'accessibility-checker' ) }
			</p>
		);
	} else {
		panelContent = (
			<p className="edac-quick-access-panel__description">
				{ __( 'Check and fix accessibility issues in your content.', 'accessibility-checker' ) }
			</p>
		);
	}

	return (
		<PluginDocumentSettingPanel
			name="accessibility-checker-quick-access"
			title={ __( 'Accessibility Checker', 'accessibility-checker' ) }
			initialOpen
		>
			<PanelRow className="edac-quick-access-panel__container">
				{ panelContent }
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
