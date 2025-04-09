import axe from 'axe-core';

beforeAll( async () => {
	// Dynamically import the custom rule
	const emptyButtonRuleModule = await import( '../../../src/pageScanner/rules/empty-button.js' );
	const emptyButtonCheckModule = await import( '../../../src/pageScanner/checks/button-is-empty.js' );
	const emptyButtonRule = emptyButtonRuleModule.default;
	const emptyButtonCheck = emptyButtonCheckModule.default;

	// Configure axe with the custom rule
	axe.configure( {
		rules: [ emptyButtonRule ],
		checks: [ emptyButtonCheck ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Empty Button Validation', () => {
	const testCases = [
		// Passing cases
		{
			name: 'should pass for button with visible text',
			html: '<button>Click Me</button>',
			shouldPass: true,
		},
		{
			name: 'should pass for button with aria-label',
			html: '<button aria-label="Submit"></button>',
			shouldPass: true,
		},
		{
			name: 'should pass for button with title attribute',
			html: '<button title="Submit"></button>',
			shouldPass: true,
		},
		{
			name: 'should pass for button with an image and alt text',
			html: '<button><img src="icon.png" alt="Submit"></button>',
			shouldPass: true,
		},
		// Additional passing cases - Edge cases
		{
			name: 'should pass for button with nested span containing text',
			html: '<button><span>Click Me</span></button>',
			shouldPass: true,
		},
		{
			name: 'should pass for button with deeply nested content',
			html: '<button><div><span><strong>Submit</strong></span></div></button>',
			shouldPass: true,
		},
		{
			name: 'should pass for button with HTML entities',
			html: '<button>&hearts; Like</button>',
			shouldPass: true,
		},
		{
			name: 'should pass for button with aria-labelledby',
			html: '<span id="label-text">Submit Form</span><button aria-labelledby="label-text"></button>',
			shouldPass: true,
		},
		{
			name: 'should pass for div with role="button" and text',
			html: '<div role="button">Click Me</div>',
			shouldPass: true,
		},
		{
			name: 'should pass for input button with value="0"',
			html: '<input type="button" value="0">',
			shouldPass: true,
		},
		{
			name: 'should pass for button with SVG that has title',
			html: '<button><svg><title>Submit</title></svg></button>',
			shouldPass: true,
		},
		{
			name: 'should pass for button with font icon that has aria-label',
			html: '<button><i class="fa fa-search" aria-label="Search"></i></button>',
			shouldPass: true,
		},
		{
			name: 'should pass for button with mixed content (text and image)',
			html: '<button><img src="icon.png" alt=""> Submit</button>',
			shouldPass: true,
		},
		{
			name: 'should pass for button with aria-hidden="true" as hidden items are not checked',
			html: '<button aria-hidden="true"></button>',
			shouldPass: true,
		},
		{
			name: 'should pass for button with aria-description',
			html: '<button aria-description="This button submits the form"></button>',
			shouldPass: true,
		},
		{
			name: 'should pass for button with aria-describedby',
			html: '<span id="desc-text">This button submits the form</span><button aria-describedby="desc-text"></button>',
			shouldPass: true,
		},

		// Failing cases
		{
			name: 'should fail for button with no text, aria-label, or title',
			html: '<button></button>',
			shouldPass: false,
		},
		{
			name: 'should fail for button with only whitespace text',
			html: '<button>   </button>',
			shouldPass: false,
		},
		{
			name: 'should fail for button with an image but no alt text',
			html: '<button><img src="icon.png" alt=""></button>',
			shouldPass: false,
		},
		{
			name: 'should fail for input button with no value attribute',
			html: '<input type="button">',
			shouldPass: false,
		},
		// Additional failing cases - Edge cases
		{
			name: 'should fail for button with nested empty elements',
			html: '<button><span></span><div></div></button>',
			shouldPass: false,
		},
		{
			name: 'should fail for button with only whitespace in nested elements',
			html: '<button><span>   </span></button>',
			shouldPass: false,
		},
		{
			name: 'should fail for div with role="button" but no accessible name',
			html: '<div role="button"></div>',
			shouldPass: false,
		},
		{
			name: 'should fail for button with emoji only',
			html: '<button>👍</button>',
			shouldPass: false,
		},
		{
			name: 'should fail for button with SVG that has no accessible name',
			html: '<button><svg viewBox="0 0 100 100"></svg></button>',
			shouldPass: false,
		},
		{
			name: 'should fail for button with font icon without accessibility attributes',
			html: '<button><i class="fa fa-search"></i></button>',
			shouldPass: false,
		},
		{
			name: 'should fail for anchor with role="button" and no text or accessible attributes',
			html: '<a href="#" role="button"></a>',
			shouldPass: false,
		},
		{
			name: 'should fail for input button with empty value attribute',
			html: '<input type="button" value="">',
			shouldPass: false,
		},
		{
			name: 'should fail for button with only non-breaking spaces',
			html: '<button>&nbsp;&nbsp;&nbsp;</button>',
			shouldPass: false,
		},
		{
			name: 'should fail for complex button with multiple empty elements',
			html: '<button><span></span><div><i class="icon"></i><img src="img.jpg" alt=""></div></button>',
			shouldPass: false,
		},
		// New failing test cases
		{
			name: 'should fail for button with empty aria-description',
			html: '<button aria-description=""></button>',
			shouldPass: false,
		},
		{
			name: 'should fail for button with aria-describedby referencing non-existent element',
			html: '<button aria-describedby="non-existent-id"></button>',
			shouldPass: false,
		},
		{
			name: 'should fail for button with aria-describedby referencing empty elements',
			html: '<span id="empty-desc1"></span><span id="empty-desc2">   </span><button aria-describedby="empty-desc1 empty-desc2"></button>',
			shouldPass: false,
		},
	];

	testCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			document.body.innerHTML = testCase.html;

			const results = await axe.run( document.body, {
				runOnly: [ 'empty_button' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
			}
		} );
	} );
} );
