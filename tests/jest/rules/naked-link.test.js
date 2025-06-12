import axe from 'axe-core';

// Store the imported modules in a broader scope
let nakedLinkRule;
let linkIsNakedCheck;

beforeAll(async () => {
	// Dynamically import the custom rule and check
	const nakedLinkRuleModule = await import('../../../src/pageScanner/rules/naked-link.js');
	const linkIsNakedCheckModule = await import('../../../src/pageScanner/checks/link-is-naked.js');

	nakedLinkRule = nakedLinkRuleModule.default;
	linkIsNakedCheck = linkIsNakedCheckModule.default;

	// Configure axe with the custom rule and check
	axe.configure({
		rules: [nakedLinkRule],
		checks: [linkIsNakedCheck],
	});
});

beforeEach(() => {
	// Reset the document body before each test
	document.body.innerHTML = '';
});

describe('Naked Link Validation', () => {
	const testCases = [
		// Failing cases
		{
			name: 'should fail for link where text is identical to href',
			html: '<a href="https://example.com">https://example.com</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for link where text is identical to href with surrounding whitespace',
			html: '<a href="https://example.com">  https://example.com  </a>',
			shouldPass: false,
		},
		{
			name: 'should fail for link where text is identical to a relative href',
			html: '<a href="/path/to/page">/path/to/page</a>',
			shouldPass: false,
		},

		// Passing cases
		{
			name: 'should pass for link with descriptive text',
			html: '<a href="https://example.com">Visit Example.com</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with no href attribute',
			html: '<a>This is just an anchor</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link that is empty (handled by empty-link rule)',
			html: '<a href="https://example.com"></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link where href is a partial match to the text',
			html: '<a href="https://example.com">See example.com for details</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link where text is a partial match to the href',
			html: '<a href="https://example.com/more-info">example.com</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with different protocol (http vs https)',
			html: '<a href="https://example.com">http://example.com</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with different subdomain',
			html: '<a href="https://www.example.com">https://example.com</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with mailto: and email address as text',
			html: '<a href="mailto:test@example.com">test@example.com</a>',
			shouldPass: true, // Typically, mailto links are an exception for this kind of rule
		},
		{
			name: 'should pass for link with tel: and phone number as text',
			html: '<a href="tel:+1234567890">+1234567890</a>',
			shouldPass: true, // Similar to mailto, tel links are often exceptions
		}
	];

	testCases.forEach((testCase) => {
		test(testCase.name, async () => {
			document.body.innerHTML = testCase.html;

			const results = await axe.run(document.body, {
				runOnly: ['naked_link'], // Run only our new rule
			});

			if (testCase.shouldPass) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
			}
		});
	});
});
