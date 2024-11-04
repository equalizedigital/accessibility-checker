/**
 * Detects linked PDFs.
 */
export default {
	id: 'link_pdf',
	selector: 'a[href$=".pdf"], a[href$=".PDF"], a[href*=".pdf?"], a[href*=".PDF?"], a[href*=".pdf#"], a[href*=".PDF#"]',
	excludeHidden: false,
	tags: [
		'cat.custom',
	],
	metadata: {
		description: 'Links to PDFs typically should be checked.',
	},
	all: [],
	any: [ 'always-fail' ],
	none: [],
};
