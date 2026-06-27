import axe from 'axe-core';

beforeAll( async () => {
	const linkMsOfficeFileRuleModule = await import( '../../../src/pageScanner/rules/link-ms-office-file.js' );
	const alwaysFailCheckModule = await import( '../../../src/pageScanner/checks/always-fail.js' );

	axe.configure( {
		rules: [ linkMsOfficeFileRuleModule.default ],
		checks: [ alwaysFailCheckModule.default ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Link to MS Office File Rule', () => {
	const testCases = [
		// Passing cases — not MS Office links
		{
			name: 'should pass for a link to an HTML page',
			html: '<a href="/page.html">Visit our page</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for a link to a PDF',
			html: '<a href="/report.pdf">Download PDF</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for a link with no href',
			html: '<a>Anchor without href</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for a link with empty href',
			html: '<a href="">Empty href</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for a URL with "doc" in a path segment but not as extension',
			html: '<a href="/documentation/guide.html">Documentation Guide</a>',
			shouldPass: true,
		},
		{
			// The selector matches .docx? and .docx# but not .docx& (second query param).
			// Adding a[href*=".docx&"] risks false positives (e.g. /?q=docx&sort=name).
			// This case is a known gap — tracked for a future rule selector update.
			name: 'does not flag an Office file served via a query parameter followed by & (known selector gap)',
			html: '<a href="/download?file=report.docx&version=2">Download Report</a>',
			shouldPass: true,
		},

		// Failing cases — Word documents
		{
			name: 'should fail for a link to a .doc file',
			html: '<a href="/report.doc">Download Report</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for a link to a .docx file',
			html: '<a href="https://example.com/plan.docx">Download a Sample Plan</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for a .DOC file (uppercase)',
			html: '<a href="/report.DOC">Report</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for a .docx file with query parameters',
			html: '<a href="/document.docx?download=true">Download Document</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for a .docx file with an anchor',
			html: '<a href="/document.docx#section">Document Section</a>',
			shouldPass: false,
		},

		// Failing cases — Excel spreadsheets
		{
			name: 'should fail for a link to a .xls file',
			html: '<a href="/data.xls">Download Spreadsheet</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for a link to a .xlsx file',
			html: '<a href="/budget.xlsx">Download Budget</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for a .XLSX file (uppercase)',
			html: '<a href="/budget.XLSX">Budget Spreadsheet</a>',
			shouldPass: false,
		},

		// Failing cases — PowerPoint presentations
		{
			name: 'should fail for a link to a .ppt file',
			html: '<a href="/slides.ppt">Download Presentation</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for a link to a .pptx file',
			html: '<a href="/presentation.pptx">Download Slides</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for a link to a .pps file',
			html: '<a href="/slideshow.pps">Download Slideshow</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for a link to a .ppsx file',
			html: '<a href="/slideshow.ppsx">Download Slideshow</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for a .PPTX file (uppercase)',
			html: '<a href="/presentation.PPTX">Presentation</a>',
			shouldPass: false,
		},
	];

	testCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			document.body.innerHTML = testCase.html;

			const results = await axe.run( document.body, {
				runOnly: [ 'link_ms_office_file' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
				expect( results.violations[ 0 ].id ).toBe( 'link_ms_office_file' );
			}
		} );
	} );
} );
