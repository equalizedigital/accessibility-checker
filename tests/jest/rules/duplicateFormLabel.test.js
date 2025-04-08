import axe from 'axe-core';

// Use async/await for the test setup
beforeAll( async () => {
	// Dynamically import the modules
	const duplicateFormLabelRuleModule = await import( '../../../src/pageScanner/rules/duplicate-form-label.js' );
	const duplicateFormLabelCheckModule = await import( '../../../src/pageScanner/checks/duplicate-form-label-check.js' );

	const duplicateFormLabelRule = duplicateFormLabelRuleModule.default;
	const duplicateFormLabelCheck = duplicateFormLabelCheckModule.default;

	// Configure axe with the imported rules
	axe.configure( {
		rules: [ duplicateFormLabelRule ],
		checks: [ duplicateFormLabelCheck ],
	} );
} );

// Reset the document between tests
beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'duplicate_form_label rule', () => {
	const testCases = [
		// Passing cases
		{
			name: 'should pass when a form field has a single label',
			html: `
    <form>
      <label for="input1">Name</label>
      <input id="input1" type="text" />
    </form>
   `,
			shouldPass: true,
		},
		{
			name: 'should pass when a form field has a single label using aria-labelledby',
			html: `
    <form>
      <div id="label1">Name</div>
      <input id="input1" type="text" aria-labelledby="label1" />
    </form>
   `,
			shouldPass: true,
		},

		// Failing cases
		{
			name: 'should fail when a form field has multiple labels',
			html: `
    <form>
      <label for="input1">First Name</label>
      <label for="input1">Last Name</label>
      <input id="input1" type="text" />
    </form>
   `,
			shouldPass: false,
		},
		{
			name: 'should pass when a form field has multiple IDs in aria-labelledby (this is valid)',
			html: `
    <form>
      <div id="label1">First Name</div>
      <div id="label2">Last Name</div>
      <input id="input1" type="text" aria-labelledby="label1 label2" />
    </form>
   `,
			shouldPass: true,
		},

		// Edge cases
		{
			name: 'should pass when a form field has no label but is validly hidden',
			html: `
    <form>
      <input id="input1" type="text" aria-hidden="true" />
    </form>
   `,
			shouldPass: true,
		},
		{
			name: 'should fail when a form field has no label and is not hidden',
			html: `
    <form>
      <input id="input1" type="text" />
    </form>
   `,
			shouldPass: false,
		},

		// Complex cases
		{
			name: 'should pass when a form field has a single label with nested elements',
			html: `
    <form>
      <label for="input1"><span>Name</span></label>
      <input id="input1" type="text" />
    </form>
   `,
			shouldPass: true,
		},
		{
			name: 'should fail when a form field has multiple labels with nested elements',
			html: `
    <form>
      <label for="input1"><span>First Name</span></label>
      <label for="input1"><span>Last Name</span></label>
      <input id="input1" type="text" />
    </form>
   `,
			shouldPass: false,
		},
		{
			name: 'should fail when a form field has an invalid aria-labelledby reference with nothing else',
			html: `
    <form>
      <input id="input1" type="text" aria-labelledby="missingId" />
    </form>
   `,
			shouldPass: false,
		},
		{
			name: 'should pass when a form field has a valid aria-labelledby reference and no label',
			html: `
    <form>
      <div id="label1">Name</div>
      <input id="input1" type="text" aria-labelledby="label1" />
    </form>
   `,
			shouldPass: true,
		},
		{
			name: 'should pass when a form field has multiple valid aria-labelledby references',
			html: `
    <form>
      <div id="label1">First Name</div>
      <div id="label2">Last Name</div>
      <input id="input1" type="text" aria-labelledby="label1 label2" />
    </form>
   `,
			shouldPass: true,
		},
		{
			name: 'should pass when a form field has both aria-label and aria-labelledby',
			html: `
    <form>
      <div id="label1">Full Name</div>
      <input id="input1" type="text" aria-label="Name" aria-labelledby="label1" />
    </form>
   `,
			shouldPass: true,
		},
	];

	testCases.forEach( ( { name, html, shouldPass } ) => {
		test( name, async () => {
			document.body.innerHTML = html;

			const results = await axe.run( document.body, {
				runOnly: [ 'duplicate_form_label' ],
			} );

			if ( shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
			}
		} );
	} );
} );

