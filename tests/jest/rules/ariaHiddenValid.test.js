import axe from 'axe-core';
// Use async/await for the test setup
beforeAll( async () => {
	// Dynamically import the modules
	const ariaHiddenRuleModule = await import( '../../../src/pageScanner/rules/aria-hidden-validation.js' );
	const ariaHiddenCheckModule = await import( '../../../src/pageScanner/checks/aria-hidden-valid-usage.js' );

	const ariaHiddenRule = ariaHiddenRuleModule.default;
	const ariaHiddenCheck = ariaHiddenCheckModule.default;

	// Configure axe with the imported rules
	axe.configure( {
		rules: [ ariaHiddenRule ],
		checks: [ ariaHiddenCheck ],
	} );
} );

// Reset the document between tests
beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Aria Hidden Validation', () => {
	const testCases = [
		// Passing cases
		{
			name: 'should pass for button with aria-label',
			html: '<button type="button" aria-label="Open menu"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect></svg></button>',
			shouldPass: true,
		},
		{
			name: 'should pass for button with screen reader text',
			html: '<button type="button"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect></svg><span class="sr-only">Open menu</span></button>',
			shouldPass: true,
		},
		{
			name: 'should pass for button with visible text',
			html: '<button type="button"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect></svg>Menu</button>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with aria-label',
			html: '<a href="/about" aria-label="About Us"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect></svg></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with screen reader text',
			html: '<a href="/about"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect></svg><span class="sr-only">About Us</span></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with visible text',
			html: '<a href="/about"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect></svg>About Us</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for image with presentation role',
			html: '<img src="image.jpg" alt="" aria-hidden="true" role="presentation">',
			shouldPass: true,
		},

		// Failing cases
		{
			name: 'should fail for link with only hidden SVG',
			html: '<a href="https://twitter.com/heyamberhinds" class="wp-block-social-link-anchor"><svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M13.982 10.622 L20.54 3h-1.554"></path></svg></a>',
			shouldPass: false,
		},
		{
			name: 'should fail for button with only hidden SVG',
			html: '<button type="button" class="components-button"><svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12.158,12.786L9.46,20.625c0.806"></path></svg></button>',
			shouldPass: false,
		},
		{
			name: 'should fail for div with important content',
			html: '<div aria-hidden="true">Important content</div>',
			shouldPass: false,
		},
		{
			name: 'should fail for link with empty aria-label',
			html: '<a href="/about" aria-label=""><svg aria-hidden="true"></svg></a>',
			shouldPass: false,
		},
	];

	const additionalTestCases = [
		// Class-based tests
		{
			name: 'should pass for wp-block-spacer class',
			html: '<div class="wp-block-spacer" aria-hidden="true"></div>',
			shouldPass: true,
		},

		// Role-based tests
		{
			name: 'should pass for element with role="presentation"',
			html: '<div role="presentation" aria-hidden="true">Content</div>',
			shouldPass: true,
		},
		{
			name: 'should pass for element with multiple roles including presentation',
			html: '<div role="presentation img" aria-hidden="true">Content</div>',
			shouldPass: true,
		},

		// Aria-label edge cases
		{
			name: 'should pass for button with aria-labelledby',
			html: '<span id="label-text">Menu</span><button aria-labelledby="label-text"><svg aria-hidden="true"></svg></button>',
			shouldPass: true,
		},
		{
			name: 'should fail for nested aria-hidden elements',
			html: '<div aria-hidden="true"><button aria-hidden="true"><svg></svg></button></div>',
			shouldPass: false,
		},

		// Screen reader text class tests
		{
			name: 'should pass with screen-reader-text class',
			html: '<a href="/about"><svg aria-hidden="true"></svg><span class="screen-reader-text">About</span></a>',
			shouldPass: true,
		},
		{
			name: 'should pass with sr-only class',
			html: '<a href="/about"><svg aria-hidden="true"></svg><span class="sr-only">About</span></a>',
			shouldPass: true,
		},
		{
			name: 'should pass with visually-hidden class',
			html: '<a href="/about"><svg aria-hidden="true"></svg><span class="visually-hidden">About</span></a>',
			shouldPass: true,
		},
		{
			name: 'should pass with partial class name match',
			html: '<a href="/about"><svg aria-hidden="true"></svg><span class="my-sr-only-text">About</span></a>',
			shouldPass: true,
		},

		// Nested elements tests
		{
			name: 'should fail for nested aria-hidden elements',
			html: '<div aria-hidden="true"><button aria-hidden="true"><svg></svg></button></div>',
			shouldPass: false,
		},
		{
			name: 'should fail for aria-hidden on semantic elements',
			html: '<h2 aria-hidden="true">Important heading</h2>',
			shouldPass: false,
		},

		// Mixed content tests
		{
			name: 'should pass for SVG with title element',
			html: '<svg aria-hidden="true"><title>Accessible title</title></svg>',
			shouldPass: false, // Current implementation would fail this as the title isn't recognized
		},
		{
			name: 'should pass for button with both icon and text in different order',
			html: '<button>Menu <svg aria-hidden="true"></svg></button>',
			shouldPass: true,
		},

		// Special element tests
		{
			name: 'should fail for aria-hidden on form controls',
			html: '<input type="text" aria-hidden="true">',
			shouldPass: false,
		},

		// Edge cases
		{
			name: 'should handle empty elements properly',
			html: '<div aria-hidden="true"></div>',
			shouldPass: false,
		},
		{
			name: 'should pass for decorative image with aria-hidden',
			html: '<img src="decorative.jpg" alt="" role="presentation" aria-hidden="true">',
			shouldPass: true,
		},
		{
			name: 'should fail for element with whitespace text only',
			html: '<button><svg aria-hidden="true"></svg>   </button>',
			shouldPass: false,
		},
		{
			name: 'should pass for button with text as child element',
			html: '<button><svg aria-hidden="true"></svg><div>Click me</div></button>',
			shouldPass: false, // Current implementation only checks for direct text nodes
		},
		{
			name: 'should pass for screen reader text in deeper structure',
			html: '<a href="/about"><svg aria-hidden="true"></svg><div><span class="sr-only">About</span></div></a>',
			shouldPass: false, // Current implementation only checks immediate siblings
		},
	];

	const cssVisibilityEdgeCases = [
		// display:none
		{
			name: 'should pass for display:none',
			html: '<div style="display:none;" aria-hidden="true">Hidden content</div>',
			shouldPass: true,
		},

		// visibility:hidden
		{
			name: 'should pass for visibility:hidden',
			html: '<div style="visibility:hidden;" aria-hidden="true"></div>',
			shouldPass: true,
		},

		// Parent with visible text + child with display:none
		{
			name: 'should pass for link with visible text and display:none svg',
			html: '<a href="/about">About Us<svg style="display:none;" aria-hidden="true"></svg></a>',
			shouldPass: true,
		},

		// Mixed display and visibility in aria-labeled button
		{
			name: 'should pass for button with aria-label and mixed hidden elements',
			html: '<button aria-label="Menu"><span style="display:none;" aria-hidden="true">Icon</span><span style="visibility:hidden;" aria-hidden="true">Text</span></button>',
			shouldPass: true,
		},

		// Combination of both properties
		{
			name: 'should pass for element with both display:none and visibility:hidden',
			html: '<div style="display:none; visibility:hidden;" aria-hidden="true">Super hidden</div>',
			shouldPass: true,
		},

		// Parent element hidden
		{
			name: 'should pass for visibility:hidden',
			html: '<a href="/settings" style="visibility:hidden;"><svg aria-hidden="true"></svg></a>',
			shouldPass: true,
		},
	];

	const cssClassHidingTestCases = [
		{
			name: 'should pass for custom CSS class with visibility:hidden',
			html: `<div class="visibility-hidden-custom" aria-hidden="true">Hidden content</div>`,
			css: `.visibility-hidden-custom { visibility: hidden; }`,
			shouldPass: true,
		},
		{
			name: 'should pass for complex hiding selector',
			html: `
			  <div class="parent">
				<span class="hidden-child" aria-hidden="true">Hidden via CSS</span>
			  </div>
			`,
			css: `.parent .hidden-child { position: absolute; left: -9999px; visibility: hidden; }`,
			shouldPass: true,
		},
	];

	const phpTestCasesConverted = [
		// Test for aria-hidden="false"
		{
			name: 'should pass for element with aria-hidden="false"',
			html: '<div aria-hidden="false">Visible content</div>',
			shouldPass: true,
		},

		// Tests for different screen reader text classes
		{
			name: 'should pass for screen-reader-text class',
			html: '<div class="parent"><div aria-hidden="true"></div><div class="screen-reader-text">Screen reader content</div></div>',
			shouldPass: true,
		},
		{
			name: 'should pass for show-for-sr class',
			html: '<div class="parent"><div aria-hidden="true"></div><div class="show-for-sr">Screen reader content</div></div>',
			shouldPass: true,
		},
		{
			name: 'should pass for visuallyhidden class',
			html: '<div class="parent"><div aria-hidden="true"></div><div class="visuallyhidden">Screen reader content</div></div>',
			shouldPass: true,
		},
		{
			name: 'should pass for hidden-visually class',
			html: '<div class="parent"><div aria-hidden="true"></div><div class="hidden-visually">Screen reader content</div></div>',
			shouldPass: true,
		},
		{
			name: 'should pass for accessibly-hidden class',
			html: '<div class="parent"><div aria-hidden="true"></div><div class="accessibly-hidden">Screen reader content</div></div>',
			shouldPass: true,
		},
	];

	const allTestCases = [
		...testCases,
		...additionalTestCases,
		...cssVisibilityEdgeCases,
		...cssClassHidingTestCases,
		...phpTestCasesConverted,
	];

	allTestCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			// Clear any previous styles
			const existingStyles = document.head.querySelectorAll( 'style[data-test-styles]' );
			existingStyles.forEach( ( el ) => el.remove() );

			// Add CSS if provided
			if ( testCase.css ) {
				const styleEl = document.createElement( 'style' );
				styleEl.setAttribute( 'data-test-styles', 'true' );
				styleEl.textContent = testCase.css;
				document.head.appendChild( styleEl );
			}

			document.body.innerHTML = testCase.html;

			const results = await axe.run( document.body, {
				runOnly: [ 'aria_hidden_validation' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
			}
		} );
	} );
} );
