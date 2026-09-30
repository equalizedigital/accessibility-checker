import axe from 'axe-core';

beforeAll( async () => {
	const linkPdfRuleModule = await import( '../../../src/pageScanner/rules/link-pdf.js' );
	const alwaysFailCheckModule = await import( '../../../src/pageScanner/checks/always-fail.js' );

	axe.configure( {
		rules: [ linkPdfRuleModule.default ],
		checks: [ alwaysFailCheckModule.default ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Link to PDF Rule', () => {
	const testCases = [
		// Passing cases — not PDF links
		{
			name: 'should pass for a link to an HTML page',
			html: '<a href="/page.html">Visit our page</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for a link to a Word document',
			html: '<a href="/report.docx">Download report</a>',
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
			name: 'should pass for a link to an image',
			html: '<a href="/photo.jpg">View photo</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for a URL that contains "pdf" in a directory name but not as extension',
			html: '<a href="/pdf-resources/guide.html">PDF Resources</a>',
			shouldPass: true,
		},
		{
			// The selector matches .pdf? and .pdf# but not .pdf& (second query param).
			// Adding a[href*=".pdf&"] risks false positives (e.g. /?q=compare-pdf&sort=name).
			// This case is a known gap — tracked for a future rule selector update.
			name: 'does not flag a PDF served via a query parameter followed by & (known selector gap)',
			html: '<a href="/download?file=document.pdf&version=2">Download PDF</a>',
			shouldPass: true,
		},

		// Failing cases — PDF links
		{
			name: 'should fail for a link ending in .pdf',
			html: '<a href="https://example.com/brochure.pdf">Download our Brochure</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for a link ending in .PDF (uppercase)',
			html: '<a href="/report.PDF">Download Report</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for a PDF link with query parameters',
			html: '<a href="/document.pdf?version=2">Versioned Document</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for a PDF link with uppercase extension and query params',
			html: '<a href="/document.PDF?download=true">Download PDF</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for a PDF link with a URL anchor',
			html: '<a href="/report.pdf#page=1">Report Page 1</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for a PDF link with uppercase extension and anchor',
			html: '<a href="/report.PDF#section">Report Section</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for a relative PDF link',
			html: '<a href="files/annual-report.pdf">Annual Report</a>',
			shouldPass: false,
		},
	];

	testCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			document.body.innerHTML = testCase.html;

			const results = await axe.run( document.body, {
				runOnly: [ 'link_pdf' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
				expect( results.violations[ 0 ].id ).toBe( 'link_pdf' );
			}
		} );
	} );
} );
