/**
 * Screen Reader Text Format panel.
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, PanelRow } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import UserMetaCheckboxControl from '../../../srOnlyFormat/components/UserMetaCheckboxControl';
import { STORE_NAME } from '../../store/accessibility-checker-store';
import { renderPanelTitleWithIcon } from '../../utils/panelHelpers';
import '../../sass/components/screen-reader-text-format.scss';

/**
 * Panel for screen reader text format settings.
 *
 * @return {JSX.Element} Sidebar panel.
 */
const ScreenReaderTextFormat = () => {
	const panelId = 'screen-reader-text-format';
	const isPanelExpanded = useSelect(
		( select ) => select( STORE_NAME ).isExpandedPanel( panelId ),
		[ panelId ],
	);
	const { setExpandedPanel } = useDispatch( STORE_NAME );

	const handlePanelToggle = () => {
		setExpandedPanel( panelId, ! isPanelExpanded );
	};

	return (
		<PanelBody
			title={ renderPanelTitleWithIcon(
				'info',
				__( 'Screen Reader Text Format', 'accessibility-checker' ),
			) }
			className="edac-panel-body edac-screen-reader-text-format"
			initialOpen={ false }
			opened={ isPanelExpanded }
			onToggle={ handlePanelToggle }
		>
			<PanelRow className="edac-panel-row">
				<div className="edac-panel-section">
					<p className="edac-panel-section__message">
						{ __( 'Control whether screen reader text stays visible while you edit.', 'accessibility-checker' ) }
					</p>
					<div className="edac-panel-section__subsection">
						<UserMetaCheckboxControl
							label={ __( 'Always show screen reader text?', 'accessibility-checker' ) }
							metaKey="show_sr_text_in_editor"
						/>
					</div>
				</div>
			</PanelRow>
		</PanelBody>
	);
};

export default ScreenReaderTextFormat;
