import axe from 'axe-core';

// Reset the document between tests
beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'form-field-multiple-labels rule', () => {
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
			name: 'should pass when a form field has a single aria-label',
			html: `
			<form>
				<input id="input1" type="text" aria-label="Name" />
			</form>
			`,
			shouldPass: true,
		},
		{
			name: 'should pass when a form field has a single aria-labelledby',
			html: `
			<form>
				<div id="label1">Name</div>
				<input id="input1" type="text" aria-labelledby="label1" />
			</form>
			`,
			shouldPass: true,
		},
		{
			name: 'should pass when a form field has no label',
			html: `
			<form>
				<input id="input1" type="text" />
			</form>
			`,
			shouldPass: true,
		},
		{
			name: 'should pass when hidden',
			html: `
			<form>
				<input id="input1" type="text" aria-hidden="true" />
			</form>
			`,
			shouldPass: true,
		},
		{
			name: 'should pass when multiple aria-labelledby references exist',
			html: `
			<form>
				<div id="label1">First</div>
				<div id="label2">Last</div>
				<input id="input1" type="text" aria-labelledby="label1 label2" />
			</form>
			`,
			shouldPass: true,
		},

		// Failing cases
		{
			name: 'should fail when a form field has multiple label elements',
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
			name: 'should fail when a select has multiple labels',
			html: `
			<form>
				<label for="select1">Category</label>
				<label for="select1">Type</label>
				<select id="select1"><option>Choose...</option></select>
			</form>
			`,
			shouldPass: false,
		},
		{
			name: 'should fail when a textarea has multiple labels',
			html: `
			<form>
				<label for="text1">Comment</label>
				<label for="text1">Message</label>
				<textarea id="text1"></textarea>
			</form>
			`,
			shouldPass: false,
		},
	];

	testCases.forEach( ( { name, html, shouldPass } ) => {
		test( name, async () => {
			document.body.innerHTML = html;

			const results = await axe.run( document.body, {
				runOnly: [ 'form-field-multiple-labels' ],
			} );

			const violations = [ ...results.violations, ...results.incomplete ];

			if ( shouldPass ) {
				expect( violations.length ).toBe( 0 );
			} else {
				expect( violations.length ).toBeGreaterThan( 0 );
			}
		} );
	} );
} );
