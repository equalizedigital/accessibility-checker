/**
 * Rule to detect the presence of video content that may require accessibility features.
 * This rule works with the video_detected check to identify various video elements
 * that might need captions, audio descriptions, or other accessibility enhancements.
 */

export default {
	id: 'video_present',
	selector: 'video, iframe, object, source, [src]:not(script), [role]',
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
	none: [ 'is_video_detected' ],
};
