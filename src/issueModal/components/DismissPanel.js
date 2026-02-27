/**
 * Dismiss Panel Component
 *
 * Handles dismissing and restoring issues with comments and reasons.
 */

import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { Panel, PanelBody, Button, Spinner, Notice, RadioControl, Dropdown } from '@wordpress/components';
import { chevronDown } from '@wordpress/icons';
import { useState } from '@wordpress/element';
import RichTextarea from './RichTextarea';
import { toggleIssueDismiss } from '../api';
import { setPendingRefetch } from '../index';
import { getDismissReasonOptions } from '../../sidebar/utils/dismissHelpers';

/**
 * Dismiss Panel Component
 *
 * @param {Object}   props              - Component props.
 * @param {Object}   props.issue        - The issue object.
 * @param {boolean}  props.isOpen       - Whether the panel is open.
 * @param {Function} props.onToggle     - Callback when panel is toggled.
 * @param {Function} props.onIgnore     - Callback when issue is dismissed/restored.
 * @param {Function} props.onCloseModal - Callback to close the parent modal.
 */
const DismissPanel = ( { issue, isOpen, onToggle, onIgnore, onCloseModal } ) => {
	const [ comment, setComment ] = useState( issue?.ignre_comment ? decodeEntities( issue.ignre_comment ) : '' );
	const [ dismissReason, setDismissReason ] = useState( issue?.ignre_reason || 'false_positive' );
	const [ isSubmitting, setIsSubmitting ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ successNotice, setSuccessNotice ] = useState( null );
	const [ isIgnored, setIsIgnored ] = useState( issue?.ignre === '1' || issue?.ignre === 1 );
	const isPro = window.edac_editor_app?.pro === '1';
	const dismissReasonOptions = getDismissReasonOptions();
	const dismissReasonLabel = dismissReasonOptions.find( ( option ) => option.value === issue?.ignre_reason )?.label;

	const handleToggleIgnore = async ( ignore, isGlobal = false ) => {
		setIsSubmitting( true );
		setError( null );
		setSuccessNotice( null );

		try {
			const response = await toggleIssueDismiss( issue.id, ignore, ignore ? dismissReason : '', ignore ? comment : '', ignore && isGlobal );
			setIsIgnored( ignore );
			setSuccessNotice(
				ignore
					? __( 'Issue dismissed successfully.', 'accessibility-checker' )
					: __( 'Issue reopened successfully.', 'accessibility-checker' ),
			);
			// Keep local issue fields in sync so UI reflects reason/comment immediately.
			// Use the same keys as both the details processor and dismiss response.
			if ( ignore && response && response.success ) {
				issue.ignre = '1';
				issue.user = response.user || response.ignre_user_name || '';
				issue.ignre_user_name = response.ignre_user_name || response.user || '';
				issue.ignre_date = response.ignre_date || '';
				issue.ignre_reason = response.ignre_reason || response.reason || dismissReason;
				issue.ignre_comment = response.ignre_comment || response.comment || comment;
				issue.ignre_global = response.ignre_global ?? ( isGlobal ? 1 : 0 );
			} else if ( ! ignore ) {
				issue.ignre = '0';
				issue.user = '';
				issue.ignre_user_name = '';
				issue.ignre_date = '';
				// Update the reason and comment states from the issue before clearing them.
				// This preserves the values in the form so users can easily re-dismiss.
				setDismissReason( issue.ignre_reason || dismissReason );
				setComment( issue.ignre_comment ? decodeEntities( issue.ignre_comment ) : comment );
				issue.ignre_reason = '';
				issue.ignre_comment = '';
			}
			setPendingRefetch( true );

			if ( onIgnore ) {
				onIgnore( issue, ignore );
			}

			return true;
		} catch ( err ) {
			setError( err.message );
			return false;
		} finally {
			setIsSubmitting( false );
		}
	};

	const dismissButtonLabel = isSubmitting ? (
		<>
			<Spinner />
			{ __( 'Dismissing...', 'accessibility-checker' ) }
		</>
	) : (
		__( 'Dismiss Issue', 'accessibility-checker' )
	);

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
										<strong>{ __( 'Comment:', 'accessibility-checker' ) }</strong>
										<p
											dangerouslySetInnerHTML={ {
												__html: decodeEntities( issue.ignre_comment ),
											} }
										/>
									</div>
								) }
								{ ( issue?.user || issue?.ignre_user_name || issue?.ignre_date || issue?.ignre_reason || issue?.ignre_global ) && (
									<div className="edac-analysis__dismissed-meta">
										{ issue?.ignre_reason && dismissReasonLabel && (
											<p>
												<strong>{ __( 'Dismissed as:', 'accessibility-checker' ) }</strong>{ ' ' }
												{ dismissReasonLabel }
											</p>
										) }
										{ ( issue?.ignre_global === 1 || issue?.ignre_global === '1' ) && (
											<p>
												<strong>{ __( 'Globally dismissed:', 'accessibility-checker' ) }</strong>{ ' ' }
												{ __( 'Yes — dismissed across all pages', 'accessibility-checker' ) }
											</p>
										) }
										{ ( issue?.ignre_user_name || issue?.user ) && (
											<p>
												<strong>{ __( 'Dismissed by:', 'accessibility-checker' ) }</strong>{ ' ' }
												{ decodeEntities( issue.ignre_user_name || issue.user ) }
											</p>
										) }
										{ issue?.ignre_date && (
											<p>
												<strong>{ __( 'Dismissed on:', 'accessibility-checker' ) }</strong>{ ' ' }
												{ decodeEntities( issue.ignre_date ) }
											</p>
										) }
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
							<form
								onSubmit={ ( e ) => {
									e.preventDefault();
									handleToggleIgnore( true );
								} }
							>
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
								<div className="edac-analysis__dismiss-split-button">
									<Button
										variant="primary"
										type="submit"
										disabled={ isSubmitting }
										className="edac-analysis__dismiss-button"
									>
										{ dismissButtonLabel }
									</Button>
									<Dropdown
										renderToggle={ ( { isOpen: isDropdownOpen, onToggle: onDropdownToggle } ) => (
											<Button
												variant="primary"
												type="button"
												icon={ chevronDown }
												onClick={ onDropdownToggle }
												aria-expanded={ isDropdownOpen }
												aria-label={ __( 'More dismiss options', 'accessibility-checker' ) }
												disabled={ isSubmitting }
												className="edac-analysis__dismiss-dropdown-toggle"
											/>
										) }
										renderContent={ ( { onClose } ) => (
											<div className="edac-analysis__dismiss-dropdown-content">
												{ isPro && (
													<Button
														variant="tertiary"
														type="button"
														onClick={ () => {
															onClose();
															handleToggleIgnore( true, true );
														} }
													>
														{ __( 'Dismiss Globally', 'accessibility-checker' ) }
													</Button>
												) }
												<Button
													variant="tertiary"
													type="button"
													onClick={ () => {
														onClose();
														handleToggleIgnore( true, false ).then( () => {
															// close the entire modal after dismissing.
															if ( onCloseModal ) {
																onCloseModal();
															}
														} );
													} }>
													{ __( 'Dismiss & Close Modal', 'accessibility-checker' ) }
												</Button>
											</div>
										) }
									/>
								</div>
							</form>
						) }
					</div>
				</PanelBody>
			</Panel>
		</div>
	);
};

export default DismissPanel;
