import { __ } from '@wordpress/i18n';
import { Panel, PanelBody, Notice } from '@wordpress/components';
import { useState, useCallback } from '@wordpress/element';
import { setPendingRescan } from '../index';
import FixCard from './FixCard';

/**
 * FixPanel — displays all available fixes for an issue inside the issue modal.
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.rule     Rule object containing a fixes array of fix slugs.
 * @param {boolean}  props.isOpen   Whether the panel is open.
 * @param {Function} props.onToggle Callback when panel is toggled.
 */
const FixPanel = ( { rule, isOpen, onToggle } ) => {
	const [ errors, setErrors ] = useState( [] );

	const handleError = useCallback( ( errorMessage ) => {
		setErrors( ( prev ) => prev.includes( errorMessage ) ? prev : [ ...prev, errorMessage ] );
	}, [] );

	const dismissError = ( index ) => {
		setErrors( ( prev ) => prev.filter( ( _, i ) => i !== index ) );
	};

	if ( ! rule?.fixes || rule.fixes.length === 0 ) {
		return null;
	}

	return (
		<div className="edac-analysis__fix-panel" data-section="fix">
			<Panel>
				<PanelBody
					title={ __( 'Fix Issue', 'accessibility-checker' ) }
					opened={ isOpen }
					onToggle={ onToggle }
				>
					<div className="edac-analysis__panel-content">
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
								<FixCard
									key={ fixSlug }
									slug={ fixSlug }
									onSave={ () => setPendingRescan( true ) }
									onError={ handleError }
								/>
							) ) }
						</div>
					</div>
				</PanelBody>
			</Panel>
		</div>
	);
};

export default FixPanel;
