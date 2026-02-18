/**
 * Dismiss Panel Component
 *
 * Handles dismissing and restoring issues with comments and reasons.
 */

import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { Panel, PanelBody, Button, Spinner, Notice, RadioControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import RichTextarea from './RichTextarea';
import { toggleIssueDismiss } from '../api';
import { setPendingRefetch } from '../index';
import { getDismissReasonOptions } from '../../sidebar/utils/dismissHelpers';

/**
 * Dismiss Panel Component
 *
 * @param {Object}   props          - Component props.
 * @param {Object}   props.issue    - The issue object.
 * @param {boolean}  props.isOpen   - Whether the panel is open.
 * @param {Function} props.onToggle - Callback when panel is toggled.
 * @param {Function} props.onIgnore - Callback when issue is dismissed/restored.
 */
const DismissPanel = ( { issue, isOpen, onToggle, onIgnore } ) => {
	const [ comment, setComment ] = useState( issue?.ignre_comment ? decodeEntities( issue.ignre_comment ) : '' );
	const [ dismissReason, setDismissReason ] = useState( 'false_positive' );
	const [ isSubmitting, setIsSubmitting ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ successNotice, setSuccessNotice ] = useState( null );
	const [ isIgnored, setIsIgnored ] = useState( issue?.ignre === '1' || issue?.ignre === 1 );

	const handleToggleIgnore = async ( ignore ) => {
		setIsSubmitting( true );
		setError( null );
		setSuccessNotice( null );

		try {
			await toggleIssueDismiss( issue.id, ignore, ignore ? dismissReason : '', ignore ? comment : '' );
			setIsIgnored( ignore );
			setSuccessNotice(
				ignore
					? __( 'Issue dismissed successfully.', 'accessibility-checker' )
					: __( 'Issue reopened successfully.', 'accessibility-checker' ),
			);
			setPendingRefetch( true );

			if ( onIgnore ) {
				onIgnore( issue, ignore );
			}
		} catch ( err ) {
			setError( err.message );
		} finally {
			setIsSubmitting( false );
		}
	};

	return (
		<div className="edac-analysis__dismiss-panel" data-section="dismiss">
			<Panel>
				<PanelBody
					title={
						isIgnored
							? __( 'Issue Dismissed', 'accessibility-checker' )
							: __( 'Dismiss Issue', 'accessibility-checker' )
					}
					opened={ isOpen }
					onToggle={ onToggle }
				>
					<div className="edac-analysis__panel-content">
						{ successNotice && (
							<Notice
								status="success"
								isDismissible={ true }
								onRemove={ () => setSuccessNotice( null ) }
							>
								{ successNotice }
							</Notice>
						) }
						{ error && (
							<Notice status="error" isDismissible={ true } onRemove={ () => setError( null ) }>
								{ error }
							</Notice>
						) }
						{ isIgnored ? (
							<>
								<p className="edac-analysis__dismissed-info">
									{ __(
										'This issue has been dismissed and will not appear in active issues.',
										'accessibility-checker',
									) }
								</p>
								{ issue?.ignre_comment && (
									<div className="edac-analysis__dismissed-comment">
										<strong>{ __( 'Ignore Comment:', 'accessibility-checker' ) }</strong>
										<p
											dangerouslySetInnerHTML={ {
												__html: decodeEntities( issue.ignre_comment ),
											} }
										/>
									</div>
								) }
								<Button
									variant="secondary"
									onClick={ () => handleToggleIgnore( false ) }
									disabled={ isSubmitting }
									className="edac-analysis__dismiss-button"
								>
									{ isSubmitting ? (
										<>
											<Spinner />
											{ __( 'Reopening...', 'accessibility-checker' ) }
										</>
									) : (
										__( 'Reopen Issue', 'accessibility-checker' )
									) }
								</Button>
							</>
						) : (
							<>
								<RadioControl
									label={ __( 'Dismiss issue as:', 'accessibility-checker' ) }
									selected={ dismissReason }
									options={ getDismissReasonOptions() }
									onChange={ setDismissReason }
								/>
								<RichTextarea
									label={ __( 'Comment (optional)', 'accessibility-checker' ) }
									help={ __(
										'Add a note explaining why this issue is being dismissed. Supports bold, italic, and links.',
										'accessibility-checker',
									) }
									value={ comment }
									onChange={ setComment }
									rows={ 3 }
									disabled={ isSubmitting }
								/>
								<Button
									variant="secondary"
									onClick={ () => handleToggleIgnore( true ) }
									disabled={ isSubmitting }
									className="edac-analysis__dismiss-button"
								>
									{ isSubmitting ? (
										<>
											<Spinner />
											{ __( 'Dismissing...', 'accessibility-checker' ) }
										</>
									) : (
										__( 'Dismiss Issue', 'accessibility-checker' )
									) }
								</Button>
							</>
						) }
					</div>
				</PanelBody>
			</Panel>
		</div>
	);
};

export default DismissPanel;
