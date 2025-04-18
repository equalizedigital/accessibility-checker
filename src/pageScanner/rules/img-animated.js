/**
 * Detect animated gifs and webps as well as common animated gif services.
 */
export default {
	id: 'img_animated',
	// Selects both images and iframes that might contain animations
	selector: 'img[src], iframe[src]',
	excludeHidden: false,
	tags: [
		'wcag2aa',
		'wcag222',
		'cat.sensory-and-visual-cues',
		'best-practice',
		'flashing',
	],
	metadata: {
		description: 'Identifies animated content that may require user controls',
		help: 'Animated GIFs, WebPs, or embedded animations should be limited to less than 5 seconds or provide user controls to pause/stop',
		impact: 'serious',
		issue: {
			type: 'warning',
			message: 'Animated content might need controls for accessibility compliance',
			tips: [
				'Limit animations to less than 5 seconds',
				'Add controls to pause/stop animations',
				'Consider using video elements with controls instead of GIFs',
				'Avoid flashing content that could trigger seizures',
			],
		},
	},
	all: [],
	any: [],
	none: [ 'img_animated_check' ],
};
