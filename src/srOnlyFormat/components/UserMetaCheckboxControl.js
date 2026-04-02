/**
 * CheckboxControl that reads and writes a user meta value via the REST API.
 */

import { CheckboxControl } from '@wordpress/components';
import { createElement, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { applySrOnlyVisibility, ensureSrOnlyVisibilityObserver, fetchUserMetaValue } from '../utils/visibility';

const UserMetaCheckboxControl = ( { label, metaKey, onChange } ) => {
	const [ checked, setChecked ] = useState( false );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ error, setError ] = useState( '' );

	useEffect( () => {
		let isMounted = true;
		ensureSrOnlyVisibilityObserver();

		fetchUserMetaValue( metaKey )
			.then( ( value ) => {
				if ( ! isMounted ) {
					return;
				}

				setChecked( value );
				onChange( value );
				setError( '' );
			} )
			.catch( () => {
				if ( ! isMounted ) {
					return;
				}

				setError( __( 'Unable to load this preference.', 'accessibility-checker' ) );
			} )
			.finally( () => {
				if ( isMounted ) {
					setIsLoading( false );
				}
			} );

		return () => {
			isMounted = false;
		};
	}, [ metaKey, onChange ] );

	useEffect( () => {
		if ( isLoading ) {
			return;
		}

		applySrOnlyVisibility( checked );
	}, [ checked, isLoading ] );

	const changeChecked = async ( nextChecked ) => {
		const previousChecked = checked;

		setChecked( nextChecked );
		setIsSaving( true );
		setError( '' );
		onChange( nextChecked );

		try {
			await apiFetch( {
				path: '/wp/v2/users/me',
				method: 'POST',
				data: { meta: { [ metaKey ]: nextChecked } },
			} );
		} catch ( saveError ) {
			setChecked( previousChecked );
			onChange( previousChecked );
			setError( __( 'Unable to save this preference.', 'accessibility-checker' ) );
		} finally {
			setIsSaving( false );
		}
	};

	let help;
	if ( error ) {
		help = error;
	} else if ( isLoading ) {
		help = __( 'Loading preference...', 'accessibility-checker' );
	}

	return createElement( CheckboxControl, {
		label,
		checked,
		onChange: changeChecked,
		disabled: isLoading || isSaving,
		help,
	} );
};

UserMetaCheckboxControl.defaultProps = {
	label: '',
	metaKey: '',
	onChange: () => {},
};

export default UserMetaCheckboxControl;
