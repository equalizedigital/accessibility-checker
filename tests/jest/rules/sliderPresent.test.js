import axe from 'axe-core';

let sliderClassKeywords = [];

beforeAll( async () => {
	const sliderRuleModule = await import( '../../../src/pageScanner/rules/slider-present.js' );
	const sliderCheckModule = await import( '../../../src/pageScanner/checks/slider-detected.js' );

	const sliderRule = sliderRuleModule.default;
	const sliderCheck = sliderCheckModule.default;

	sliderClassKeywords = sliderCheckModule.sliderClassKeywords;

	axe.configure( {
		rules: [ sliderRule ],
		checks: [ sliderCheck ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Slider Detection Rule', () => {
	test.each( [
		...sliderClassKeywords.map( ( keyword ) => ( {
			name: `detects slider with class "${ keyword }"`,
			html: `<div class="${ keyword }"></div>`,
			shouldPass: false,
		} ) ),
		{
			name: 'detects slider with multiple classes including a slider class',
			html: '<div class="container slider widget"></div>',
			shouldPass: false,
		},
		{
			name: 'handles nested slider elements correctly',
			html: '<div class="wrapper"><div class="carousel">Nested slider</div></div>',
			shouldPass: false,
		},
		{
			name: 'ignores case variations in class names',
			html: '<div class="SLIDER"></div>',
			shouldPass: false,
		},
		{
			name: 'detects data-jssor-slider',
			html: '<div data-jssor-slider="true"></div>',
			shouldPass: false,
		},
		{
			name: 'detects data-layerslider-uid',
			html: '<div data-layerslider-uid="1"></div>',
			shouldPass: false,
		},
		{
			name: 'ignores unrelated div with no classes',
			html: '<div></div>',
			shouldPass: true,
		},
		{
			name: 'ignores unrelated section with text content',
			html: '<section>Just content</section>',
			shouldPass: true,
		},
		{
			name: 'ignores similar but unrelated class name',
			html: '<div class="not-a-slider"></div>',
			shouldPass: true,
		},
		{
			name: 'ignores [data-slider-id] which is not a match',
			html: '<div data-slider-id="abc123"></div>',
			shouldPass: true,
		},
	] )( '$name', async ( { html, shouldPass } ) => {
		document.body.innerHTML = html;

		const results = await axe.run( document.body, {
			runOnly: [ 'slider_present' ],
		} );

		if ( shouldPass ) {
			expect( results.violations.length ).toBe( 0 );
		} else {
			expect( results.violations.length ).toBeGreaterThan( 0 );
		}
	} );
} );
