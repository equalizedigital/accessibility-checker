/**
 * Readability Analysis Component
 */

import { __, sprintf } from '@wordpress/i18n';
import { PanelBody, PanelRow, TextareaControl, Button, Notice } from '@wordpress/components';
import { useAccessibilityCheckerData } from '../../hooks/useAccessibilityCheckerData';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { STORE_NAME } from '../../store/accessibility-checker-store';
import Icon from '../Icon';
import { renderPanelTitleWithIcon } from '../../utils/panelHelpers';
import '../../sass/components/readability-analysis.scss';

/**
 * Readability Analysis component
 *
 * @return {JSX.Element} The readability analysis panel
 */
const ReadabilityAnalysis = () => {
	const { data, updateReadabilityData } = useAccessibilityCheckerData();
	const postId = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostId(), [] );
	const { settingsUrl, readabilityHelpUrl } = window?.edac_sidebar_app || {};
	const [ isSaving, setIsSaving ] = useState( false );
	const [ summaryText, setSummaryText ] = useState( '' );
	const [ summaryGrade, setSummaryGrade ] = useState( 0 );
	const [ summaryGradeFailed, setSummaryGradeFailed ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	// Panel ID for state persistence
	const panelId = 'readability-analysis';

	// Get panel expanded state from store
	const isPanelExpanded = useSelect( ( select ) => select( STORE_NAME ).isExpandedPanel( panelId ), [ panelId ] );
	const { setExpandedPanel } = useDispatch( STORE_NAME );

	// Handle panel toggle
	const handlePanelToggle = () => {
		setExpandedPanel( panelId, ! isPanelExpanded );
	};

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

			// Update the grade state with the complete response data
			if ( response.success ) {
				setSummaryGrade( response.simplified_summary_grade || 0 );
				setSummaryGradeFailed( response.simplified_summary_grade_failed || false );

				// Update the data store with the complete readability data
				updateReadabilityData( {
					post_grade: response.post_grade,
					post_grade_readability: response.post_grade_readability,
					post_grade_failed: response.post_grade_failed,
					simplified_summary: response.simplified_summary,
					simplified_summary_grade: response.simplified_summary_grade,
					simplified_summary_grade_failed: response.simplified_summary_grade_failed,
					simplified_summary_prompt: response.simplified_summary_prompt,
					simplified_summary_position: response.simplified_summary_position,
					content_length: response.content_length,
				} );

				setNotice( {
					type: 'success',
					message: __( 'Simplified summary saved successfully.', 'accessibility-checker' ),
				} );

				// Dispatch custom event to trigger old metabox refresh
				const event = new CustomEvent( 'edac-simplified-summary-saved', {
					detail: {
						postId,
						summary: summaryText,
						readabilityData: response,
					},
				} );
				window.dispatchEvent( event );
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

		if ( postGrade >= 9 ) {
			return 'above';
		}

		return 'below';
	};

	const getGradeLabel = () => {
		if ( postGradeReadable ) {
			return sprintf( __( '%s grade', 'accessibility-checker' ), postGradeReadable );
		}
		if ( postGrade ) {
			return sprintf( __( '%dth grade', 'accessibility-checker' ), postGrade );
		}
		return __( 'Not available', 'accessibility-checker' );
	};

	const readingLevelStatus = getReadingLevelStatus();
	const gradeLabel = getGradeLabel();

	// Build screen reader text for accordion title (grade only when available)
	let srOnlyTitle = '';
	if ( hasContent && postGrade > 0 && readingLevelStatus ) {
		srOnlyTitle = gradeLabel;
	}

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
			title={ renderPanelTitleWithIcon(
				getPanelIcon(),
				__( 'Readability Analysis', 'accessibility-checker' ),
				hasContent && postGrade > 0 ? ` (${ postGradeReadable })` : '',
				srOnlyTitle,
			) }
			className="edac-panel-body edac-readability-analysis-panel edac-readability-analysis"
			initialOpen={ false }
			opened={ isPanelExpanded }
			onToggle={ handlePanelToggle }
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
				<PanelRow className="edac-panel-row">
					<div className="edac-panel-section">
						<div className="edac-panel-section__header">
							<Icon
								name="warning"
								type="warning"
								ariaLabel={ __( 'Warning', 'accessibility-checker' ) }
							/>
							<h3 className="edac-panel-section__title">
								{ __( 'Not available', 'accessibility-checker' ) }
							</h3>
						</div>
						<p className="edac-panel-section__message">
							{ __( 'This post does not contain enough content to calculate a reading level.', 'accessibility-checker' ) }
						</p>
						<a href={ settingsUrl || '#' } className="edac-panel-section__link">
							{ __( 'Adjust summary prompts in settings.', 'accessibility-checker' ) }
						</a>
					</div>
				</PanelRow>
			) }

			{/* Not Enough Content Panel - when postGrade is 0 */}
			{ hasContent && postGrade === 0 && (
				<PanelRow className="edac-panel-row">
					<div className="edac-panel-section">
						<div className="edac-panel-section__header">
							<Icon
								name="warning"
								type="warning"
								ariaLabel={ __( 'Warning', 'accessibility-checker' ) }
							/>
							<h3 className="edac-panel-section__title">
								{ __( 'Not available', 'accessibility-checker' ) }
							</h3>
						</div>
						<p className="edac-panel-section__message">
							{ __( 'Not enough content to determine an accurate reading level.', 'accessibility-checker' ) }
						</p>
						<a href={ settingsUrl || '#' } className="edac-panel-section__link">
							{ __( 'Adjust summary prompts in settings.', 'accessibility-checker' ) }
						</a>
					</div>
				</PanelRow>
			) }

			{/* Reading Level Panel */}
			{ hasContent && postGrade > 0 && readingLevelStatus && (
				<PanelRow className="edac-panel-row">
					<div className="edac-panel-section">
						<div className="edac-panel-section__header">
							<h3 className="edac-panel-section__title">{ __( 'Reading Level', 'accessibility-checker' ) }</h3>
						</div>
						<p className="post-grade-display">
							<Icon name={ getReadingLevelIcon() } />
							{ gradeLabel }
						</p>

						<p className="edac-panel-section__message">
							{ readingLevelStatus === 'below'
								? __( 'Content written at a 9th-grade reading level or below does not require a simplified summary.', 'accessibility-checker' )
								: __( 'Content above a 9th-grade reading level requires a simplified summary to meet WCAG AAA guidance.', 'accessibility-checker' )
							}
						</p>
						{ readingLevelStatus === 'below' && (
							<a href={ settingsUrl || '#' } className="edac-panel-section__link">
								{ __( 'Adjust summary prompts in settings.', 'accessibility-checker' ) }
							</a>
						) }
						{ readingLevelStatus !== 'below' && (
							<a href={ readabilityHelpUrl || '#' } className="edac-panel-section__link">
								{ __( 'Learn more about readability requirements.', 'accessibility-checker' ) }
							</a>
						) }

						{ readingLevelStatus !== 'below' && (
							<>
								<div className="edac-panel-section__subsection">
									<h4 className="edac-panel-section__subheading">
										{ __( 'Simplified Summary Reading Level', 'accessibility-checker' ) }
									</h4>
									{ ! summaryText && (
										<p className="edac-panel-section__message">
											{ __( 'Summary required', 'accessibility-checker' ) }
										</p>
									) }
									{ summaryText && summaryGrade === 0 && (
										<p className="edac-panel-section__message">
											{ __( 'Not enough content to determine an accurate reading level.', 'accessibility-checker' ) }
										</p>
									) }
									{ summaryText && summaryGrade > 0 && (
										<p className="edac-panel-section__message">
											{ summaryGradeFailed
												? __( 'Needs improvement, summary is above the 9th-grade reading level.', 'accessibility-checker' )
												: __( 'Below the recommended 9th-grade reading level.', 'accessibility-checker' )
											}
										</p>
									) }
								</div>

								<div className="edac-panel-section__subsection">
									<h4 className="edac-panel-section__subheading">
										{ __( 'Simplified Summary', 'accessibility-checker' ) }
									</h4>
									<TextareaControl
										value={ summaryText }
										onChange={ setSummaryText }
										placeholder={ __( 'Enter simplified summary...', 'accessibility-checker' ) }
										rows={ 4 }
										className="summary-textarea"
									/>
									<Button
										variant="primary"
										onClick={ handleSaveSummary }
										disabled={ isSaving || summaryText === initialSummary }
										className="edac-panel-section__save-button"
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

