/**
 * Accessibility Analysis Component
 */

import { __, _n } from '@wordpress/i18n';
import { Panel, PanelBody, PanelRow } from '@wordpress/components';
import { useAccessibilityCheckerData } from '../hooks/useAccessibilityCheckerData';
import '../sass/components/spinner.scss';

/**
 * Accessibility Analysis component
 *
 * @return {JSX.Element} The accessibility analysis panel
 */
const AccessibilityAnalysis = () => {
	const { data, refreshing } = useAccessibilityCheckerData();

	// Calculate error and warning counts from data
	const errorCount = data?.summary?.errors || 0;
	const warningCount = data?.summary?.warnings || 0;

	return (
		<Panel>
			<PanelBody
				title={ __( 'Accessibility Analysis', 'accessibility-checker' ) }
				initialOpen={ true }
				className="edac-panel-body"
			>
				<PanelRow className="edac-data-row">
					{ refreshing && (
						<p className="edac-refreshing-message">
							<span className="edac-spinner">
								<span className="spinner is-active" />
							</span>
							{ __( 'Updating...', 'accessibility-checker' ) }
						</p>
					) }
					<div>
						<div className="edac-data-section">
							<strong className="edac-data-section__label">
								{ __( 'Errors', 'accessibility-checker' ) }
							</strong>
							<span className={ `edac-data-section__count edac-data-section__count--errors ${ errorCount === 0 ? 'edac-data-section__count--empty' : '' }` }>
								{ errorCount }
							</span>
							<span className="edac-data-section__description">
								{ _n(
									'problem to address',
									'problems to address',
									errorCount,
									'accessibility-checker',
								) }
							</span>
						</div>
						<div className="edac-data-section">
							<strong className="edac-data-section__label">
								{ __( 'Warnings', 'accessibility-checker' ) }
							</strong>
							<span className={ `edac-data-section__count edac-data-section__count--warnings ${ warningCount === 0 ? 'edac-data-section__count--empty' : '' }` }>
								{ warningCount }
							</span>
							<span className="edac-data-section__description">
								{ _n(
									'issue that needs review',
									'issues that need review',
									warningCount,
									'accessibility-checker',
								) }
							</span>
						</div>
					</div>
				</PanelRow>
			</PanelBody>
		</Panel>
	);
};

export default AccessibilityAnalysis;

