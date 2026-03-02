/**
 * Dismiss Panel Component
 *
 * Handles dismissing and restoring issues with comments and reasons.
 */

import { __, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { Panel, PanelBody, Button, Spinner, RadioControl, Dropdown } from '@wordpress/components';
import { Notice } from '@wordpress/ui';
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

	// Notice action configuration for reusable dismiss button.
	const noticeActions = [
		{
			label: __( 'Dismiss notice', 'accessibility-checker' ),
			onClick: ( noticeType ) => {
				if ( noticeType === 'success' ) {
					setSuccessNotice( null );
				} else if ( noticeType === 'error' ) {
					setError( null );
				}
			},
		},
	];

	// Helper function to render a notice.
	const renderNotice = ( message, status, noticeType ) => (
		<Notice
			status={ status }
			onDismiss={ () => noticeActions[ 0 ].onClick( noticeType ) }
			actions={ [
				{
					label: noticeActions[ 0 ].label,
					onClick: () => noticeActions[ 0 ].onClick( noticeType ),
				},
			] }
		>
			{ message }
		</Notice>
	);

	let panelTitle;
	if ( isIgnored ) {
		// translators: %s: dismiss reason label e.g. "False Positive"
		panelTitle = dismissReasonLabel
			? sprintf( __( 'Issue Dismissed — %s', 'accessibility-checker' ), dismissReasonLabel )
			: __( 'Issue Dismissed', 'accessibility-checker' );
	} else {
		panelTitle = __( 'Dismiss Issue', 'accessibility-checker' );
	}

	return (
		<div className="edac-analysis__dismiss-panel" data-section="dismiss">
			<Panel>
				<PanelBody
					title={ panelTitle }
					opened={ isOpen }
					onToggle={ onToggle }
				>
					<div className="edac-analysis__panel-content">
						{ successNotice && renderNotice( successNotice, 'success', 'success' ) }
						{ error && renderNotice( error, 'error', 'error' ) }
						{ isIgnored ? (
							<>
								{ ( issue?.user || issue?.ignre_user_name || issue?.ignre_date || issue?.ignre_global ) && (
									<dl className="edac-analysis__dismissed-meta">

										{ ( issue?.ignre_global === 1 || issue?.ignre_global === '1' ) && (
											<>
												<dt>{ __( 'Scope:', 'accessibility-checker' ) }</dt>
												<dd>{ __( 'All pages', 'accessibility-checker' ) }</dd>
											</>
										) }
										{ ( issue?.ignre_user_name || issue?.user ) && (
											<>
												<dt>{ __( 'By:', 'accessibility-checker' ) }</dt>
												<dd>{ decodeEntities( issue.ignre_user_name || issue.user ) }</dd>
											</>
										) }
										{ issue?.ignre_date && (
											<>
												<dt>{ __( 'On:', 'accessibility-checker' ) }</dt>
												<dd>{ decodeEntities( issue.ignre_date ) }</dd>
											</>
										) }
									</dl>
								) }
								{ issue?.ignre_comment && (
									<div className="edac-analysis__dismissed-comment">
										<p className="edac-analysis__dismissed-comment-label">
											{ __( 'Reason for dismissal:', 'accessibility-checker' ) }
										</p>
										<div
											className="edac-analysis__dismissed-comment-body"
											dangerouslySetInnerHTML={ {
												__html: decodeEntities( issue.ignre_comment ),
											} }
										/>
									</div>
								) }
								<div className="edac-analysis__dismissed-actions">
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
								</div>
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
									labelId="edac-dismiss-comment-label"
									help={ __(
										'Add a note explaining why this issue is being dismissed. Supports bold, italic, and links.',
										'accessibility-checker',
									) }
									helpId="edac-dismiss-comment-helptext"
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
