import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';

/**
 * The block is dynamic: metadata comes from block.json server-side and the
 * front end output is produced by a PHP render callback, so save returns null.
 */
registerBlockType( 'edac/simplified-summary', {
	edit: Edit,
	save: () => null,
} );
