/**
 * This is a modified version of the original rule from axe-core library.
 *
 * The matches is changed to remove 'image' as a bail condition. A new image alt check is added. The "non-empty-placeholder" check is removed.
 *
 * original rule: https://github.com/dequelabs/axe-core/blob/develop/lib/rules/label.json
 */

export default {
	id: 'label',
	impact: 'critical',
	selector: 'input, textarea',
	matches: ( node, virtualNode ) => {
		if ( virtualNode.props.nodeName !== 'input' || virtualNode.hasAttr( 'type' ) === false ) {
			return true;
		}
		const type2 = virtualNode.attr( 'type' ).toLowerCase();
		// 'image' is a removed value compared to the original `label-matches` matcher.
		return [ 'hidden', 'button', 'submit', 'reset' ].includes( type2 ) === false;
	},
	tags: [
		'cat.forms',
		'wcag2a',
		'wcag412',
		'section508',
		'section508.22.n',
		'TTv5',
		'TT5.c',
		'EN-301-549',
		'EN-9.4.1.2',
		'ACT',
	],
	actIds: [ 'e086e5' ],
	metadata: {
		description: 'Ensure every form element has a label',
		help: 'Form elements must have labels',
	},
	all: [],
	any: [
		'implicit-label',
		'explicit-label',
		'aria-label',
		'aria-labelledby',
		'non-empty-title',
		'presentational-role',
		'image_input_has_alt', // this is added as a custom check. "non-empty-placeholder" was removed.
	],
	none: [ 'hidden-explicit-label' ],
};
