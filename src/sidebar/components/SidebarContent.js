/**
 * Accessibility Checker Sidebar Content Component
 */

import { __ } from '@wordpress/i18n';
import { useAccessibilityCheckerData } from '../hooks/useAccessibilityCheckerData';
import AccessibilityStatus from './AccessibilityStatus';
import ReadabilityAnalysis from './ReadabilityAnalysis';
import '../sass/components/sidebar-content.scss';

/**
 * Sidebar content component
 *
 * @return {JSX.Element} The sidebar content
 */
const SidebarContent = () => {
	const { loading, error } = useAccessibilityCheckerData();

	if ( loading ) {
		return (
			<div className="edac-sidebar__loading">
				<p>{ __( 'Loading accessibility data...', 'accessibility-checker' ) }</p>
			</div>
		);
	}

	if ( error ) {
		return (
			<div className="edac-sidebar__error">
				<p>{ error }</p>
			</div>
		);
	}

	return (
		<div className="edac-sidebar__content">
			<AccessibilityStatus />
			<ReadabilityAnalysis />
		</div>
	);
};

export default SidebarContent;

