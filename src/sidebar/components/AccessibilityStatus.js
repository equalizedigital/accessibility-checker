/**
 * Accessibility Status Component
 */

import { __, sprintf } from '@wordpress/i18n';
import { Panel, PanelBody, PanelRow } from '@wordpress/components';
import { useAccessibilityCheckerData } from '../hooks/useAccessibilityCheckerData';
import Icon from './Icon';
import '../sass/components/spinner.scss';
import '../sass/components/accessibility-status.scss';

/**
 * Accessibility Status component
 *
 * @return {JSX.Element} The accessibility status panel
 */
const AccessibilityStatus = () => {
	const { data, refreshing } = useAccessibilityCheckerData();

	// Extract data from store
	const summary = data?.summary || {};
	const readability = data?.readability || {};

	const coveragePercent = summary.passed_tests || 0;
	const problems = summary.errors || 0;
	const needsReview = summary.warnings || 0;

	const postGrade = readability.post_grade || 0;
	const postGradeReadable = readability.post_grade_readability || '';
	const needsSummary = postGrade >= 9;
	const hasSummary = !! readability.simplified_summary;

	// Determine reading level display
	let readingLevelText = __( 'Not available', 'accessibility-checker' );
	let summaryStatus = '';

	if ( postGrade > 0 ) {
		if ( postGradeReadable ) {
			readingLevelText = sprintf( __( '%s', 'accessibility-checker' ), postGradeReadable );
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
				title={ __( 'Accessibility Status', 'accessibility-checker' ) }
				initialOpen={ true }
				className="edac-panel-body edac-accessibility-status"
			>
				{ refreshing && (
					<p className="edac-refreshing-message">
						<span className="edac-spinner">
							<span className="spinner is-active" />
						</span>
						{ __( 'Updating...', 'accessibility-checker' ) }
					</p>
				) }

				<PanelRow className="edac-status-grid">
					{/* Coverage */}
					<div className="edac-status-card">
						<div className="edac-status-card__header">
							<span className="edac-status-card__label">
								{ __( 'Coverage', 'accessibility-checker' ) }
								<sup>*</sup>
							</span>
							<Icon name="info" type="info" className="edac-status-card__icon" />
						</div>
						<div className="edac-status-card__value">
							{ coveragePercent }%
						</div>
						<progress
							className="edac-status-card__progress"
							value={ coveragePercent }
							max="100"
						/>
						{/* Placeholder for 30-day trend - will be implemented later */}
					</div>

					{/* Problems (Errors) */}
					<div className="edac-status-card">
						<div className="edac-status-card__header">
							<span className="edac-status-card__label">
								{ __( 'Problems', 'accessibility-checker' ) }
							</span>
							<Icon
								name={ problems > 0 ? 'error' : 'check' }
								type={ problems > 0 ? 'error' : 'success' }
								className="edac-status-card__icon"
							/>
						</div>
						<div className="edac-status-card__value">
							{ problems }
						</div>
						{/* Placeholder for 30-day trend - will be implemented later */}
					</div>

					{/* Needs Review (Warnings) */}
					<div className="edac-status-card">
						<div className="edac-status-card__header">
							<span className="edac-status-card__label">
								{ __( 'Needs Review', 'accessibility-checker' ) }
							</span>
							<Icon
								name={ needsReview > 0 ? 'warning' : 'check' }
								type={ needsReview > 0 ? 'warning' : 'success' }
								className="edac-status-card__icon"
							/>
						</div>
						<div className="edac-status-card__value">
							{ needsReview }
						</div>
						{/* Placeholder for 30-day trend - will be implemented later */}
					</div>

					{/* Reading Level */}
					<div className="edac-status-card">
						<div className="edac-status-card__header">
							<span className="edac-status-card__label">
								{ __( 'Reading Level', 'accessibility-checker' ) }
							</span>
							<Icon
								name={ needsSummary && ! hasSummary ? 'warning' : 'check' }
								type={ needsSummary && ! hasSummary ? 'warning' : 'success' }
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
				</PanelRow>

				<div className="edac-status-footer">
					<p className="edac-status-footer__note">
					* { __( 'True accessibility requires manual testing in addition to automated scans.', 'accessibility-checker' ) }
					</p>
					<a
						href="https://equalizedigital.com/accessibility-checker/manual-testing/"
						target="_blank"
						rel="noopener noreferrer"
						className="edac-status-footer__link"
					>
						{ __( 'Learn how to manually test for accessibility', 'accessibility-checker' ) }
					</a>
				</div>
			</PanelBody>
		</Panel>
	);
};

export default AccessibilityStatus;

