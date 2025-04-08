export default {
	id: 'video_present',
	selector: 'video, [role="video"], iframe[src*="youtube"], iframe[src*="vimeo"], iframe[src*="dailymotion"], object[type*="video"]',
	excludeHidden: false,
	tags: [
		'wcag2a',
		'wcag121',
		'wcag122',
		'wcag123',
		'cat.time-and-media',
		'cat.sensory',
	],
	metadata: {
		description: 'Identifies presence of video content that may require accessibility features',
		help: 'Video content should have appropriate alternatives like captions and audio descriptions',
		impact: 'serious',
	},
	all: [],
	any: [],
	none: [ 'video_element_present' ],
};
