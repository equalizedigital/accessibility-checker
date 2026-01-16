/**
 * Accessibility Checker Sidebar Content Component
 */

import { __, _n } from '@wordpress/i18n';
import { Panel, PanelBody, PanelRow } from '@wordpress/components';
import { useAccessibilityDataContext } from '../context/AccessibilityDataContext';

/**
 * Sidebar content component
 *
 * @return {JSX.Element} The sidebar content
 */
const SidebarContent = () => {
	const { loading, error, data, refreshing } = useAccessibilityDataContext();

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
				>
					{ refreshing && (
						<PanelRow>
							<p>
								<span className="spinner is-active" style={ { float: 'none', margin: '0 8px 0 0' } } />
								{ __( 'Updating...', 'accessibility-checker' ) }
							</p>
						</PanelRow>
					) }
					<PanelRow>
						<div style={ { width: '100%' } }>
							<div style={ { marginBottom: '12px' } }>
								<strong style={ { display: 'block', marginBottom: '4px' } }>
									{ __( 'Errors', 'accessibility-checker' ) }
								</strong>
								<span style={ { fontSize: '24px', fontWeight: 'bold', color: errorCount > 0 ? '#d63638' : '#50575e' } }>
									{ errorCount }
								</span>
								<span style={ { marginLeft: '8px', color: '#50575e' } }>
									{ _n(
										'problem to address',
										'problems to address',
										errorCount,
										'accessibility-checker',
									) }
								</span>
							</div>
							<div>
								<strong style={ { display: 'block', marginBottom: '4px' } }>
									{ __( 'Warnings', 'accessibility-checker' ) }
								</strong>
								<span style={ { fontSize: '24px', fontWeight: 'bold', color: warningCount > 0 ? '#f0b849' : '#50575e' } }>
									{ warningCount }
								</span>
								<span style={ { marginLeft: '8px', color: '#50575e' } }>
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
		</div>
	);
};

export default SidebarContent;
