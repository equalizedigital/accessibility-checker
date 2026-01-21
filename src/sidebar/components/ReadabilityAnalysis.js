/**
 * Readability Analysis Component
 */

import { __, sprintf } from '@wordpress/i18n';
import { PanelBody, PanelRow, TextareaControl, Button } from '@wordpress/components';
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

	// Extract readability data from the accessibility data.
	const readabilityData = data?.readability || null;
	const postGrade = readabilityData?.post_grade;
	const postGradeReadable = readabilityData?.post_grade_readability;
	const contentLength = Number( readabilityData?.content_length || 0 );
	const hasContent = contentLength > 0;

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
					summary: summaryText || initialSummary,
				},
			} );

			// Update the grade state with the response
			if ( response.success ) {
				setSummaryGrade( response.simplified_summary_grade || 0 );
				setSummaryGradeFailed( response.simplified_summary_grade_failed || false );
			}
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Error saving summary:', error );
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

	const readingLevelStatus = getReadingLevelStatus();
	const gradeLabel = postGradeReadable || ( postGrade ? sprintf( __( '%dth grade', 'accessibility-checker' ), postGrade ) : __( 'Not available', 'accessibility-checker' ) );

	// Don't render if no data
	if ( ! readabilityData ) {
		return null;
	}

	return (
		<PanelBody
			title={ __( 'Readability Analysis', 'accessibility-checker' ) }
			initialOpen={ true }
			className="edac-panel-body"
		>
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

			{/* Reading Level Panel */}
			{ hasContent && readingLevelStatus && (
				<PanelRow className="edac-readability-row">
					<div className="edac-readability-section">
						<div className="edac-readability-section__header">
							<Icon
								name={ readingLevelStatus === 'below' ? 'check' : 'warning' }
								type={ readingLevelStatus === 'below' ? 'success' : 'warning' }
								ariaLabel={ readingLevelStatus === 'below'
									? __( 'Reading level at or below 9th grade', 'accessibility-checker' )
									: __( 'Reading level above 9th grade', 'accessibility-checker' )
								}
							/>
							<h3 className="edac-readability-section__title">
								{ gradeLabel }
							</h3>
						</div>

						<p className="edac-readability-section__message">
							{ readingLevelStatus === 'below'
								? __( 'Content written at a 9th-grade reading level or below does not require a simplified summary.', 'accessibility-checker' )
								: __( 'Content above a 9th-grade reading level requires a simplified summary to meet WCAG AAA guidance.', 'accessibility-checker' )
							}
						</p>

						{ readingLevelStatus !== 'below' && (
							<>
								<div className="edac-readability-section__subsection">
									<h4 className="edac-readability-section__subheading">
										{ __( 'Simplified Summary Reading Level', 'accessibility-checker' ) }
									</h4>
									{ summaryGrade > 0 ? (
										<>
											<p className={ `edac-readability-section__message ${ summaryGradeFailed ? 'failed-text-color' : 'passed-text-color' }` }>
												{ sprintf( __( '%dth grade', 'accessibility-checker' ), summaryGrade ) }
											</p>
											<p className="edac-readability-section__message">
												{ summaryGradeFailed
													? __( 'Your simplified summary has a reading level higher than 9th grade.', 'accessibility-checker' )
													: __( 'Your simplified summary has a reading level at or below 9th grade.', 'accessibility-checker' )
												}
											</p>
										</>
									) : (
										<p className="edac-readability-section__message">
											{ __( 'Summary required', 'accessibility-checker' ) }
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
										value={ summaryText || initialSummary }
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
										{ isSaving ? __( 'Saving...', 'accessibility-checker' ) : __( 'Save Summary', 'accessibility-checker' ) }
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

