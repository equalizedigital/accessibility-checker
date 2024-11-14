/**
 * Detects linked MS Office files.
 */

const msOfficeFileExtensions = [ '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx', '.pps', '.ppsx' ];

// This generates a very long list of selectors, 6 in total for each extension: extension at the end,
// extension within having query vars, extension within having #anchors plus uppercase versions of the
// file extension for each one of these cases. It is still quicker that iterating through all the links
// on the page and checking the href in a matches.
const selectorString = msOfficeFileExtensions.map( ( extension ) => `a[href$="${ extension }"], a[href$="${ extension.toUpperCase() }"], a[href*="${ extension }?"], a[href*="${ extension.toUpperCase() }?"], a[href*="${ extension }#"], a[href*="${ extension.toUpperCase() }#"]` ).join( ', ' );

export default {
	id: 'link_ms_office_file',
	selector: selectorString,
	excludeHidden: false,
	tags: [
		'cat.custom',
	],
	metadata: {
		description: 'Links to MS Office documents typically should be checked.',
	},
	all: [],
	any: [ 'always-fail' ],
	none: [],
};
