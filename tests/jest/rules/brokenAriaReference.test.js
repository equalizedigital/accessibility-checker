import axe from 'axe-core';
// Set up axe with your rules before tests
beforeAll( async () => {
	// Dynamically import rule and check modules
	const ariaBrokenReferenceRule = await import( '../../../src/pageScanner/rules/aria-broken-reference.js' );
	const ariaLabelNotFoundCheck = await import( '../../../src/pageScanner/checks/aria-label-not-found.js' );
	const ariaDescribedByNotFoundCheck = await import( '../../../src/pageScanner/checks/aria-describedby-not-found.js' );
	const ariaOwnsNotFoundCheck = await import( '../../../src/pageScanner/checks/aria-owns-not-found.js' );

	// Configure axe with the imported rules and checks
	axe.configure( {
		rules: [ ariaBrokenReferenceRule.default ],
		checks: [ ariaLabelNotFoundCheck.default, ariaDescribedByNotFoundCheck.default, ariaOwnsNotFoundCheck.default ],
	} );
} );

// Reset the document between tests
beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Broken ARIA Reference Validation', () => {
	const testCases = [
		// Passing cases
		{
			name: 'should pass for valid aria-labelledby reference',
			html: '<div id="label">Label Text</div><button aria-labelledby="label">Click Me</button>',
			shouldPass: true,
		},
		{
			name: 'should pass for valid aria-describedby reference',
			html: '<div id="description">Description Text</div><input type="text" aria-describedby="description">',
			shouldPass: true,
		},
		{
			name: 'should pass for multiple valid aria-labelledby references',
			html: '<div id="label1">Label 1</div><div id="label2">Label 2</div><button aria-labelledby="label1 label2">Click Me</button>',
			shouldPass: true,
		},
		{
			name: 'should pass for valid aria-owns reference',
			html: '<div id="child">Child Element</div><div aria-owns="child">Parent Element</div>',
			shouldPass: true,
		},

		// Failing cases
		{
			name: 'should fail for missing aria-labelledby reference',
			html: '<button aria-labelledby="missing-label">Click Me</button>',
			shouldPass: false,
		},
		{
			name: 'should fail for missing aria-describedby reference',
			html: '<input type="text" aria-describedby="missing-description">',
			shouldPass: false,
		},
		{
			name: 'should fail for partially missing aria-labelledby references',
			html: '<div id="label1">Label 1</div><button aria-labelledby="label1 missing-label">Click Me</button>',
			shouldPass: false,
		},
		{
			name: 'should fail for missing aria-owns reference',
			html: '<div aria-owns="missing-child">Parent Element</div>',
			shouldPass: false,
		},
		{
			name: 'should fail for empty aria-labelledby attribute',
			html: '<button aria-labelledby="">Click Me</button>',
			shouldPass: false,
		},
		{
			name: 'should fail for empty aria-describedby attribute',
			html: '<input type="text" aria-describedby="">',
			shouldPass: false,
		},
	];

	const additionalTestCases = [
		// Passing cases
		{
			name: 'should pass for multiple valid aria-labelledby references',
			html: `
        <div id="myBillingId">Billing</div>
        <div>
          <div id="myNameId">Name</div>
          <p><input type="text" aria-labelledby="myBillingId myNameId"></p>
        </div>
        <div>
          <div id="myAddressId">Address</div>
          <p><input type="text" aria-labelledby="myBillingId myAddressId"></p>
        </div>
      `,
			shouldPass: true,
		},
		{
			name: 'should pass for associating headings with regions',
			html: `
        <div role="main" aria-labelledby="foo">
          <h4 id="foo">Wild fires spread across the San Diego Hills</h4>
          <p>Strong winds expand fires ignited by high temperatures ...</p>
        </div>
        <div role="application" aria-labelledby="calendar" aria-describedby="info">
          <h4 id="calendar">Calendar</h4>
          <p id="info">This calendar shows the game schedule for the Boston Red Sox.</p>
          <div role="grid">...</div>
        </div>
      `,
			shouldPass: true,
		},
		{
			name: 'should pass for button with valid aria-describedby',
			html: `
        <p><button aria-label="Close" aria-describedby="descriptionClose">X</button></p>
        <div id="descriptionClose">Closing this window will discard any information entered and<br>return you back to the main page</div>
      `,
			shouldPass: true,
		},

		// Failing cases
		{
			name: 'should fail for multiple labels with one missing',
			html: `
        <div>Billing</div>
        <div>
          <div id="hasNameId">Name</div>
          <p><input type="text" aria-labelledby="missingBillingId hasNameId"></p>
        </div>
      `,
			shouldPass: false,
		},
		{
			name: 'should fail for multiple labels with both missing',
			html: `
        <div>
          <div id="wrong-id">Address</div>
          <p><input type="text" aria-labelledby="missingId another-missing-Id"></p>
        </div>
      `,
			shouldPass: false,
		},
		{
			name: 'should fail for associating headings with regions - ID empty',
			html: `
        <div role="main" aria-labelledby="missing-association">
          <h4 id="">Wild fires spread across the San Diego Hills</h4>
          <p>Strong winds expand fires ignited by high temperatures ...</p>
        </div>
      `,
			shouldPass: false,
		},
		{
			name: 'should fail for button with typo in aria-describedby',
			html: `
        <p><button aria-label="Close" aria-describedby="closeDesciption">X</button></p>
        <div id="CloseDescription">Closing this window will discard any information entered and<br>return you back to the main page</div>
      `,
			shouldPass: false,
		},
	];

	const allTestCases = [ ...testCases, ...additionalTestCases ];

	allTestCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			document.body.innerHTML = testCase.html;

			const results = await axe.run( document.body, {
				runOnly: [ 'aria_broken_reference' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
			}
		} );
	} );
} );
