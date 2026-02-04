import { __ } from '@wordpress/i18n';
import { Panel, PanelBody, Button, Spinner, Notice, ToggleControl, TextControl, TextareaControl } from '@wordpress/components';
import { useState, useEffect, useCallback } from '@wordpress/element';
import { external } from '@wordpress/icons';
import apiFetch from '@wordpress/api-fetch';

/**
 * Single Fix Card Component
 *
 * @param {Object}   props                      - Component props.
 * @param {string}   props.slug                 - Fix slug.
 * @param {Function} props.onError              - Error callback.
 * @param {Function} props.onFixSettingsUpdated - Callback when fix settings are updated.
 */
const FixCard = ( { slug, onError, onFixSettingsUpdated } ) => {
	const [ fixInfo, setFixInfo ] = useState( null );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ formValues, setFormValues ] = useState( {} );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	useEffect( () => {
		const fetchFixInfo = async () => {
			setIsLoading( true );
			setError( null );

			try {
				const path = `/edac/v1/fix-fields/${ slug }`;
				// eslint-disable-next-line no-console
				console.log( 'Fetching fix info from:', path );

				const response = await apiFetch( {
					path,
					method: 'GET',
				} );

				// eslint-disable-next-line no-console
				console.log( 'Fix response:', response );

				if ( response.success ) {
					setFixInfo( response );
					const nextValues = Object.keys( response.fields || {} ).reduce( ( acc, key ) => {
						const field = response.fields[ key ] || {};
						const rawValue = field.value ?? '';
						acc[ key ] = field.type === 'checkbox'
							? rawValue === true || rawValue === 1 || rawValue === '1'
							: rawValue;
						return acc;
					}, {} );
					setFormValues( nextValues );
				} else {
					const errorMsg = response.message || `Failed to load ${ slug } fix information.`;
					setError( errorMsg );
					if ( onError ) {
						onError( errorMsg );
					}
				}
			} catch ( err ) {
				// eslint-disable-next-line no-console
				console.error( 'Fix fetch error:', err );
				const errorMsg = err.message || `Error loading ${ slug } fix information.`;
				setError( errorMsg );
				if ( onError ) {
					onError( errorMsg );
				}
			} finally {
				setIsLoading( false );
			}
		};

		fetchFixInfo();
	}, [ slug ] );

	const handleFieldChange = ( key, value ) => {
		setFormValues( ( prev ) => ( {
			...prev,
			[ key ]: value,
		} ) );
	};

	const handleSave = async () => {
		if ( ! fixInfo?.fix_slug ) {
			return;
		}
		setIsSaving( true );
		setNotice( null );
		try {
			await apiFetch( {
				path: '/edac/v1/fixes/update',
				method: 'POST',
				data: {
					[ fixInfo.fix_slug ]: formValues,
				},
			} );
			setNotice( {
				status: 'success',
				message: __( 'Fix settings saved.', 'accessibility-checker' ),
			} );
			if ( onFixSettingsUpdated ) {
				onFixSettingsUpdated();
			}
			setFixInfo( ( prev ) => {
				if ( ! prev?.fields ) {
					return prev;
				}
				const nextFields = { ...prev.fields };
				Object.keys( formValues ).forEach( ( key ) => {
					if ( nextFields[ key ] ) {
						nextFields[ key ] = {
							...nextFields[ key ],
							value: formValues[ key ],
						};
					}
				} );
				return { ...prev, fields: nextFields };
			} );
		} catch ( err ) {
			setNotice( {
				status: 'error',
				message: err?.message || __( 'Failed to save fix settings.', 'accessibility-checker' ),
			} );
		} finally {
			setIsSaving( false );
		}
	};

	const renderField = ( fieldKey, field ) => {
		const value = formValues[ fieldKey ];
		if ( field.type === 'checkbox' ) {
			return (
				<ToggleControl
					label={ field.label }
					checked={ !! value }
					onChange={ ( next ) => handleFieldChange( fieldKey, next ) }
				/>
			);
		}
		if ( field.type === 'textarea' ) {
			return (
				<TextareaControl
					label={ field.label }
					value={ value ?? '' }
					onChange={ ( next ) => handleFieldChange( fieldKey, next ) }
				/>
			);
		}
		return (
			<TextControl
				label={ field.label }
				value={ value ?? '' }
				onChange={ ( next ) => handleFieldChange( fieldKey, next ) }
			/>
		);
	};

	if ( error ) {
		return (
			<div className="edac-fix-card edac-fix-card--error">
				<Notice
					status="error"
					isDismissible={ false }
				>
					{ error }
				</Notice>
			</div>
		);
	}

	if ( isLoading ) {
		return (
			<div className="edac-fix-card edac-fix-card--loading">
				<Spinner />
				<p>{ __( 'Loading fix information...', 'accessibility-checker' ) }</p>
			</div>
		);
	}

	if ( ! fixInfo ) {
		return null;
	}

	const settingsUrl = '/wp-admin/admin.php?page=accessibility_checker_settings';
	const statusClass = fixInfo.enabled ? 'edac-fix-card--enabled' : 'edac-fix-card--disabled';

	return (
		<div className={ `edac-fix-card ${ statusClass }` }>
			<div className="edac-fix-card__header">
				<h4 className="edac-fix-card__title">{ fixInfo.fix_name }</h4>
				<span className={ `edac-fix-card__status edac-fix-card__status--${ fixInfo.enabled ? 'enabled' : 'disabled' }` }>
					{ fixInfo.enabled ? __( 'Enabled', 'accessibility-checker' ) : __( 'Disabled', 'accessibility-checker' ) }
				</span>
			</div>

			{ Object.keys( fixInfo.fields ).length > 0 && (
				<div className="edac-fix-card__fields">
					{ Object.entries( fixInfo.fields ).map( ( [ fieldKey, field ] ) => (
						<div key={ fieldKey } className="edac-fix-card__field-item">
							{ renderField( fieldKey, field ) }
							{ field.description && (
								<p className="edac-fix-card__field-description" dangerouslySetInnerHTML={ { __html: field.description } } />
							) }
						</div>
					) ) }
				</div>
			) }

			{ notice && (
				<Notice
					status={ notice.status }
					isDismissible={ true }
					onRemove={ () => setNotice( null ) }
				>
					{ notice.message }
				</Notice>
			) }

			<div className="edac-fix-card__actions">
				<Button
					variant="primary"
					onClick={ handleSave }
					disabled={ isSaving }
				>
					{ isSaving ? __( 'Saving…', 'accessibility-checker' ) : __( 'Save Fix Settings', 'accessibility-checker' ) }
				</Button>
				<Button
					variant="secondary"
					icon={ external }
					href={ `${ settingsUrl }#${ fixInfo.fix_slug }` }
					target="_blank"
					rel="noopener noreferrer"
				>
					{ __( 'Open Fix Settings', 'accessibility-checker' ) }
				</Button>
			</div>
		</div>
	);
};

