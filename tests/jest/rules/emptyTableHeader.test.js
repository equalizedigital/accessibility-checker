import axe from 'axe-core';

beforeAll( async () => {
	// Dynamically import the custom rule
	const emptyTableHeaderRuleModule = await import( '../../../src/pageScanner/rules/empty-table-header.js' );
	const emptyTableHeaderCheckModule = await import( '../../../src/pageScanner/checks/table-header-is-empty.js' );
	const emptyTableHeaderRule = emptyTableHeaderRuleModule.default;
	const emptyTableHeaderCheck = emptyTableHeaderCheckModule.default;

	// Configure axe with the custom rule
	axe.configure( {
		rules: [ emptyTableHeaderRule ],
		checks: [ emptyTableHeaderCheck ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Empty Table Header Validation', () => {
	const testCases = [
		// Passing cases
		{
			name: 'should pass for table header with visible text',
			html: '<table><tr><th>Name</th><td>John</td></tr></table>',
			shouldPass: true,
		},
		{
			name: 'should pass for table header with aria-label',
			html: '<table><tr><th aria-label="Product Name"></th><td>Widget</td></tr></table>',
			shouldPass: true,
		},
		{
			name: 'should pass for table header with title attribute',
			html: '<table><tr><th title="Customer ID"></th><td>12345</td></tr></table>',
			shouldPass: true,
		},
		{
			name: 'should pass for table header with an image and alt text',
			html: '<table><tr><th><img src="icon.png" alt="Status"></th><td>Active</td></tr></table>',
			shouldPass: true,
		},
		{
			name: 'should pass for table header with nested span containing text',
			html: '<table><tr><th><span>Price</span></th><td>$10.00</td></tr></table>',
			shouldPass: true,
		},
		{
			name: 'should pass for table header with aria-labelledby',
			html: '<span id="header-label">Category</span><table><tr><th aria-labelledby="header-label"></th><td>Electronics</td></tr></table>',
			shouldPass: true,
		},
		{
			name: 'should pass for table header with SVG that has title',
			html: '<table><tr><th><svg><title>Rating</title></svg></th><td>5 stars</td></tr></table>',
			shouldPass: true,
		},
		{
			name: 'should pass for table header with i tag that has title',
			html: '<table><tr><th><i class="fa fa-info" title="Information"></i></th><td>Details</td></tr></table>',
			shouldPass: true,
		},
		{
			name: 'should pass for table header with screen reader-only content',
			html: '<table><tr><th><span class="screen-reader-text">Hidden Column Title</span></th><td>Data</td></tr></table>',
			shouldPass: true,
		},

		// Failing cases
		{
			name: 'should fail for empty table header with no text or attributes',
			html: '<table><tr><th></th><td>Data</td></tr></table>',
			shouldPass: false,
		},
		{
			name: 'should fail for table header with only whitespace',
			html: '<table><tr><th>   </th><td>Data</td></tr></table>',
			shouldPass: false,
		},
		{
			name: 'should fail for table header with only non-breaking spaces',
			html: '<table><tr><th>&nbsp;&nbsp;&nbsp;</th><td>Data</td></tr></table>',
			shouldPass: false,
		},
		{
			name: 'should fail for table header with only hyphens',
			html: '<table><tr><th>---</th><td>Data</td></tr></table>',
			shouldPass: false,
		},
		{
			name: 'should fail for table header with only underscores',
			html: '<table><tr><th>___</th><td>Data</td></tr></table>',
			shouldPass: false,
		},
		{
			name: 'should fail for table header with an image but no alt text',
			html: '<table><tr><th><img src="icon.png" alt=""></th><td>Data</td></tr></table>',
			shouldPass: false,
		},
		{
			name: 'should fail for table header with nested empty elements',
			html: '<table><tr><th><span></span><div></div></th><td>Data</td></tr></table>',
			shouldPass: false,
		},
		{
			name: 'should fail for table header with font icon without accessibility attributes',
			html: '<table><tr><th><i class="fa fa-star"></i></th><td>Data</td></tr></table>',
			shouldPass: false,
		},
		{
			name: 'should fail for table header with empty aria-label',
			html: '<table><tr><th aria-label=""></th><td>Data</td></tr></table>',
			shouldPass: false,
		},
		{
			name: 'should fail for table header with aria-labelledby referencing empty element',
			html: '<span id="empty-label"></span><table><tr><th aria-labelledby="empty-label"></th><td>Data</td></tr></table>',
			shouldPass: false,
		},
		{
			name: 'should fail for table header with empty cells in complex table structure',
			html: '<table><thead><tr><th>Name</th><th></th><th>Price</th></tr></thead><tbody><tr><td>Product</td><td>Description</td><td>$10</td></tr></tbody></table>',
			shouldPass: false,
		},
		{
			name: 'should fail for table header in scope but without content',
			html: '<table><tr><th scope="col"></th><td>Data</td></tr></table>',
			shouldPass: false,
		},
		{
			name: 'should fail for table header with SVG without title',
			html: '<table><tr><th><svg viewBox="0 0 100 100"></svg></th><td>Data</td></tr></table>',
			shouldPass: false,
		},
	];

	testCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			document.body.innerHTML = testCase.html;

			const results = await axe.run( document.body, {
				runOnly: [ 'empty_table_header' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
			}
		} );
	} );
} );
