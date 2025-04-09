import axe from 'axe-core';

beforeAll( async () => {
	// Dynamically import the custom rule
	const emptyLinkRuleModule = await import( '../../../src/pageScanner/rules/empty-link.js' );
	const emptyLinkCheckModule = await import( '../../../src/pageScanner/checks/link-is-empty.js' );
	const emptyLinkRule = emptyLinkRuleModule.default;
	const emptyLinkCheck = emptyLinkCheckModule.default;

	// Configure axe with the custom rule
	axe.configure( {
		rules: [ emptyLinkRule ],
		checks: [ emptyLinkCheck ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Empty Link Validation', () => {
	const testCases = [
		// Passing cases
		{
			name: 'should pass for link with visible text',
			html: '<a href="https://example.com">Click here</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with aria-label',
			html: '<a href="https://example.com" aria-label="Visit our homepage"></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with title',
			html: '<a href="https://example.com" title="Visit our homepage"></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with an image and alt text',
			html: '<a href="https://example.com"><img src="icon.png" alt="Home icon"></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with nested span containing text',
			html: '<a href="https://example.com"><span>Go to homepage</span></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with input that has value',
			html: '<a href="https://example.com"><input type="button" value="Submit"></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with i tag that has title',
			html: '<a href="https://example.com"><i class="fa fa-home" title="Home"></i></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with i tag that has aria-label',
			html: '<a href="https://example.com"><i class="fa fa-home" aria-label="Home"></i></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with SVG that has title',
			html: '<a href="https://example.com"><svg><title>Home</title></svg></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with aria-labelledby',
			html: '<span id="label-text">Home</span><a href="https://example.com" aria-labelledby="label-text"></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with aria-hidden and text content for sighted users',
			html: '<a href="https://example.com"><span aria-hidden="true">✓</span> Approved</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link without href (anchor only)',
			html: '<a id="top">Back to top</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for empty link with name attribute',
			html: '<a href="https://example.com" name="anchor"></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with multiple nested elements, some with text',
			html: '<a href="https://example.com"><span>Hello</span> <div>World</div></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with aria-label and nested text (aria-label should take precedence)',
			html: '<a href="https://example.com" aria-label="Custom label">Nested Text</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with role="img" and aria-label',
			html: '<a href="https://example.com" role="img" aria-label="Descriptive text"></a>',
			shouldPass: true,
		},
		{
			name: 'should fail for link with empty title attribute',
			html: '<a href="https://example.com" title=""></a>',
			shouldPass: false,
		},
		{
			name: 'should pass for link with decorative image',
			html: '<a href="https://example.com"><img src="icon.png" alt=""></a>',
			shouldPass: false,
		},
		{
			name: 'should pass for link with screen reader-only content',
			html: '<a href="https://example.com"><span class="screen-reader-text">Skip to content</span></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with aria-hidden="true"',
			html: '<a href="https://example.com" aria-hidden="true"></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with valid accessible name but also includes elements that would otherwise cause a failure',
			html: '<a href="https://example.com" aria-label="Valid"><i class="fa fa-star"></i></a>',
			shouldPass: true,
		},

		// Failing cases
		{
			name: 'should fail for empty link with id attribute',
			html: '<a href="https://example.com" id="anchor"></a>',
			shouldPass: false,
		},
		{
			name: 'should fail for empty link with href but no text or attributes',
			html: '<a href="https://example.com"></a>',
			shouldPass: false,
		},
		{
			name: 'should fail for link with only whitespace',
			html: '<a href="https://example.com">   </a>',
			shouldPass: false,
		},
		{
			name: 'should fail for link with only non-breaking spaces',
			html: '<a href="https://example.com">&nbsp;&nbsp;&nbsp;</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for link with only hyphens',
			html: '<a href="https://example.com">---</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for link with only underscores',
			html: '<a href="https://example.com">___</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for link with an image but no alt text',
			html: '<a href="https://example.com"><img src="icon.png" alt=""></a>',
			shouldPass: false,
		},
		{
			name: 'should fail for link with nested empty elements',
			html: '<a href="https://example.com"><span></span><div></div></a>',
			shouldPass: false,
		},
		{
			name: 'should fail for link with font icon without accessibility attributes',
			html: '<a href="https://example.com"><i class="fa fa-star"></i></a>',
			shouldPass: false,
		},
		{
			name: 'should fail for link with complex nested empty elements',
			html: '<a href="https://example.com"><span></span><div><i class="icon"></i><img src="img.jpg" alt=""></div></a>',
			shouldPass: false,
		},
		{
			name: 'should fail for link with input without value',
			html: '<a href="https://example.com"><input type="button"></a>',
			shouldPass: false,
		},
		{
			name: 'should fail for link with empty aria-label',
			html: '<a href="https://example.com" aria-label=""></a>',
			shouldPass: false,
		},
		{
			name: 'should fail for link with aria-labelledby referencing empty element',
			html: '<span id="empty-label"></span><a href="https://example.com" aria-labelledby="empty-label"></a>',
			shouldPass: false,
		},
		{
			name: 'should fail for duplicate empty links on a page',
			html: '<a href="https://example.com"><img src="icon1.png" alt=""></a><a href="https://example.com"><img src="icon2.png" alt=""></a>',
			shouldPass: false,
		},
		{
			name: 'should fail for link with SVG without title or accessible name',
			html: '<a href="https://example.com"><svg viewBox="0 0 100 100"></svg></a>',
			shouldPass: false,
		},
		{
			name: 'should fail for link with aria-describedby referencing non-existent element',
			html: '<a href="https://example.com" aria-describedby="non-existent"></a>',
			shouldPass: false,
		},
		{
			name: 'should fail for link with aria-describedby referencing empty element',
			html: '<span id="empty-desc"></span><a href="https://example.com" aria-describedby="empty-desc"></a>',
			shouldPass: false,
		},
	];

	testCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			document.body.innerHTML = testCase.html;

			const results = await axe.run( document.body, {
				runOnly: [ 'empty_link' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
			}
		} );
	} );
} );
