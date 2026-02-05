import { __ } from '@wordpress/i18n';
import { Panel, PanelBody, Button, Spinner, Notice, ToggleControl, TextControl, TextareaControl } from '@wordpress/components';
import { useState, useEffect, useCallback, memo } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
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
	const [ shouldNotifyParent, setShouldNotifyParent ] = useState( false );

	useEffect( () => {
		const fetchFixInfo = async () => {
			setIsLoading( true );
			setError( null );

			try {
				const path = `/edac/v1/fix-fields/${ slug }`;

				const response = await apiFetch( {
					path,
					method: 'GET',
				} );

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

	// Notify parent only after all local state has settled
	useEffect( () => {
		if ( shouldNotifyParent && ! isSaving ) {
			if ( onFixSettingsUpdated ) {
				onFixSettingsUpdated();
			}
			setShouldNotifyParent( false );
		}
	}, [ shouldNotifyParent, isSaving, onFixSettingsUpdated ] );

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
			// Set flag to notify parent only after isSaving is set to false
			setShouldNotifyParent( true );

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
		const decodedLabel = decodeEntities( field.label );

		if ( field.type === 'checkbox' ) {
			return (
				<div key={ fieldKey } className="edac-fix-field edac-fix-field--checkbox">
					<label className="edac-fix-field__label" dangerouslySetInnerHTML={ { __html: decodedLabel } } />
					<ToggleControl
						checked={ !! value }
						onChange={ ( next ) => handleFieldChange( fieldKey, next ) }
					/>
					{ field.description && (
						<p className="edac-fix-field__description" dangerouslySetInnerHTML={ { __html: field.description } } />
					) }
				</div>
			);
		}
		if ( field.type === 'textarea' ) {
			return (
				<div key={ fieldKey } className="edac-fix-field edac-fix-field--textarea">
					<label className="edac-fix-field__label" htmlFor={ fieldKey } dangerouslySetInnerHTML={ { __html: decodedLabel } } />
					<TextareaControl
						value={ value ?? '' }
						onChange={ ( next ) => handleFieldChange( fieldKey, next ) }
					/>
					{ field.description && (
						<p className="edac-fix-field__description" dangerouslySetInnerHTML={ { __html: field.description } } />
					) }
				</div>
			);
		}
		return (
			<div key={ fieldKey } className="edac-fix-field edac-fix-field--text">
				<label className="edac-fix-field__label" htmlFor={ fieldKey } dangerouslySetInnerHTML={ { __html: decodedLabel } } />
				<TextControl
					value={ value ?? '' }
					onChange={ ( next ) => handleFieldChange( fieldKey, next ) }
				/>
				{ field.description && (
					<p className="edac-fix-field__description" dangerouslySetInnerHTML={ { __html: field.description } } />
				) }
			</div>
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
						renderField( fieldKey, field )
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
					{ __( 'Save', 'accessibility-checker' ) }
				</Button>
			</div>
		</div>
	);
};

const MemoizedFixCard = memo( FixCard );

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

	const memoizedOnFixSettingsUpdated = useCallback( () => {
		if ( onFixSettingsUpdated ) {
			onFixSettingsUpdated();
		}
	}, [ onFixSettingsUpdated ] );

	const dismissError = ( index ) => {
		setErrors( ( prev ) => prev.filter( ( _, i ) => i !== index ) );
	};

	if ( ! rule?.fixes || rule.fixes.length === 0 ) {
		return null;
	}

	return (
		<Panel className="edac-analysis__fix-panel" data-section="fix">
			<PanelBody
				title={ __( 'Fix Issue', 'accessibility-checker' ) }
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
					{ __( 'These settings enable global fixes across your entire site.', 'accessibility-checker' ) }
				</p>

				<div className="edac-fix-panel__cards">
					{ rule.fixes.map( ( fixSlug ) => (
						<MemoizedFixCard
							key={ fixSlug }
							slug={ fixSlug }
							onError={ handleError }
							onFixSettingsUpdated={ memoizedOnFixSettingsUpdated }
						/>
					) ) }
				</div>
			</PanelBody>
		</Panel>
	);
};

export default FixPanel;
