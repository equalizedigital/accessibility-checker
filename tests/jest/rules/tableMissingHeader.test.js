import axe from 'axe-core';

beforeAll( async () => {
	const ruleModule = await import( '../../../src/pageScanner/rules/table-header-missing.js' );
	const checkModule = await import( '../../../src/pageScanner/checks/table-has-headers.js' );

	axe.configure( {
		rules: [ ruleModule.default ],
		checks: [ checkModule.default ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Table Header Detection Rule', () => {
	const testCases = [
		// ✅ Passing cases
		{
			name: 'Core table block with thead',
			html: `
				<figure class="wp-block-table">
					<table>
						<thead><tr><th>First Name</th><th>Last Name</th></tr></thead>
						<tbody>
							<tr><td>Amber</td><td>Hinds</td></tr>
							<tr><td>Chris</td><td>Hinds</td></tr>
							<tr><td>Steve</td><td>Jones</td></tr>
						</tbody>
					</table>
				</figure>
			`,
			shouldPass: true,
		},
		{
			name: 'Table with column and row headers',
			html: `
				<table>
					<tr>
						<th scope="col">Name</th>
						<th scope="col">Title</th>
						<th scope="col">Twitter Handle</th>
					</tr>
					<tr>
						<th scope="row">Amber Hinds</th>
						<td>CEO</td>
						<td>@HeyAmberHinds</td>
					</tr>
					<tr>
						<th scope="row">Chris Hinds</th>
						<td>COO</td>
						<td>@mr_chrishinds</td>
					</tr>
					<tr>
						<th scope="row">Steve Jones</th>
						<td>CTO</td>
						<td>@stevejonesdev</td>
					</tr>
				</table>
			`,
			shouldPass: true,
		},
		{
			name: 'Row headers only (no scope)',
			html: `
				<table>
					<tr><th>Monday</th><td>8:00 AM – 5:00 PM</td></tr>
					<tr><th>Tuesday</th><td>8:00 AM – 5:00 PM</td></tr>
					<tr><th>Wednesday</th><td>8:00 AM – 5:00 PM</td></tr>
					<tr><th>Thursday</th><td>8:00 AM – 5:00 PM</td></tr>
					<tr><th>Friday</th><td>8:00 AM – 5:00 PM</td></tr>
					<tr><th>Saturday</th><td>10:00 AM – 2:00 PM</td></tr>
					<tr><th>Sunday</th><td>Closed</td></tr>
				</table>
			`,
			shouldPass: true,
		},
		{
			name: 'Row headers with scope="row"',
			html: `
				<table>
					<tr><th scope="row">Monday</th><td>8 a.m.</td><td>5 p.m.</td></tr>
					<tr><th scope="row">Tuesday</th><td>8 a.m.</td><td>5 p.m.</td></tr>
					<tr><th scope="row">Wednesday</th><td>8 a.m.</td><td>1 p.m.</td></tr>
					<tr><th scope="row">Thursday</th><td>8 a.m.</td><td>5 p.m.</td></tr>
					<tr><th scope="row">Friday</th><td>8 a.m.</td><td>5 p.m.</td></tr>
				</table>
			`,
			shouldPass: true,
		},
		{
			name: 'passes with empty headers (valid structure)',
			html: `
				<table>
					<thead><tr><th></th><th></th></tr></thead>
					<tbody>
						<tr><td>Data 1</td><td>Data 2</td></tr>
						<tr><td>Data 3</td><td>Data 4</td></tr>
					</tbody>
				</table>
			`,
			shouldPass: true,
		},

		// ❌ Failing cases
		{
			name: 'Core table block with no <th>',
			html: `
				<figure class="wp-block-table">
					<table>
						<tbody>
							<tr><td><strong>Food</strong></td><td><strong>Type</strong></td></tr>
							<tr><td>Bananas</td><td>Fruit</td></tr>
							<tr><td>Apples</td><td>Fruit</td></tr>
							<tr><td>Broccoli</td><td>Vegetable</td></tr>
						</tbody>
					</table>
				</figure>
			`,
			shouldPass: false,
		},
		{
			name: 'Table with fewer <th> than data columns',
			html: `
				<table>
					<tr><th>Student</th><th>Age</th></tr>
					<tr><td>Eleanor</td><td>11</td><td>Female</td></tr>
					<tr><td>Zara</td><td>7</td><td>Female</td></tr>
				</table>
			`,
			shouldPass: false,
		},
		{
			name: 'Table with missing row header in last row',
			html: `
				<table>
					<tr><th>Monday</th><td>8:00 AM – 5:00 PM</td></tr>
					<tr><th>Tuesday</th><td>8:00 AM – 5:00 PM</td></tr>
					<tr><th>Wednesday</th><td>8:00 AM – 5:00 PM</td></tr>
					<tr><th>Thursday</th><td>8:00 AM – 5:00 PM</td></tr>
					<tr><th>Friday</th><td>8:00 AM – 5:00 PM</td></tr>
					<tr><th>Saturday</th><td>10:00 AM – 2:00 PM</td></tr>
					<tr><td>Sunday</td><td>Closed</td></tr>
				</table>
			`,
			shouldPass: false,
		},
	];

	testCases.forEach( ( { name, html, shouldPass } ) => {
		test( name, async () => {
			document.body.innerHTML = html;

			const results = await axe.run( document.body, {
				runOnly: [ 'missing_table_header' ],
			} );

			if ( shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
			}
		} );
	} );
} );
