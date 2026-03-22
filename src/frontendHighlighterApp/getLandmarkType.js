/**
 * Determines the landmark type of an element.
 *
 * @param {HTMLElement} element The element to check.
 * @return {string} The landmark type (e.g., "Header", "Navigation", "Main").
 */
export function getLandmarkType( element ) {
	// Check explicit ARIA role first
	const role = element.getAttribute( 'role' );
	if ( role ) {
		switch ( role.toLowerCase() ) {
			case 'banner':
				return 'Header';
			case 'navigation':
				return 'Navigation';
			case 'main':
				return 'Main';
			case 'complementary':
				return 'Complementary';
			case 'contentinfo':
				return 'Footer';
			case 'search':
				return 'Search';
			case 'form':
				return 'Form';
			case 'region':
				return 'Region';
			default:
				return role.charAt( 0 ).toUpperCase() + role.slice( 1 );
		}
	}

	// Check semantic HTML elements
	const tagName = element.tagName.toLowerCase();
	switch ( tagName ) {
		case 'header':
			return 'Header';
		case 'nav':
			return 'Navigation';
		case 'main':
			return 'Main';
		case 'aside':
			return 'Complementary';
		case 'footer':
			return 'Footer';
		case 'section': {
			// Check if section has accessible name
			const hasAccessibleName = element.getAttribute( 'aria-label' ) ||
				element.getAttribute( 'aria-labelledby' ) ||
				element.querySelector( 'h1, h2, h3, h4, h5, h6' );
			return hasAccessibleName ? 'Region' : 'Section';
		}
		case 'article':
			return 'Article';
		case 'form': {
			// Check if form has accessible name
			const formHasAccessibleName = element.getAttribute( 'aria-label' ) ||
				element.getAttribute( 'aria-labelledby' );
			return formHasAccessibleName ? 'Form' : 'Form (unlabeled)';
		}
		default:
			return 'Landmark';
	}
}
