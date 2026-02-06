/**
 * Badge Component
 *
 * Displays a label inside a styled badge with optional icon
 */

import Icon from './Icon';
import '../sass/components/badge.scss';

/**
 * Renders badge with icon and label
 *
 * @param {Object} props           - Component props.
 * @param {string} props.label     - Text label for the badge.
 * @param {string} props.type      - Type/style of badge.
 * @param {string} props.icon      - Optional icon name to display.
 * @param {string} props.size      - Optional size variant ('small' or default).
 * @param {string} props.className - Optional CSS classes.
 * @return {JSX.Element} Badge element
 */
const Badge = ( { label, type = 'info', icon, size = '', className = '' } ) => {
	const sizeClass = size ? `edac-badge--${ size }` : '';
	const classes = [ `edac-badge edac-badge--${ type }`, sizeClass, className ]
		.filter( Boolean )
		.join( ' ' );

	return (
		<span className={ classes }>
			{ icon && <Icon name={ icon } type={ type } ariaHidden={ true } /> }
			<span className="edac-badge__label">{ label }</span>
		</span>
	);
};

export default Badge;
