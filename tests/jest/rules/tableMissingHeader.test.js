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

describe( 'Table Header Detection Rule - Complex Scenarios', () => {
	const complexTestCases = [
		// ✅ Passing cases with colspan/rowspan
		{
			name: 'Table with colspan headers',
			html: `
				<table>
					<tr>
						<th colspan="2">Name</th>
						<th colspan="2">Contact</th>
					</tr>
					<tr>
						<th>First</th>
						<th>Last</th>
						<th>Email</th>
						<th>Phone</th>
					</tr>
					<tr>
						<td>John</td>
						<td>Doe</td>
						<td>john@example.com</td>
						<td>123-456-7890</td>
					</tr>
				</table>
			`,
			shouldPass: true,
		},
		{
			name: 'Table with rowspan headers',
			html: `
				<table>
					<tr>
						<th rowspan="2">Person</th>
						<th>Email</th>
					</tr>
					<tr>
						<th>Phone</th>
					</tr>
					<tr>
						<td>John Doe</td>
						<td>john@example.com</td>
					</tr>
					<tr>
						<td></td>
						<td>123-456-7890</td>
					</tr>
				</table>
			`,
			shouldPass: true,
		},
		{
			name: 'Table with ARIA headers attribute',
			html: `
				<table>
					<tr>
						<th id="name">Name</th>
						<th id="email">Email</th>
					</tr>
					<tr>
						<td headers="name">John Doe</td>
						<td headers="email">john@example.com</td>
					</tr>
				</table>
			`,
			shouldPass: true,
		},
		{
			name: 'Table with complex headers and scope',
			html: `
				<table>
					<tr>
						<th scope="col">Student</th>
						<th scope="colgroup" colspan="2">Scores</th>
					</tr>
					<tr>
						<th scope="col">Name</th>
						<th scope="col">Math</th>
						<th scope="col">Science</th>
					</tr>
					<tr>
						<td>John</td>
						<td>85</td>
						<td>92</td>
					</tr>
				</table>
			`,
			shouldPass: true,
		},
		{
			name: 'Table with both row and column headers',
			html: `
				<table>
					<tr>
						<th scope="col">Quarter</th>
						<th scope="col">Q1</th>
						<th scope="col">Q2</th>
						<th scope="col">Q3</th>
						<th scope="col">Q4</th>
					</tr>
					<tr>
						<th scope="row">Sales</th>
						<td>$100k</td>
						<td>$120k</td>
						<td>$110k</td>
						<td>$130k</td>
					</tr>
					<tr>
						<th scope="row">Profit</th>
						<td>$20k</td>
						<td>$25k</td>
						<td>$22k</td>
						<td>$28k</td>
					</tr>
				</table>
			`,
			shouldPass: true,
		},
		{
			name: 'Table with irregular structure but proper headers',
			html: `
				<table>
					<tr>
						<th colspan="3">Monthly Report</th>
					</tr>
					<tr>
						<th rowspan="2">Region</th>
						<th colspan="2">Sales</th>
					</tr>
					<tr>
						<th>Jan</th>
						<th>Feb</th>
					</tr>
					<tr>
						<td>North</td>
						<td>$50k</td>
						<td>$55k</td>
					</tr>
					<tr>
						<td>South</td>
						<td>$45k</td>
						<td>$48k</td>
					</tr>
				</table>
			`,
			shouldPass: true,
		},

		// ❌ Failing cases with complex structures
		{
			name: 'Table with colspan but insufficient headers',
			html: `
				<table>
					<tr>
						<th colspan="2">Contact Info</th>
					</tr>
					<tr>
						<td>John Doe</td>
						<td>john@example.com</td>
						<td>123-456-7890</td>
					</tr>
				</table>
			`,
			shouldPass: false,
		},
		{
			name: 'Table with mismatched ARIA headers',
			html: `
				<table>
					<tr>
						<th id="name">Name</th>
						<th id="email">Email</th>
					</tr>
					<tr>
						<td headers="nonexistent">John Doe</td>
						<td>john@example.com</td>
					</tr>
				</table>
			`,
			shouldPass: false,
		},
		{
			name: 'Complex table without proper headers',
			html: `
				<table>
					<tr>
						<td colspan="2"><strong>Contact Information</strong></td>
					</tr>
					<tr>
						<td>Name</td>
						<td>Email</td>
					</tr>
					<tr>
						<td>John Doe</td>
						<td>john@example.com</td>
					</tr>
				</table>
			`,
			shouldPass: false,
		},
	];

	complexTestCases.forEach( ( { name, html, shouldPass } ) => {
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