/**
 * FixPanel Component
 *
 * Displays all available fixes for an issue.
 *
 * @param {Object}   props                      - Component props.
 * @param {Object}   props.rule                 - Rule object containing fixes array.
 * @param {Function} props.onFixSettingsUpdated - Callback when any fix settings are updated, to allow parent to refresh data if needed.
 */
const FixPanel = ( { rule, onFixSettingsUpdated } ) => {
	const [ errors, setErrors ] = useState( [] );

	const handleError = useCallback( ( errorMessage ) => {
		setErrors( ( prev ) => {
			// Don't add duplicate errors
			if ( prev.includes( errorMessage ) ) {
				return prev;
			}
			return [ ...prev, errorMessage ];
		} );
	}, [] );

	const dismissError = ( index ) => {
		setErrors( ( prev ) => prev.filter( ( _, i ) => i !== index ) );
	};

	if ( ! rule?.fixes || rule.fixes.length === 0 ) {
		return null;
	}

	return (
		<Panel className="edac-analysis__fix-panel" data-section="fix">
			<PanelBody
				title={ __( 'Available Fixes', 'accessibility-checker' ) }
				initialOpen={ false }
			>
				{ errors.map( ( error, index ) => (
					<Notice
						key={ index }
						status="error"
						isDismissible={ true }
						onRemove={ () => dismissError( index ) }
					>
						{ error }
					</Notice>
				) ) }

				<p className="edac-fix-panel__intro">
					{ __( 'This issue can be resolved by enabling one or more of the fixes below in Accessibility Checker settings.', 'accessibility-checker' ) }
				</p>

				<div className="edac-fix-panel__cards">
					{ rule.fixes.map( ( fixSlug, index ) => (
						<FixCard
							key={ index }
							slug={ fixSlug }
							onError={ handleError }
							onFixSettingsUpdated={ onFixSettingsUpdated }
						/>
					) ) }
				</div>
			</PanelBody>
		</Panel>
	);
};

export default FixPanel;
