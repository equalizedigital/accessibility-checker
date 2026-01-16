/**
 * Accessibility Checker Sidebar Content Component
 */

import { __ } from '@wordpress/i18n';
import { useAccessibilityDataContext } from '../context/AccessibilityDataContext';

/**
 * Sidebar content component
 *
 * @return {JSX.Element} The sidebar content
 */
const SidebarContent = () => {
	const { loading, error, data, postId } = useAccessibilityDataContext();

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
			<p>{ __( 'Sidebar content goes here', 'accessibility-checker' ) }</p>
			<p><strong>Post ID:</strong> { postId }</p>
			<p><strong>Data loaded:</strong> { data ? 'Yes' : 'No' }</p>
			{ data && (
				<pre style={ { fontSize: '11px', overflow: 'auto' } }>
					{ JSON.stringify( data, null, 2 ) }
				</pre>
			) }
		</div>
	);
};

export default SidebarContent;
