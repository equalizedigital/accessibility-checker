import axe from 'axe-core';

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'iframe_missing_title rule', () => {
	const testCases = [
		// Should trigger violations
		{
			name: 'detects iframe without title or aria-label',
			html: '<iframe src="https://example.com"></iframe>',
			shouldPass: false,
		},
		{
			name: 'detects iframe with empty title and/or aria-label attributes',
			html: '<iframe src="https://example.com" title="" aria-label=""></iframe>',
			shouldPass: false,
		},
		{
			name: 'detects iframe with whitespace only in title attribute',
			html: '<iframe src="https://example.com" title="   "></iframe>',
			shouldPass: false,
		},

		// Should not trigger violations
		{
			name: 'does not detect iframe with valid title attribute',
			html: '<iframe src="https://example.com" title="Example iframe"></iframe>',
			shouldPass: true,
		},
		{
			name: 'does not detect iframe with valid aria-label attribute',
			html: '<iframe src="https://example.com" aria-label="Example iframe"></iframe>',
			shouldPass: true,
		},
		{
			name: 'skips a default Google Tag Manager iframe (they are hidden via inline styles)',
			html: '<iframe src="https://www.googletagmanager.com/ns.html?id=GTM-12345" title="" style="display:none;visibility:hidden"></iframe>',
			shouldPass: true,
		},

		// Skipping hidden iframes
		{
			name: 'skips hidden iframe with display:none and visibility:hidden (various formats)',
			html: '<iframe src="https://example.com" style="display:none;visibility:hidden"></iframe>',
			shouldPass: true,
		},
		{
			name: 'correctly handles mixed case style attributes',
			html: '<iframe src="https://example.com" style="DISPLAY: none; Visibility: Hidden;"></iframe>',
			shouldPass: true,
		},
		{
			name: 'passes iframe with only display:none but not visibility:hidden',
			html: '<iframe src="https://example.com" style="display:none;"></iframe>',
			shouldPass: true,
		},
		{
			name: 'passes iframe with only visibility:hidden but not display:none',
			html: '<iframe src="https://example.com" style="visibility:hidden;"></iframe>',
			shouldPass: true,
		},
		{
			name: 'correctly handles complex style attributes with both required properties',
			html: '<iframe src="https://example.com" style="color: blue; display: none; margin: 5px; visibility: hidden; padding: 10px;"></iframe>',
			shouldPass: true,
		},
		{
			name: 'frame hidden with css display does not trigger violation',
			html: '<iframe src="https://example.com" class="hidewithcss"></iframe>',
			css: '.hidewithcss { display: none; }',
			shouldPass: true,
		},
		{
			name: 'frame hidden with css visibility does not trigger violation',
			html: '<iframe src="https://example.com" class="hidewithcss"></iframe>',
			css: '.hidewithcss { visibility: hidden; }',
			shouldPass: true,
		},

		// Edge cases
		{
			name: 'handles multiple iframes correctly',
			html: '<div><iframe src="https://example1.com"></iframe><iframe src="https://example2.com" title="Example 2"></iframe></div>',
			expectedViolations: 1, // Only the first iframe should be flagged
		},
		{
			name: 'handles css priority correctly not triggering violation',
			html: '<iframe src="https://example.com" class="hidewithcss"></iframe>',
			css: '.hidewithcss { visibility: hidden; } iframe.hidewithcss { visibility: visible; }',
			shouldPass: true,
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
				runOnly: [ 'frame-title' ],
			} );

			if ( testCase.expectedViolations !== undefined ) {
				expect( results.violations.length > 0 ? results.violations[ 0 ].nodes.length : 0 ).toBe( testCase.expectedViolations );
			} else if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
			}
		} );
	} );
} );
