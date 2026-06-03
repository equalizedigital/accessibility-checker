import { __, sprintf } from '@wordpress/i18n';
import { Button, Spinner, Notice, ToggleControl, TextControl, TextareaControl } from '@wordpress/components';
import { useState, useEffect, memo } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import apiFetch from '@wordpress/api-fetch';

/**
 * FixCard — renders inline settings form for a single fix slug.
 *
 * @param {Object}   props         Component props.
 * @param {string}   props.slug    Fix slug (e.g. 'meta_viewport_scalable').
 * @param {Function} props.onSave  Called after a successful save.
 * @param {Function} props.onError Called with an error message string on failure.
 */
const FixCard = ( { slug, onSave, onError } ) => {
	const [ fixInfo, setFixInfo ] = useState( null );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ formValues, setFormValues ] = useState( {} );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	useEffect( () => {
		let cancelled = false;
		setIsLoading( true );
		setError( null );

		apiFetch( { path: `/edac/v1/fix-fields/${ slug }`, method: 'GET' } )
			.then( ( response ) => {
				if ( cancelled ) {
					return;
				}
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
					const msg = response.message || sprintf( __( 'Failed to load %s fix information.', 'accessibility-checker' ), slug );
					setError( msg );
					onError?.( msg );
				}
			} )
			.catch( ( err ) => {
				if ( cancelled ) {
					return;
				}
				const msg = err?.message || sprintf( __( 'Error loading %s fix information.', 'accessibility-checker' ), slug );
				setError( msg );
				onError?.( msg );
			} )
			.finally( () => {
				if ( ! cancelled ) {
					setIsLoading( false );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ slug ] );

	const handleFieldChange = ( key, value ) => {
		setFormValues( ( prev ) => ( { ...prev, [ key ]: value } ) );
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
				data: { [ fixInfo.fix_slug ]: formValues },
			} );

			setNotice( { status: 'success', message: __( 'Fix settings saved.', 'accessibility-checker' ) } );

			setFixInfo( ( prev ) => {
				if ( ! prev?.fields ) {
					return prev;
				}
				const nextFields = { ...prev.fields };
				Object.keys( formValues ).forEach( ( key ) => {
					if ( nextFields[ key ] ) {
						nextFields[ key ] = { ...nextFields[ key ], value: formValues[ key ] };
					}
				} );
				return { ...prev, fields: nextFields };
			} );

			onSave?.();
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
					<ToggleControl
						label={ <span dangerouslySetInnerHTML={ { __html: field.label } } /> }
						help={ field.description ? <span dangerouslySetInnerHTML={ { __html: field.description } } /> : undefined }
						checked={ !! value }
						onChange={ ( next ) => handleFieldChange( fieldKey, next ) }
					/>
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
					id={ fieldKey }
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
				<Notice status="error" isDismissible={ false }>{ error }</Notice>
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
			<form onSubmit={ ( e ) => {
				e.preventDefault();
				handleSave();
			} }>
				<div className="edac-fix-card__header">
					<h3 className="edac-fix-card__title">{ fixInfo.fix_name }</h3>
				</div>
				{ Object.keys( fixInfo.fields ).length > 0 && (
					<div className="edac-fix-card__fields">
						{ Object.entries( fixInfo.fields ).map( ( [ fieldKey, field ] ) => renderField( fieldKey, field ) ) }
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
						type="submit"
						disabled={ isSaving }
						aria-label={ sprintf( __( 'Save fix for: %s', 'accessibility-checker' ), fixInfo.fix_name ) }
					>
						{ __( 'Save', 'accessibility-checker' ) }
					</Button>
				</div>
			</form>
		</div>
	);
};

export default memo( FixCard );
