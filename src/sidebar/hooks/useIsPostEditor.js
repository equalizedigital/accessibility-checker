/**
 * Hook to check if we're in the post editor context
 */

import { useSelect } from '@wordpress/data';

/**
 * Hook to check if we're in the post editor context
 *
 * @return {boolean} True if in post editor, false otherwise
 */
export const useIsPostEditor = () => {
	return useSelect(
		( select ) => {
			// Check if we have the editor store (available in both post and site editor).
			const editorStore = select( 'core/editor' );
			if ( ! editorStore ) {
				return false;
			}
			// In post editor, we'll have a current post type, other contexts we won't.
			const postType = editorStore.getCurrentPostType?.();
			return postType !== null && postType !== undefined;
		},
		[],
	);
};

