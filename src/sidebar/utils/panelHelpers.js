/**
 * Panel helper utilities
 */

import Icon from '../components/Icon';

/**
 * Helper function to render panel title with icon
 *
 * @param {string} iconName - The icon name to use from the Icon component
 * @param {string} title    - The title text
 * @param {string} subtitle - Optional subtitle/extra text to append after title
 * @return {JSX.Element} The rendered title with icon
 */
export const renderPanelTitleWithIcon = ( iconName, title, subtitle = '' ) => (
	<>
		{ iconName && <Icon name={ iconName } /> }
		<span>{ title }</span>
		{ subtitle && <span>{ subtitle }</span> }
	</>
);
