import axe from 'axe-core';

beforeAll( async () => {
	// Dynamically import the custom rule
	const emptyHeadingRuleModule = await import( '../../../src/pageScanner/rules/empty-heading-tag.js' );
	const emptyHeadingCheckModule = await import( '../../../src/pageScanner/checks/heading-is-empty.js' );
	const emptyHeadingRule = emptyHeadingRuleModule.default;
	const emptyHeadingCheck = emptyHeadingCheckModule.default;

	// Configure axe with the custom rule
	axe.configure( {
		rules: [ emptyHeadingRule ],
		checks: [ emptyHeadingCheck ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Empty Heading Validation', () => {
	const testCases = [
		// Passing cases
		{
			name: 'should pass for heading with visible text',
			html: '<h1>Page Title</h1>',
			shouldPass: true,
		},
		{
			name: 'should pass for heading with aria-label',
			html: '<h2 aria-label="Section Title"></h2>',
			shouldPass: true,
		},
		{
			name: 'should pass for heading with an image and alt text',
			html: '<h3><img src="icon.png" alt="Section Icon"></h3>',
			shouldPass: true,
		},
		{
			name: 'should pass for heading with nested span containing text',
			html: '<h4><span>Section Title</span></h4>',
			shouldPass: true,
		},
		{
			name: 'should pass for heading with deeply nested content',
			html: '<h5><div><span><strong>Important Information</strong></span></div></h5>',
			shouldPass: true,
		},
		{
			name: 'should pass for heading with HTML entities',
			html: '<h6>&hearts; Special Section</h6>',
			shouldPass: true,
		},
		{
			name: 'should pass for heading with emoji only',
			html: '<h1>👍</h1>',
			shouldPass: true,
		},
		{
			name: 'should pass for heading with special characters',
			html: '<h2>§ Legal Section</h2>',
			shouldPass: true,
		},
		{
			name: 'should pass for heading with SVG that has title',
			html: '<h3><svg><title>Visual Section</title></svg></h3>',
			shouldPass: true,
		},
		{
			name: 'should pass for heading with mixed content',
			html: '<h4><img src="icon.png" alt=""> Visible Text</h4>',
			shouldPass: true,
		},
		{
			name: 'should pass for heading with aria-labelledby',
			html: '<span id="label-text">Hidden Title</span><h1 aria-labelledby="label-text"></h1>',
			shouldPass: true,
		},
		// Edge cases
		{
			name: 'should pass when heading contains only punctuation',
			html: '<h1>...</h1>',
			shouldPass: true,
		},
		{
			name: 'should pass when heading has content but role is changed',
			html: '<h1 role="presentation">No Longer a Heading</h1>',
			shouldPass: true,
		},
		{
			name: 'should pass when heading has content but is aria-hidden',
			html: '<h1 aria-hidden="true">Hidden Heading</h1>',
			shouldPass: true,
		},

		// Failing cases
		{
			name: 'should fail for empty heading with no text or attributes',
			html: '<h1></h1>',
			shouldPass: false,
		},
		{
			name: 'should fail for heading with only whitespace',
			html: '<h2>   </h2>',
			shouldPass: false,
		},
		{
			name: 'should fail for heading with only non-breaking spaces',
			html: '<h3>&nbsp;&nbsp;&nbsp;</h3>',
			shouldPass: false,
		},
		{
			name: 'should fail for heading with only hyphens',
			html: '<h4>---</h4>',
			shouldPass: false,
		},
		{
			name: 'should fail for heading with only underscores',
			html: '<h5>___</h5>',
			shouldPass: false,
		},
		{
			name: 'should fail for heading with an image but no alt text',
			html: '<h6><img src="icon.png" alt=""></h6>',
			shouldPass: false,
		},
		{
			name: 'should fail for heading with nested empty elements',
			html: '<h1><span></span><div></div></h1>',
			shouldPass: false,
		},
		{
			name: 'should fail for heading with only whitespace in nested elements',
			html: '<h2><span>   </span></h2>',
			shouldPass: false,
		},
		{
			name: 'should fail for heading with SVG that has no accessible name',
			html: '<h3><svg viewBox="0 0 100 100"></svg></h3>',
			shouldPass: false,
		},
		{
			name: 'should fail for heading with font icon without accessibility attributes',
			html: '<h4><i class="fa fa-star"></i></h4>',
			shouldPass: false,
		},
		{
			name: 'should fail for heading with complex nested empty elements',
			html: '<h5><span></span><div><i class="icon"></i><img src="img.jpg" alt=""></div></h5>',
			shouldPass: false,
		},
		{
			name: 'should fail for heading with empty aria-label',
			html: '<h6 aria-label=""></h6>',
			shouldPass: false,
		},
		{
			name: 'should fail for heading with aria-labelledby referencing empty element',
			html: '<span id="empty-label"></span><h1 aria-labelledby="empty-label"></h1>',
			shouldPass: false,
		},
		// Edge cases
		{
			name: 'should fail when heading contains only different whitespace characters',
			html: '<h1>\n\t</h1>',
			shouldPass: false,
		},
		{
			name: 'should fail when heading content is only in template element',
			html: '<h1><template>Hidden Content</template></h1>',
			shouldPass: false,
		},
		{
			name: 'should fail when heading has only CSS-generated content',
			html: '<h1 class="css-generated"></h1>',
			css: '.css-generated::before { content: "Generated"; }',
			shouldPass: false,
		},
	];

	testCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			document.body.innerHTML = testCase.html;
			if ( testCase.css ) {
				const style = document.createElement( 'style' );
				style.textContent = testCase.css;
				document.head.appendChild( style );
			}

			const results = await axe.run( document.body, {
				runOnly: [ 'empty_heading_tag' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
			}
		} );
	} );
} );
