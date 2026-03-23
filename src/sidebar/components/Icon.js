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
	info: (
		<svg
			width="20"
			height="20"
			viewBox="0 0 20 20"
			fill="none"
			xmlns="http://www.w3.org/2000/svg"
		>
			<path
				d="M9.375 9.375L9.40917 9.35833C9.51602 9.30495 9.63594 9.2833 9.75472 9.29596C9.8735 9.30862 9.98616 9.35505 10.0794 9.42976C10.1726 9.50446 10.2424 9.60432 10.2806 9.71749C10.3189 9.83066 10.3238 9.95242 10.295 10.0683L9.705 12.4317C9.67595 12.5476 9.68078 12.6695 9.71891 12.7828C9.75704 12.8961 9.82687 12.9961 9.92011 13.071C10.0134 13.1458 10.1261 13.1923 10.245 13.205C10.3639 13.2177 10.4839 13.196 10.5908 13.1425L10.625 13.125M17.5 10C17.5 10.9849 17.306 11.9602 16.9291 12.8701C16.5522 13.7801 15.9997 14.6069 15.3033 15.3033C14.6069 15.9997 13.7801 16.5522 12.8701 16.9291C11.9602 17.306 10.9849 17.5 10 17.5C9.01509 17.5 8.03982 17.306 7.12987 16.9291C6.21993 16.5522 5.39314 15.9997 4.6967 15.3033C4.00026 14.6069 3.44781 13.7801 3.0709 12.8701C2.69399 11.9602 2.5 10.9849 2.5 10C2.5 8.01088 3.29018 6.10322 4.6967 4.6967C6.10322 3.29018 8.01088 2.5 10 2.5C11.9891 2.5 13.8968 3.29018 15.3033 4.6967C16.7098 6.10322 17.5 8.01088 17.5 10ZM10 6.875H10.0067V6.88167H10V6.875Z"
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
 * @param {string}  props.name       - Icon name (check, warning, error, info)
 * @param {string}  props.type       - Icon type for color (success, warning, error, info)
 * @param {boolean} props.ariaHidden - Hide from accessibility tree (default: true, auto-set to false if ariaLabel provided)
 * @param {string}  props.ariaLabel  - Accessible label for the icon (automatically sets ariaHidden to false)
 * @param {string}  props.className  - Additional CSS classes
 * @return {JSX.Element|null} The icon element
 */
const Icon = ( { name = 'check', type = '', ariaHidden = true, ariaLabel, className = '' } ) => {
	const iconSvg = icons[ name ];

	if ( ! iconSvg ) {
		return null;
	}

	// Set default type if not provided.
	let resolvedType = type;
	if ( ! type ) {
		switch ( name ) {
			case 'check':
				resolvedType = 'success';
				break;
			case 'warning':
				resolvedType = 'warning';
				break;
			case 'error':
				resolvedType = 'error';
				break;
			case 'info':
				resolvedType = 'info';
				break;
			default:
				resolvedType = '';
		}
	}

	const baseClass = 'edac-icon';
	const typeClass = resolvedType ? `edac-icon--${ resolvedType }` : '';
	const classes = [ baseClass, typeClass, className ].filter( Boolean ).join( ' ' );

	// Auto-set ariaHidden to false if ariaLabel is provided, so the label is announced.
	const resolvedAriaHidden = ariaLabel ? false : ariaHidden;

	const ariaProps = {
		'aria-hidden': resolvedAriaHidden,
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
