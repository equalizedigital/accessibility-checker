/**
 * Accessibility Checker Sidebar Content Component
 */

import { __, _n } from '@wordpress/i18n';
import { Panel, PanelBody, PanelRow } from '@wordpress/components';
import { useAccessibilityCheckerData } from '../hooks/useAccessibilityCheckerData';
import ReadabilityAnalysis from './ReadabilityAnalysis';
import '../sass/components/spinner.scss';
import '../sass/components/sidebar-content.scss';

/**
 * Sidebar content component
 *
 * @return {JSX.Element} The sidebar content
 */
const SidebarContent = () => {
	const { loading, error, data, refreshing } = useAccessibilityCheckerData();

	if ( loading ) {
		return (
			<div className="edac-sidebar__loading">
				<p>{ __( 'Loading accessibility data...', 'accessibility-checker' ) }</p>
			</div>
		);
	}

	if ( error ) {
		return (
			<div className="edac-sidebar__error">
				<p>{ error }</p>
			</div>
		);
	}

	// Calculate error and warning counts from data
	const errorCount = data?.summary?.errors || 0;
	const warningCount = data?.summary?.warnings || 0;

	return (
		<div className="edac-sidebar__content">
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
			<ReadabilityAnalysis />
		</div>
	);
};

export default SidebarContent;

