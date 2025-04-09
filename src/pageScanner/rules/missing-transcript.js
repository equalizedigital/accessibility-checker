/**
 * Check to detect the presence of a transcript for media elements.
 * Ensures that audio, video, or iframe elements are accompanied by a transcript or a link to one.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if a transcript is present, false otherwise.
 */

export default {
	id: 'missing_transcript',
	selector: 'audio, video, iframe, a',
	excludeHidden: false,
	tags: [
		'wcag2a',
		'wcag122',
		'cat.time-and-media',
	],
	metadata: {
		description: 'Media content should be accompanied by a text transcript',
		help: 'Ensure audio or video content includes a nearby transcript or transcript link',
		impact: 'serious',
	},
	all: [],
	any: [ 'transcript_missing' ],
	none: [],
};
