import axe from 'axe-core';

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'image_map_missing_alt_text rule', () => {
	const testCases = [
		// Should trigger violations
		{
			name: 'detects area element without alt attribute',
			html: '<map name="example-map"><area shape="rect" coords="0,0,100,100" href="#"></map>',
			shouldPass: false,
		},
		{
			name: 'detects area element with empty alt attribute',
			html: '<map name="example-map"><area shape="rect" coords="0,0,100,100" href="#" alt=""></map>',
			shouldPass: false,
		},
		{
			name: 'detects area element with whitespace only in alt attribute',
			html: '<map name="example-map"><area shape="rect" coords="0,0,100,100" href="#" alt="   "></map>',
			shouldPass: false,
		},
		{
			name: 'detects multiple area elements with missing alt text in one map',
			html: '<map name="example-map"><area shape="rect" coords="0,0,100,100" href="#" alt=""><area shape="circle" coords="200,200,50" href="#" alt=""></map>',
			expectedViolations: 2,
		},

		// Should not trigger violations
		{
			name: 'does not detect area element with valid alt attribute',
			html: '<map name="example-map"><area shape="rect" coords="0,0,100,100" href="#" alt="Example area"></map>',
			shouldPass: true,
		},
		{
			name: 'correctly handles map with mixed valid and invalid area elements',
			html: '<map name="example-map"><area shape="rect" coords="0,0,100,100" href="#" alt="Valid alt"><area shape="circle" coords="200,200,50" href="#" alt=""></map>',
			expectedViolations: 1,
		},

		// Edge cases
		{
			name: 'handles map with no area elements',
			html: '<map name="example-map"></map>',
			shouldPass: true,
		},
		{
			name: 'handles complete image map setup correctly',
			html: '<img src="example.jpg" usemap="#example-map" alt="Example image"><map name="example-map"><area shape="rect" coords="0,0,100,100" href="#" alt=""><area shape="circle" coords="200,200,50" href="#" alt="Valid area"></map>',
			expectedViolations: 1,
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

			// Custom axe check for image maps with missing alt text will need to be registered
			// This is a placeholder for the actual rule, which will be implemented separately
			const results = await axe.run( document.body, {
				runOnly: {
					type: 'rule',
					values: [ 'area-alt' ],
				},
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
