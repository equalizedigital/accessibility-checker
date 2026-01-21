/**
 * Readability Analysis Component
 */

import { __, sprintf } from '@wordpress/i18n';
import { PanelBody, PanelRow, TextareaControl, Button, Notice } from '@wordpress/components';
import { useAccessibilityCheckerData } from '../hooks/useAccessibilityCheckerData';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import Icon from './Icon';
import '../sass/components/readability-analysis.scss';

/**
 * Readability Analysis component
 *
 * @return {JSX.Element} The readability analysis panel
 */
const ReadabilityAnalysis = () => {
	const { data } = useAccessibilityCheckerData();
	const postId = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostId(), [] );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ summaryText, setSummaryText ] = useState( '' );
	const [ summaryGrade, setSummaryGrade ] = useState( 0 );
	const [ summaryGradeFailed, setSummaryGradeFailed ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	// Extract readability data from the accessibility data.
	const readabilityData = data?.readability || null;
	const postGrade = readabilityData?.post_grade;
	const postGradeReadable = readabilityData?.post_grade_readability;
	const contentLength = Number( readabilityData?.content_length || 0 );
	const hasContent = contentLength > 20;

	// Initialize summary text from readability data
	const initialSummary = readabilityData?.simplified_summary || '';
	const initialSummaryGrade = readabilityData?.simplified_summary_grade || 0;
	const initialSummaryGradeFailed = Boolean( readabilityData?.simplified_summary_grade_failed );

	// Update textarea when initial summary changes
	useEffect( () => {
		setSummaryText( initialSummary );
		setSummaryGrade( initialSummaryGrade );
		setSummaryGradeFailed( initialSummaryGradeFailed );
	}, [ initialSummary, initialSummaryGrade, initialSummaryGradeFailed ] );

	// Handle summary save
	const handleSaveSummary = async () => {
		setIsSaving( true );
		try {
			const response = await apiFetch( {
				path: `/accessibility-checker/v1/simplified-summary/${ postId }`,
				method: 'POST',
				data: {
					summary: summaryText,
				},
			} );

			// Update the grade state with the response
			if ( response.success ) {
				setSummaryGrade( response.simplified_summary_grade || 0 );
				setSummaryGradeFailed( response.simplified_summary_grade_failed || false );
				setNotice( {
					type: 'success',
					message: __( 'Simplified summary saved successfully.', 'accessibility-checker' ),
				} );
			} else {
				setNotice( {
					type: 'error',
					message: __( 'Failed to save summary. Please try again.', 'accessibility-checker' ),
				} );
			}
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Error saving summary:', error );
			setNotice( {
				type: 'error',
				message: __( 'An error occurred while saving the summary.', 'accessibility-checker' ),
			} );
		} finally {
			setIsSaving( false );
		}
	};

	// Determine reading level status
	const getReadingLevelStatus = () => {
		if ( postGrade === undefined || postGrade === null ) {
			return null;
		}

		if ( postGrade > 9 ) {
			return 'above';
		}

		if ( postGrade < 9 ) {
			return 'below';
		}

		return 'at';
	};

	const getGradeLabel = () => {
		if ( postGradeReadable ) {
			return postGradeReadable + ' grade';
		}
		if ( postGrade ) {
			return sprintf( __( '%dth grade', 'accessibility-checker' ), postGrade );
		}
		return __( 'Not available', 'accessibility-checker' );
	};

	const readingLevelStatus = getReadingLevelStatus();
	const gradeLabel = getGradeLabel();

	// Determine the correct icon for the PanelBody title based on the overall status
	const getPanelIcon = () => {
		if ( ! hasContent || postGrade === 0 || postGrade === undefined || postGrade === null ) {
			return 'warning';
		}
		if ( readingLevelStatus === 'below' ) {
			return 'check';
		}
		if ( readingLevelStatus !== 'below' ) {
			if ( ! summaryText ) {
				return 'warning';
			}
			if ( summaryGrade > 0 && ! summaryGradeFailed ) {
				return 'check';
			}
			if ( summaryGradeFailed ) {
				return 'warning';
			}
		}
		return 'warning';
	};

	// Determine the correct icon for the reading level display
	const getReadingLevelIcon = () => {
		if ( readingLevelStatus === 'below' ) {
			return 'check';
		}
		if ( summaryText && summaryGrade > 0 && ! summaryGradeFailed ) {
			return 'info';
		}
		return 'warning';
	};

	// Don't render if no data
	if ( ! readabilityData ) {
		return null;
	}

	return (
		<PanelBody
			title={ (
				<>
					<Icon name={getPanelIcon()} />
					{ __( 'Readability Analysis', 'accessibility-checker' ) }
					{ hasContent && postGrade > 0 && ` (${ postGradeReadable })` }
				</>
			) }
			initialOpen={ true }
			className="edac-panel-body"
		>
			{ notice && (
				<Notice
					status={ notice.type }
					isDismissible={ true }
					onRemove={ () => setNotice( null ) }
				>
					{ notice.message }
				</Notice>
			) }

			{/* No Content Panel */}
			{ ! hasContent && (
				<PanelRow className="edac-readability-row">
					<div className="edac-readability-section">
						<div className="edac-readability-section__header">
							<Icon
								name="warning"
								type="warning"
								ariaLabel={ __( 'Warning', 'accessibility-checker' ) }
							/>
							<h3 className="edac-readability-section__title">
								{ __( 'Not available', 'accessibility-checker' ) }
							</h3>
						</div>
						<p className="edac-readability-section__message">
							{ __( 'This post does not contain enough content to calculate a reading level.', 'accessibility-checker' ) }
						</p>
						<a href="#" className="edac-readability-section__link">
							{ __( 'Adjust summary prompts in settings.', 'accessibility-checker' ) }
						</a>
					</div>
				</PanelRow>
			) }

			{/* Not Enough Content Panel - when postGrade is 0 */}
			{ hasContent && postGrade === 0 && (
				<PanelRow className="edac-readability-row">
					<div className="edac-readability-section">
						<div className="edac-readability-section__header">
							<Icon
								name="warning"
								type="warning"
								ariaLabel={ __( 'Warning', 'accessibility-checker' ) }
							/>
							<h3 className="edac-readability-section__title">
								{ __( 'Not available', 'accessibility-checker' ) }
							</h3>
						</div>
						<p className="edac-readability-section__message">
							{ __( 'Not enough content to determine an accurate reading level.', 'accessibility-checker' ) }
						</p>
						<a href="#" className="edac-readability-section__link">
							{ __( 'Adjust summary prompts in settings.', 'accessibility-checker' ) }
						</a>
					</div>
				</PanelRow>
			) }

			{/* Reading Level Panel */}
			{ hasContent && postGrade > 0 && readingLevelStatus && (
				<PanelRow className="edac-readability-row">
					<div className="edac-readability-section">
						<div className="edac-readability-section__header">
							<h3 className="edac-readability-section__title">{ __( 'Reading Level', 'accessibility-checker' ) }</h3>
						</div>
						<p className="edac-readability-section__grade-display">
							<Icon name={ getReadingLevelIcon() } />
							{ gradeLabel }
						</p>

						<p className="edac-readability-section__message">
							{ readingLevelStatus === 'below'
								? __( 'Content written at a 9th-grade reading level or below does not require a simplified summary.', 'accessibility-checker' )
								: __( 'Content above a 9th-grade reading level requires a simplified summary to meet WCAG AAA guidance.', 'accessibility-checker' )
							}
						</p>
						{ readingLevelStatus === 'below' && (
							<a href="#" className="edac-readability-section__link">
								{ __( 'Adjust summary prompts in settings.', 'accessibility-checker' ) }
							</a>
						) }
						{ readingLevelStatus !== 'below' && (
							<a href="#" className="edac-readability-section__link">
								{ __( 'Learn more about readability requirements.', 'accessibility-checker' ) }
							</a>
						) }

						{ readingLevelStatus !== 'below' && (
							<>
								<div className="edac-readability-section__subsection">
									<h4 className="edac-readability-section__subheading">
										{ __( 'Simplified Summary Reading Level', 'accessibility-checker' ) }
									</h4>
									{ ! summaryText && (
										<p className="edac-readability-section__message">
											{ __( 'Summary required', 'accessibility-checker' ) }
										</p>
									) }
									{ summaryText && summaryGrade === 0 && (
										<p className="edac-readability-section__message">
											{ __( 'Not enough content to determine an accurate reading level.', 'accessibility-checker' ) }
										</p>
									) }
									{ summaryText && summaryGrade > 0 && (
										<p className="edac-readability-section__message">
											{ summaryGradeFailed
												? __( 'Needs improvement, not above the 9th-grade reading level.', 'accessibility-checker' )
												: __( 'Below the recommended 9th-grade reading level.', 'accessibility-checker' )
											}
										</p>
									) }
								</div>

								<div className="edac-readability-section__subsection">
									<h4 className="edac-readability-section__subheading">
										{ __( 'Placement', 'accessibility-checker' ) }
									</h4>
									<p className="edac-readability-section__message">
										{ __( 'Inserted after the content', 'accessibility-checker' ) }
									</p>
									<a href="#" className="edac-readability-section__link">
										{ __( 'Change in settings', 'accessibility-checker' ) }
									</a>
								</div>

								<div className="edac-readability-section__subsection">
									<h4 className="edac-readability-section__subheading">
										{ __( 'Simplified Summary', 'accessibility-checker' ) }
									</h4>
									<TextareaControl
										value={ summaryText }
										onChange={ setSummaryText }
										placeholder={ __( 'Enter simplified summary...', 'accessibility-checker' ) }
										rows={ 4 }
										className="edac-readability-section__textarea"
									/>
									<Button
										variant="primary"
										onClick={ handleSaveSummary }
										disabled={ isSaving }
										className="edac-readability-section__save-button"
									>
										{ __( 'Save Summary', 'accessibility-checker' ) }
									</Button>
								</div>
							</>
						) }
					</div>
				</PanelRow>
			) }
		</PanelBody>
	);
};

export default ReadabilityAnalysis;

