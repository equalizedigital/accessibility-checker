import { isElementVisible } from '../helpers/helpers';

export default {
	id: 'image_map_missing_alt_text',
	selector: 'map area',
	matches( node ) {
		// Don't check anything hidden with aria-hidden.
		if ( node.closest( '[aria-hidden]' ) ) {
			return false;
		}

		// Dheck if the area or any of the parents are hidden by css.
		return isElementVisible( node );
	},
	excludeHidden: false,
	tags: [ 'cat.text-alternatives', 'wcag2a', 'wcag111' ],
	metadata: {
		description: 'Ensures all area elements in image maps have alt text',
		help: 'Area elements in image maps must have alternative text',
	},
	all: [ 'has_non_empty_alt' ], // Ensure all area elements pass the has_alt check
	any: [],
	none: [],
};
