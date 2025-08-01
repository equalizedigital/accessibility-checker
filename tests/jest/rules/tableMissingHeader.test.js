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

		// ✅ ARIA headers and labelledby support
		{
			name: 'table with headers attribute (ARIA headers)',
			html: `
				<table>
					<tr>
						<td id="name">Name</td>
						<td id="position">Position</td>
					</tr>
					<tr>
						<td headers="name">John Doe</td>
						<td headers="position">Developer</td>
					</tr>
					<tr>
						<td headers="name">Jane Smith</td>
						<td headers="position">Designer</td>
					</tr>
				</table>
			`,
			shouldPass: true,
		},
		{
			name: 'table with aria-labelledby attribute',
			html: `
				<table>
					<tr>
						<td aria-labelledby="header1">Name</td>
						<td aria-labelledby="header2">Age</td>
					</tr>
					<tr>
						<td>Alice</td>
						<td>25</td>
					</tr>
					<tr>
						<td>Bob</td>
						<td>30</td>
					</tr>
				</table>
			`,
			shouldPass: true,
		},
		{
			name: 'table with both headers and aria-labelledby',
			html: `
				<table>
					<tr>
						<td id="col1" aria-labelledby="header1">Product</td>
						<td id="col2">Price</td>
					</tr>
					<tr>
						<td headers="col1">Widget A</td>
						<td headers="col2">$10</td>
					</tr>
				</table>
			`,
			shouldPass: true,
		},

		// ✅ Colspan support in headers
		{
			name: 'table with colspan in header row',
			html: `
				<table>
					<thead>
						<tr>
							<th colspan="2">Personal Information</th>
							<th colspan="2">Contact Details</th>
						</tr>
						<tr>
							<th>First Name</th>
							<th>Last Name</th>
							<th>Email</th>
							<th>Phone</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>John</td>
							<td>Doe</td>
							<td>john@example.com</td>
							<td>555-1234</td>
						</tr>
					</tbody>
				</table>
			`,
			shouldPass: true,
		},
		{
			name: 'table with colspan in single header row',
			html: `
				<table>
					<tr>
						<th colspan="3">Sales Report</th>
					</tr>
					<tr>
						<td>Q1</td>
						<td>$100K</td>
						<td>15% growth</td>
					</tr>
					<tr>
						<td>Q2</td>
						<td>$120K</td>
						<td>20% growth</td>
					</tr>
				</table>
			`,
			shouldPass: true,
		},
		{
			name: 'table with colspan in data rows matching header',
			html: `
				<table>
					<thead>
						<tr>
							<th>Month</th>
							<th>Sales</th>
							<th>Growth</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>January</td>
							<td colspan="2">Data not available</td>
						</tr>
						<tr>
							<td>February</td>
							<td>$50K</td>
							<td>10%</td>
						</tr>
					</tbody>
				</table>
			`,
			shouldPass: true,
		},

		// ✅ Complex table structures that should pass
		{
			name: 'table with mixed th and td in data rows',
			html: `
				<table>
					<thead>
						<tr>
							<th>Category</th>
							<th>Item</th>
							<th>Price</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<th scope="row">Electronics</th>
							<td>Laptop</td>
							<td>$999</td>
						</tr>
						<tr>
							<th scope="row">Books</th>
							<td>Novel</td>
							<td>$15</td>
						</tr>
					</tbody>
				</table>
			`,
			shouldPass: true,
		},
		{
			name: 'complex table with rowspan headers',
			html: `
				<table>
					<thead>
						<tr>
							<th rowspan="2">Employee</th>
							<th colspan="2">2023</th>
							<th colspan="2">2024</th>
						</tr>
						<tr>
							<th>Q1</th>
							<th>Q2</th>
							<th>Q1</th>
							<th>Q2</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>John</td>
							<td>90%</td>
							<td>95%</td>
							<td>88%</td>
							<td>92%</td>
						</tr>
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

		// ❌ ARIA-related failing cases
		{
			name: 'table without headers and without ARIA attributes',
			html: `
				<table>
					<tr>
						<td>Name</td>
						<td>Position</td>
					</tr>
					<tr>
						<td>John Doe</td>
						<td>Developer</td>
					</tr>
					<tr>
						<td>Jane Smith</td>
						<td>Designer</td>
					</tr>
				</table>
			`,
			shouldPass: false,
		},

		// ❌ Colspan-related failing cases
		{
			name: 'table with colspan mismatch - too many data columns',
			html: `
				<table>
					<thead>
						<tr>
							<th colspan="2">Basic Info</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>John</td>
							<td>Doe</td>
							<td>Extra Column</td>
							<td>Another Extra</td>
						</tr>
					</tbody>
				</table>
			`,
			shouldPass: false,
		},
		{
			name: 'table with inadequate headers for complex structure',
			html: `
				<table>
					<tr>
						<th>Name</th>
					</tr>
					<tr>
						<td>John</td>
						<td>Extra data</td>
						<td>More data</td>
					</tr>
					<tr>
						<td>Jane</td>
						<td>Extra data</td>
						<td>More data</td>
					</tr>
				</table>
			`,
			shouldPass: false,
		},

		// ❌ Complex structure failing cases
		{
			name: 'complex table without proper header structure',
			html: `
				<table>
					<tr>
						<td colspan="2">Report Title</td>
						<td>Date</td>
					</tr>
					<tr>
						<td>Item 1</td>
						<td>Value 1</td>
						<td>2024-01-01</td>
					</tr>
					<tr>
						<td>Item 2</td>
						<td>Value 2</td>
						<td>2024-01-02</td>
					</tr>
				</table>
			`,
			shouldPass: false,
		},
		{
			name: 'table with inconsistent row structure',
			html: `
				<table>
					<thead>
						<tr>
							<th>Column 1</th>
							<th>Column 2</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>Data 1</td>
							<td>Data 2</td>
						</tr>
						<tr>
							<td>Data 3</td>
							<td>Data 4</td>
							<td>Extra</td>
							<td>More Extra</td>
							<td>Even More</td>
						</tr>
					</tbody>
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
