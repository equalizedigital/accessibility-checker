/**
 * Check that a paragraph is NOT empty.
 *
 * @param {Node} node The paragraph element to evaluate.
 * @return {boolean} Returns true if the paragraph has content, false if empty.
 */

import { __ } from '@wordpress/i18n';

export default {
	id: 'label_is_empty',
	evaluate: ( node ) => {
		if ( 'label' !== node.tagName.toLowerCase() ) {
			return false;
		}

		// get the text content of the label
		const textContent = node.textContent;

		// remove any asterisks
		textContent.replace( /\*/gi, '' );

		// remove the translated 'required' string
		const translatedRequiredString = __( 'required', 'accessibility-checker' );
		textContent.replace( translatedRequiredString, '' );

		// if the label is empty, return true
		if ( textContent === '' ) {
			return true;
		}
	},
};
