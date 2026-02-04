/**
 * Fix Panel Component
 *
 * Displays fix information and provides link to settings to enable the fix.
 */

import { __ } from '@wordpress/i18n';
import { Panel, PanelBody, Button, Spinner, Notice } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { external } from '@wordpress/icons';
import apiFetch from '@wordpress/api-fetch';

/**
 * FixPanel Component
 *
 * @param {Object} props      - Component props.
 * @param {Object} props.rule - Rule object containing fixes array.
 */
const FixPanel = ( { rule } ) => {
	const [ fixInfo, setFixInfo ] = useState( null );
	const [ isLoadingInfo, setIsLoadingInfo ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	// Get the first fix slug
	const fixSlug = rule?.fixes?.length > 0 ? rule.fixes[ 0 ] : null;

	// Fetch fix information when panel opens
	useEffect( () => {
		if ( ! fixSlug ) {
			return;
		}

		const fetchFixInfo = async () => {
			setIsLoadingInfo( true );
			setNotice( null );

			try {
				const apiUrl = window.edac_sidebar_app?.edacApiUrl || '/wp-json/accessibility-checker/v1';
				const response = await apiFetch( {
					path: `${ apiUrl }/fix-fields/${ fixSlug }`,
					method: 'GET',
				} );

				if ( response.success ) {
					setFixInfo( response );
				} else {
					setNotice( {
						status: 'error',
						message: __( 'Failed to load fix information.', 'accessibility-checker' ),
					} );
				}
			} catch ( error ) {
				setNotice( {
					status: 'error',
					message: error.message || __( 'Error loading fix information.', 'accessibility-checker' ),
				} );
			} finally {
				setIsLoadingInfo( false );
			}
		};

		fetchFixInfo();
	}, [ fixSlug ] );

	if ( ! rule?.fixes || rule.fixes.length === 0 ) {
		return null;
	}

	// Get the settings page URL
	const settingsUrl = window.edac_sidebar_app?.settingsUrl || '/wp-admin/admin.php?page=accessibility_checker_settings';

	return (
		<Panel className="edac-analysis__fix-panel" data-section="fix">
			<PanelBody
				title={ __( 'Available Fix', 'accessibility-checker' ) }
				initialOpen={ false }
			>
				{ notice && (
					<Notice
						status={ notice.status }
						isDismissible={ true }
						onRemove={ () => setNotice( null ) }
					>
						{ notice.message }
					</Notice>
				) }

				{ isLoadingInfo && (
					<div style={ { textAlign: 'center', padding: '20px' } }>
						<Spinner />
						<p>{ __( 'Loading fix information...', 'accessibility-checker' ) }</p>
					</div>
				) }

				{ ! isLoadingInfo && fixInfo && (
					<div className="edac-fix-info">
						<h4>{ fixInfo.fix_name }</h4>

						<p>
							{ __( 'This issue can be resolved by enabling the automated fix in Accessibility Checker settings.', 'accessibility-checker' ) }
						</p>

						{ Object.keys( fixInfo.fields ).length > 0 && (
							<>
								<p>
									<strong>{ __( 'Fix Options:', 'accessibility-checker' ) }</strong>
								</p>
								<ul style={ { marginLeft: '20px', listStyle: 'disc' } }>
									{ Object.values( fixInfo.fields ).map( ( field, index ) => (
										<li key={ index }>
											<strong>{ field.label }</strong>
											{ field.description && (
												<>
													{ ': ' }
													<span dangerouslySetInnerHTML={ { __html: field.description } } />
												</>
											) }
											{ field.value && (
												<span style={ { color: '#46b450', marginLeft: '8px' } }>
													({ __( 'Currently Enabled', 'accessibility-checker' ) })
												</span>
											) }
										</li>
									) ) }
								</ul>
							</>
						) }

						<div style={ { marginTop: '20px' } }>
							<Button
								variant="primary"
								icon={ external }
								href={ settingsUrl }
								target="_blank"
								rel="noopener noreferrer"
							>
								{ __( 'Open Fix Settings', 'accessibility-checker' ) }
							</Button>
						</div>

						{ rule.fixes.length > 1 && (
							<p style={ { marginTop: '16px', fontSize: '12px', color: '#666' } }>
								<em>
									{ __( 'Note: This rule has multiple fixes available. All can be configured in settings.', 'accessibility-checker' ) }
								</em>
							</p>
						) }
					</div>
				) }

				{ ! isLoadingInfo && ! fixInfo && ! notice && (
					<p>{ __( 'Fix information not available.', 'accessibility-checker' ) }</p>
				) }
			</PanelBody>
		</Panel>
	);
};

export default FixPanel;
