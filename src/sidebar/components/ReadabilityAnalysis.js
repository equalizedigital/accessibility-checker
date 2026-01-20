/**
 * Readability Analysis Component
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, PanelRow } from '@wordpress/components';
import { useAccessibilityCheckerData } from '../hooks/useAccessibilityCheckerData';
import '../sass/components/readability-analysis.scss';

/**
 * Readability Analysis component
 *
 * @return {JSX.Element} The readability analysis panel
 */
const ReadabilityAnalysis = () => {
	const { data } = useAccessibilityCheckerData();

	// Extract readability data from the accessibility data.
	const readabilityData = data?.readability || null;

	return (
		<PanelBody
			title={ __( 'Readability Analysis', 'accessibility-checker' ) }
			initialOpen={ true }
			className="edac-panel-body"
		>
			<PanelRow>
				<div className="edac-readability-section">
					<h3 className="edac-readability-section__heading">
						{ __( 'Reading Level', 'accessibility-checker' ) }
					</h3>
					{ JSON.stringify( readabilityData ) }
				</div>
			</PanelRow>
		</PanelBody>
	);
};

export default ReadabilityAnalysis;

