import axe from 'axe-core';

// Store the imported modules in a broader scope
let nakedLinkRule;
let linkIsNakedCheck;

beforeAll( async () => {
	// Dynamically import the custom rule and check
	const nakedLinkRuleModule = await import( '../../../src/pageScanner/rules/link-naked.js' );
	const linkIsNakedCheckModule = await import( '../../../src/pageScanner/checks/link-is-naked.js' );

	nakedLinkRule = nakedLinkRuleModule.default;
	linkIsNakedCheck = linkIsNakedCheckModule.default;

	// Configure axe with the custom rule and check
	axe.configure( {
		rules: [ nakedLinkRule ],
		checks: [ linkIsNakedCheck ],
	} );
} );

beforeEach( () => {
	// Reset the document body before each test
	document.body.innerHTML = '';
} );

describe( 'Naked Link Validation', () => {
	const testCases = [
		// Failing cases
		{
			name: 'should fail when text is identical to href',
			html: '<a href="https://example.com">https://example.com</a>',
			shouldPass: false,
		},
		{
			name: 'should fail when text is identical to href with surrounding whitespace',
			html: '<a href="https://example.com">  https://example.com  </a>',
			shouldPass: false,
		},
		{
			name: 'should fail when text is identical to a relative href',
			html: '<a href="/path/to/page">/path/to/page</a>',
			shouldPass: false,
		},
		{
			name: 'should fail when text has trailing slash difference',
			html: '<a href="https://example.com/path">https://example.com/path/</a>',
			shouldPass: false,
		},
		{
			name: 'should fail when text has http and href has https',
			html: '<a href="https://example.com/path">http://example.com/path</a>',
			shouldPass: false,
		},
		{
			name: 'should fail when text has different www subdomain',
			html: '<a href="https://www.example.com/path">https://example.com/path</a>',
			shouldPass: false,
		},
		{
			name: 'should fail when text omits query string',
			html: '<a href="https://example.com/path?utm_source=newsletter">https://example.com/path</a>',
			shouldPass: false,
		},
		{
			name: 'should fail when text omits hash fragment',
			html: '<a href="https://example.com/path#section-1">https://example.com/path</a>',
			shouldPass: false,
		},
		{
			name: 'should fail when text has different protocol (http vs https)',
			html: '<a href="https://example.com">http://example.com</a>',
			shouldPass: false,
		},
		{
			name: 'should fail when text has different subdomain with protocol',
			html: '<a href="https://www.example.com">https://example.com</a>',
			shouldPass: false,
		},
		{
			name: 'should fail when mailto link has email address as text',
			html: '<a href="mailto:test@example.com">test@example.com</a>',
			shouldPass: false,
		},
		{
			name: 'should fail when tel link has phone number as text',
			html: '<a href="tel:+1234567890">+1234567890</a>',
			shouldPass: false,
		},
		{
			name: 'should fail when naked URL appears in paragraph context',
			html: '<p>Read the article, Ambiguous Links, on the The Admin Bar: <a href="https://theadminbar.com/accessibility-weekly/ambiguous-links/">https://theadminbar.com/accessibility-weekly/ambiguous-links/</a></p>',
			shouldPass: false,
		},
		{
			name: 'should fail when link has other words plus naked URL',
			html: '<p><a href="https://equalizedigital.com/accessibility-checker/">Learn more: https://equalizedigital.com/accessibility-checker/</a></p>',
			shouldPass: false,
		},
		{
			name: 'should fail when link has naked URL with punctuation',
			html: '<p>Read our documentation at <a href="https://equalizedigital.com/accessibility-checker/documentation/">https://equalizedigital.com/accessibility-checker/documentation.</a></p>',
			shouldPass: false,
		},
		{
			name: 'should fail when link to file has naked URL as text',
			html: '<p> <a href="https://a11ycheckrules.wpenginepowered.com/wp-content/uploads/2020/11/accessible-pdf-example.pdf">https://a11ycheckrules.wpenginepowered.com/wp-content/uploads/2020/11/accessible-pdf-example.pdf</a></p>',
			shouldPass: false,
		},
		// Passing cases
		{
			name: 'should pass when text has no protocol but otherwise matches href',
			html: '<a href="https://example.com/path">example.com/path</a>',
			shouldPass: true,
		},
		{
			name: 'should pass when link has descriptive text',
			html: '<a href="https://example.com">Visit Example.com</a>',
			shouldPass: true,
		},
		{
			name: 'should pass when link has no href attribute',
			html: '<a>This is just an anchor</a>',
			shouldPass: true,
		},
		{
			name: 'should pass when link is empty (handled by empty-link rule)',
			html: '<a href="https://example.com"></a>',
			shouldPass: true,
		},
		{
			name: 'should pass when href is partial match to descriptive text',
			html: '<a href="https://example.com">See example.com for details</a>',
			shouldPass: true,
		},
		{
			name: 'should pass when text is bare domain partial match to href',
			html: '<a href="https://example.com/more-info">example.com</a>',
			shouldPass: true,
		},
		{
			name: 'should pass when link has descriptive anchor text in paragraph',
			html: '<p>Read the article, <a href="https://theadminbar.com/accessibility-weekly/ambiguous-links/">Ambiguous Links</a>, on The Admin Bar.</p>',
			shouldPass: true,
		},
		{
			name: 'should pass when link has domain without protocol or path',
			html: '<p>You can download WordPress free from <a href="https://wordpress.org">WordPress.org</a>. </p>',
			shouldPass: true,
		},
		{
			name: 'should pass when link has domain without protocol with punctuation',
			html: '<p>If it seems overwhelming to download WordPress from <a href="https://wordpress.org">WordPress.org,</a> get hosted WordPress at <a href="https://wordpress.com">WordPress.com.</a></p>',
			shouldPass: true,
		},
		{
			name: 'should pass when link has domain and path without protocol',
			html: '<p>Learn more at <a href="https://equalizedigital.com/accessibility-checker">equalizedigital.com/accessibility-checker</a>.</p>',
			shouldPass: true,
		},
		{
			name: 'should pass when link has domain and single path segment without protocol',
			html: '<p>Don’t miss upcoming meetups. Register to attend at <a href="https://equalizedigital.com/meetup">equalizedigital.com/meetup</a>.</p>',
			shouldPass: true,
		},
		{
			name: 'should pass when link in button block has descriptive text',
			html: '<div class="wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex">\n<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://a11ycheckrules.wpenginepowered.com/wp-content/uploads/2020/11/accessible-pdf-example.pdf">Download Accessible PDF</a></div>\n</div>',
			shouldPass: true,
		},
		{
			name: 'should pass when descriptive text contains domain name',
			html: '<a href="https://example.com/path">Visit example.com for details</a>',
			shouldPass: true,
		},
	];

	testCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			document.body.innerHTML = testCase.html;

			const results = await axe.run( document.body, {
				runOnly: [ 'link_naked' ], // Run only our new rule
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
			}
		} );
	} );
} );
