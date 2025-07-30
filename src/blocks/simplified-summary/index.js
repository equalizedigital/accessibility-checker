import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( 'accessibility-checker/simplified-summary', {
	title: __( 'Simplified Summary', 'accessibility-checker' ),
	icon: 'excerpt-view',
	category: 'widgets',
	description: __( 'Displays the Simplified Summary for the post.', 'accessibility-checker' ),
	supports: {
		html: false,
	},
	edit: () => {
		return wp.element.createElement( 'p', null, __( 'Simplified Summary will display on the frontend.', 'accessibility-checker' ) );
	},
	save: () => null,
} );
