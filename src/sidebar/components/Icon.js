/**
 * Icon Component
 *
 * Reusable icon component that renders different icons based on name prop
 */

import '../sass/components/icon.scss';

// Icon SVG definitions
const icons = {
	check: (
		<svg
			width="20"
			height="20"
			viewBox="0 0 20 20"
			fill="none"
			xmlns="http://www.w3.org/2000/svg"
		>
			<path
				d="M7.5 10.625L9.375 12.5L12.5 8.125"
				stroke="currentColor"
				strokeWidth="1.25"
				strokeLinecap="round"
				strokeLinejoin="round"
			/>
			<rect
				x="2.625"
				y="2.625"
				width="14.75"
				height="14.75"
				rx="2.375"
				stroke="currentColor"
				strokeWidth="1.25"
			/>
		</svg>
	),
	warning: (
		<svg
			width="20"
			height="20"
			viewBox="0 0 20 20"
			fill="none"
			xmlns="http://www.w3.org/2000/svg"
		>
			<path
				d="M9.99997 7.5V10.625M2.24747 13.4383C1.52581 14.6883 2.42831 16.25 3.87081 16.25H16.1291C17.5708 16.25 18.4733 14.6883 17.7525 13.4383L11.6241 2.815C10.9025 1.565 9.09747 1.565 8.37581 2.815L2.24747 13.4383ZM9.99997 13.125H10.0058V13.1317H9.99997V13.125Z"
				stroke="currentColor"
				strokeWidth="1.25"
				strokeLinecap="round"
				strokeLinejoin="round"
			/>
		</svg>
	),
	error: (
		<svg
			width="20"
			height="20"
			viewBox="0 0 20 20"
			fill="none"
			xmlns="http://www.w3.org/2000/svg"
		>
			<path
				d="M10 7.5V10.625M17.5 10C17.5 10.9849 17.306 11.9602 16.9291 12.8701C16.5522 13.7801 15.9997 14.6069 15.3033 15.3033C14.6069 15.9997 13.7801 16.5522 12.8701 16.9291C11.9602 17.306 10.9849 17.5 10 17.5C9.01509 17.5 8.03982 17.306 7.12987 16.9291C6.21993 16.5522 5.39314 15.9997 4.6967 15.3033C4.00026 14.6069 3.44781 13.7801 3.0709 12.8701C2.69399 11.9602 2.5 10.9849 2.5 10C2.5 8.01088 3.29018 6.10322 4.6967 4.6967C6.10322 3.29018 8.01088 2.5 10 2.5C11.9891 2.5 13.8968 3.29018 15.3033 4.6967C16.7098 6.10322 17.5 8.01088 17.5 10ZM10 13.125H10.0067V13.1317H10V13.125Z"
				stroke="currentColor"
				strokeWidth="1.25"
				strokeLinecap="round"
				strokeLinejoin="round"
			/>
		</svg>
	),
};

/**
 * Icon component
 *
 * @param {Object}  props            - Component props
 * @param {string}  props.name       - Icon name (check, warning, error)
 * @param {string}  props.type       - Icon type for color (success, warning, error)
 * @param {boolean} props.ariaHidden - Hide from accessibility tree (default: true)
 * @param {string}  props.ariaLabel  - Accessible label for the icon
 * @param {string}  props.className  - Additional CSS classes
 * @return {JSX.Element|null} The icon element
 */
const Icon = ( { name = 'check', type = 'success', ariaHidden = true, ariaLabel, className = '' } ) => {
	const iconSvg = icons[ name ];

	if ( ! iconSvg ) {
		return null;
	}

	const baseClass = 'edac-icon';
	const typeClass = type ? `edac-icon--${ type }` : '';
	const classes = [ baseClass, typeClass, className ].filter( Boolean ).join( ' ' );

	const ariaProps = {
		'aria-hidden': ariaHidden,
	};

	if ( ariaLabel ) {
		ariaProps[ 'aria-label' ] = ariaLabel;
	}

	return (
		<span className={ classes } { ...ariaProps }>
			{ iconSvg }
		</span>
	);
};

export default Icon;

