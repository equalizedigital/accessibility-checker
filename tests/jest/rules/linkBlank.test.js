import axe from 'axe-core';

beforeAll( async () => {
	const ruleModule = await import( '../../../src/pageScanner/rules/link_target_blank.js' );
	const checkModule = await import( '../../../src/pageScanner/checks/link-target-blank-without-informing.js' );

	axe.configure( {
		rules: [ ruleModule.default ],
		checks: [ checkModule.default ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';

	const style = document.createElement( 'style' );
	style.innerHTML = `
		.hidden { display: none; }
		.invisible { visibility: hidden; }
	`;
	document.head.appendChild( style );
} );

describe( 'Link Target Blank Rule', () => {
	test.each( [
		// ❌ Failing cases - links that open in new tab without informing the user
		{
			name: 'Fails when link has target="_blank" without warning text',
			html: '<a href="https://example.com" target="_blank">Click here</a>',
			shouldPass: false,
		},
		{
			name: 'Fails when link has target="_blank" with no descriptive text',
			html: '<a href="/page" target="_blank">Link</a>',
			shouldPass: false,
		},

		// ✅ Passing cases - links with appropriate warnings
		{
			name: 'Passes when link text contains "new window"',
			html: '<a href="https://example.com" target="_blank">Visit our site (opens in new window)</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when link text contains "new tab"',
			html: '<a href="https://example.com" target="_blank">External link (new tab)</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when link text contains "new document"',
			html: '<a href="https://example.com" target="_blank">Download report (opens new document)</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when aria-label contains "new window"',
			html: '<a href="https://example.com" target="_blank" aria-label="Visit example.com in new window">Visit</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when aria-label contains "new tab"',
			html: '<a href="https://example.com" target="_blank" aria-label="External link opens new tab">External</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when aria-label contains "new document"',
			html: '<a href="https://example.com" target="_blank" aria-label="Download opens new document">Download</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when aria-labelledby references element with "new window"',
			html: `
				<span id="label1">View details in new window</span>
				<a href="https://example.com" target="_blank" aria-labelledby="label1">Details</a>
			`,
			shouldPass: true,
		},
		{
			name: 'Passes when aria-labelledby references element with "new tab"',
			html: `
				<span id="label2">Opens in new tab</span>
				<a href="https://example.com" target="_blank" aria-labelledby="label2">Link</a>
			`,
			shouldPass: true,
		},
		{
			name: 'Passes when aria-labelledby references element with "new document"',
			html: `
				<span id="label3">Download opens new document</span>
				<a href="https://example.com" target="_blank" aria-labelledby="label3">Download</a>
			`,
			shouldPass: true,
		},
		{
			name: 'Passes when image alt text contains "new window"',
			html: '<a href="https://example.com" target="_blank"><img src="icon.png" alt="External site icon - opens in new window" /></a>',
			shouldPass: true,
		},
		{
			name: 'Passes when image alt text contains "new tab"',
			html: '<a href="https://example.com" target="_blank"><img src="icon.png" alt="Link icon - new tab" /></a>',
			shouldPass: true,
		},
		{
			name: 'Passes when image alt text contains "new document"',
			html: '<a href="https://example.com" target="_blank"><img src="icon.png" alt="Document icon - opens new document" /></a>',
			shouldPass: true,
		},

		// ✅ Passing cases - links without target="_blank"
		{
			name: 'Passes when link has no target attribute',
			html: '<a href="https://example.com">Regular link</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when link has target="_self"',
			html: '<a href="https://example.com" target="_self">Same window link</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when element is not a link',
			html: '<button target="_blank">Not a link</button>',
			shouldPass: true,
		},

		// ✅ Passing cases - hidden elements should be handled correctly
		{
			name: 'Hidden link with display:none inline should still be evaluated',
			html: '<a href="https://example.com" target="_blank" style="display:none;">Hidden link</a>',
			shouldPass: false, // Still evaluated because excludeHidden is false for this rule
		},
		{
			name: 'Hidden link with visibility:hidden inline should still be evaluated',
			html: '<a href="https://example.com" target="_blank" style="visibility:hidden;">Invisible link</a>',
			shouldPass: false, // Still evaluated because excludeHidden is false for this rule
		},

		// ✅ Edge cases - mixed case and partial matches
		{
			name: 'Passes with case-insensitive match - NEW WINDOW',
			html: '<a href="https://example.com" target="_blank">Click here (opens in NEW WINDOW)</a>',
			shouldPass: true,
		},
		{
			name: 'Passes with case-insensitive match - New Tab',
			html: '<a href="https://example.com" target="_blank">External link - New Tab</a>',
			shouldPass: true,
		},
		{
			name: 'Passes with case-insensitive match - New Document',
			html: '<a href="https://example.com" target="_blank">PDF download - New Document</a>',
			shouldPass: true,
		},
	] )( '$name', async ( { html, shouldPass } ) => {
		document.body.innerHTML = html;

		const results = await axe.run( document.body, {
			runOnly: [ 'link_blank' ],
		} );

		if ( shouldPass ) {
			expect( results.violations.length ).toBe( 0 );
		} else {
			expect( results.violations.length ).toBeGreaterThan( 0 );
		}
	} );
} );
