/**
 * Check for links that open in a new tab without informing the user.
 */

export default {
	id: 'link_blank',
	selector: 'a[target="_blank"]',
	excludeHidden: false,
	tags: [],
	metadata: {
		description: 'Links that open in a new tab should inform the user.',
		help: 'Links that open in a new tab should inform the user. This is important for users who rely on screen readers, as they may not realize that a new tab has opened.',
	},
	all: [],
	any: [],
	none: [ 'link_target_blank' ],
};
