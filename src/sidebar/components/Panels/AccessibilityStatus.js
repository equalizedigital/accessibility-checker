/**
 * Accessibility Status Component
 */

import { __, sprintf } from '@wordpress/i18n';
import { Panel, PanelBody, PanelRow } from '@wordpress/components';
import { useAccessibilityCheckerData } from '../../hooks/useAccessibilityCheckerData';
import { useCallback, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { STORE_NAME } from '../../store/accessibility-checker-store';
import Icon from '../Icon';
import { renderPanelTitleWithIcon } from '../../utils/panelHelpers';
import '../../sass/components/spinner.scss';
import '../../sass/components/accessibility-status.scss';

/**
 * Accessibility Status component
 *
 * @return {JSX.Element} The accessibility status panel
 */
const AccessibilityStatus = () => {
	const { data, refetch } = useAccessibilityCheckerData();

	// Panel ID for state persistence
	const panelId = 'accessibility-status';

	// Get panel expanded state from store
	const isPanelExpanded = useSelect( ( select ) => {
		return select( STORE_NAME ).isExpandedPanel( panelId );
	}, [ panelId ] );

	// Get UI state to check if panel has been explicitly set
	const uiState = useSelect( ( select ) => {
		return select( STORE_NAME ).getUIState();
	}, [] );

	const { setExpandedPanel } = useDispatch( STORE_NAME );

	// Initialize panel to open on first mount if not set in store
	useEffect( () => {
		// Check if the panel state has never been set
		// We want to ensure it's explicitly set so the controlled component works properly
		if ( uiState.expandedPanels[ panelId ] === undefined ) {
			setExpandedPanel( panelId, true );
		}
	}, [ panelId, setExpandedPanel, uiState.expandedPanels ] );

	// Handle panel toggle
	const handlePanelToggle = () => {
		setExpandedPanel( panelId, ! isPanelExpanded );
	};

	// Listen for ignore updates from the old metabox and refetch data
	useEffect( () => {
		const handleIgnoreUpdated = () => {
			// Small delay so the ignore save can complete before we refetch.
			window.setTimeout( () => {
				refetch();
			}, 300 );
		};

		window.addEventListener( 'edac-ignore-updated', handleIgnoreUpdated );

		return () => {
			window.removeEventListener( 'edac-ignore-updated', handleIgnoreUpdated );
		};
	}, [ refetch ] );

	// Extract data from store
	const summary = data?.summary || {};
	const readability = data?.readability || {};

	const coveragePercent = summary.passed_tests || 0;
	const problems = ( summary.errors || 0 ) + ( summary.contrast_errors || 0 );
	const needsReview = summary.warnings || 0;

	const postGrade = readability.post_grade || 0;
	const postGradeReadable = readability.post_grade_readability || '';
	const needsSummary = !! readability.post_grade_failed;
	const hasSummary = !! readability.simplified_summary;

	// Determine status icon based on errors and warnings
	let statusIconName = 'check';

	if ( problems > 0 ) {
		statusIconName = 'error';
	} else if ( needsReview > 0 ) {
		statusIconName = 'warning';
	}

	// Determine reading level display
	let readingLevelText = __( 'N/A', 'accessibility-checker' );
	let summaryStatus = '';

	// Handles clicking a status card to scroll to the analysis panel and open a specific tab.
	const handleAnalysisCardClick = useCallback( ( tabName ) => {
		const analysisElement = document.querySelector( '.edac-accessibility-analysis' );
		if ( ! analysisElement ) {
			return;
		}

		analysisElement.scrollIntoView( { behavior: 'smooth', block: 'start' } );

		// Open the panel if it's closed.
		const panelButton = analysisElement.querySelector( '.components-panel__body-toggle' );
		if ( panelButton && panelButton.getAttribute( 'aria-expanded' ) === 'false' ) {
			panelButton.click();
		}

		// The timeout gives the panel time to open before we try to click the tab.
		// This can be brittle and might fail on slow devices.
		setTimeout( () => {
			const tabButton = analysisElement.querySelector( `button[id$='-${ tabName }']` );
			if ( tabButton ) {
				tabButton.click();
				tabButton.focus();
			}
		}, 100 );
	}, [] );

	const handleProblemsClick = useCallback( () => {
		handleAnalysisCardClick( 'problems' );
	}, [ handleAnalysisCardClick ] );

	const handleNeedsReviewClick = useCallback( () => {
		handleAnalysisCardClick( 'warnings' );
	}, [ handleAnalysisCardClick ] );

	// Handle click on Reading Level card to scroll to ReadabilityAnalysis
	const handleReadingLevelClick = useCallback( () => {
		const readabilityElement = document.querySelector( '.edac-readability-analysis' );
		if ( readabilityElement ) {
			readabilityElement.scrollIntoView( { behavior: 'smooth', block: 'start' } );
			// Open the panel if closed
			const panelButton = readabilityElement.querySelector( '.components-panel__body-toggle' );
			if ( panelButton && panelButton.getAttribute( 'aria-expanded' ) === 'false' ) {
				panelButton.click();
			}
			// Focus the panel
			setTimeout( () => {
				if ( panelButton ) {
					panelButton.focus();
				}
			}, 100 );
		}
	}, [] );

	if ( postGrade > 0 ) {
		if ( postGradeReadable ) {
			readingLevelText = postGradeReadable;
		} else {
			readingLevelText = sprintf( __( '%dth', 'accessibility-checker' ), postGrade );
		}

		if ( needsSummary ) {
			summaryStatus = hasSummary
				? __( 'Summary provided', 'accessibility-checker' )
				: __( 'Summary required', 'accessibility-checker' );
		} else {
			summaryStatus = __( 'Summary not required', 'accessibility-checker' );
		}
	}

	return (
		<Panel>
			<PanelBody
				title={ renderPanelTitleWithIcon(
					statusIconName,
					__( 'Accessibility Status', 'accessibility-checker' ),
				) }
				initialOpen={ true }
				opened={ isPanelExpanded }
				onToggle={ handlePanelToggle }
				className="edac-panel-body edac-accessibility-status"
			>
				<PanelRow className="edac-status-grid">

					{/* Problems (Errors) */}
					<div
						className="edac-status-card edac-status-card--clickable"
						onClick={ handleProblemsClick }
						role="button"
						tabIndex={ 0 }
						aria-label={ sprintf(
							__( 'View %d problems in Accessibility Analysis', 'accessibility-checker' ),
							problems,
						) }
						onKeyDown={ ( e ) => {
							if ( e.key === 'Enter' || e.key === ' ' ) {
								if ( e.key === ' ' ) {
									e.preventDefault();
								}
								handleProblemsClick();
							}
						} }
					>
						<div className="edac-status-card__header">
							<span className="edac-status-card__label">
								{ __( 'Problems', 'accessibility-checker' ) }
							</span>
							<Icon
								name={ problems > 0 ? 'error' : 'check' }
								className="edac-status-card__icon"
							/>
						</div>
						<div className="edac-status-card__value">
							{ problems }
						</div>
						{/* Placeholder for 30-day trend - will be implemented later */}
					</div>

					{/* Needs Review (Warnings) */}
					<div
						className="edac-status-card edac-status-card--clickable"
						onClick={ handleNeedsReviewClick }
						role="button"
						tabIndex={ 0 }
						aria-label={ sprintf(
							__( 'View %d items needing review in Accessibility Analysis', 'accessibility-checker' ),
							needsReview,
						) }
						onKeyDown={ ( e ) => {
							if ( e.key === 'Enter' || e.key === ' ' ) {
								if ( e.key === ' ' ) {
									e.preventDefault();
								}
								handleNeedsReviewClick();
							}
						} }
					>
						<div className="edac-status-card__header">
							<span className="edac-status-card__label">
								{ __( 'Needs Review', 'accessibility-checker' ) }
							</span>
							<Icon
								name={ needsReview > 0 ? 'warning' : 'check' }
								className="edac-status-card__icon"
							/>
						</div>
						<div className="edac-status-card__value">
							{ needsReview }
						</div>
						{/* Placeholder for 30-day trend - will be implemented later */}
					</div>
					{/* Reading Level */}
					<div
						className="edac-status-card edac-status-card--clickable"
						onClick={ handleReadingLevelClick }
						role="button"
						tabIndex={ 0 }
						aria-label={ sprintf(
							__( 'View reading level details: %s', 'accessibility-checker' ),
							readingLevelText,
						) }
						onKeyDown={ ( e ) => {
							if ( e.key === 'Enter' || e.key === ' ' ) {
								if ( e.key === ' ' ) {
									e.preventDefault();
								}
								handleReadingLevelClick();
							}
						} }
					>
						<div className="edac-status-card__header">
							<span className="edac-status-card__label">
								{ __( 'Reading Level', 'accessibility-checker' ) }
							</span>
							<Icon
								name={ needsSummary && ! hasSummary ? 'warning' : 'check' }
								className="edac-status-card__icon"
							/>
						</div>
						<div className="edac-status-card__value">
							{ readingLevelText }
						</div>
						{ summaryStatus && (
							<div className="edac-status-card__meta">
								{ summaryStatus }
							</div>
						) }
					</div>
					{/* Passed Checks (Coverage) */}
					<div className="edac-status-card">
						<div className="edac-status-card__header">
							<span className="edac-status-card__label">
								{ __( 'Passed Checks', 'accessibility-checker' ) }
							</span>
							<Icon name="info" className="edac-status-card__icon" />
						</div>
						<div className="edac-status-card__value">
							{ coveragePercent }%
						</div>
						{/* Placeholder for 30-day trend - will be implemented later */}
					</div>
				</PanelRow>
			</PanelBody>
		</Panel>
	);
};

export default AccessibilityStatus;

