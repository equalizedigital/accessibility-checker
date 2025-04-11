import axe from 'axe-core';

beforeAll( async () => {
	const ruleModule = await import( '../../../src/pageScanner/rules/long-description-invalid.js' );
	const checkModule = await import( '../../../src/pageScanner/checks/longdesc-valid.js' );

	const longdescInvalidRule = ruleModule.default;
	const longdescValidCheck = checkModule.default;

	axe.configure( {
		rules: [ longdescInvalidRule ],
		checks: [ longdescValidCheck ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';

	// Inject test styles for visibility testing
	const style = document.createElement( 'style' );
	style.innerHTML = `
		.hidden { display: none; }
		.invisible { visibility: hidden; }
	`;
	document.head.appendChild( style );
} );

describe( 'Longdesc Invalid Rule', () => {
	test.each( [
		// ❌ Failing cases
		{
			name: 'Fails when longdesc is empty',
			html: '<img src="image.jpg" longdesc="">',
			shouldPass: false,
		},
		{
			name: 'Fails when longdesc points to image',
			html: '<img src="image.jpg" longdesc="image-desc.png">',
			shouldPass: false,
		},
		{
			name: 'Fails when longdesc is malformed URL',
			html: '<img src="image.jpg" longdesc="ht!tp://bad-url">',
			shouldPass: false,
		},
		{
			name: 'Fails when longdesc is URL but missing filename',
			html: '<img src="image.jpg" longdesc="https://example.com/">',
			shouldPass: false,
		},
		{
			name: 'Fails with valid-looking but image file extension',
			html: '<img src="photo.jpg" longdesc="graphic.gif">',
			shouldPass: false,
		},

		// ✅ Passing cases
		{
			name: 'Passes with valid longdesc URL to HTML',
			html: '<img src="photo.jpg" longdesc="details.html">',
			shouldPass: true,
		},
		{
			name: 'Passes when longdesc is not present',
			html: '<img src="photo.jpg">',
			shouldPass: true,
		},
		{
			name: 'Passes with valid external longdesc URL',
			html: '<img src="photo.jpg" longdesc="https://example.com/details.html">',
			shouldPass: true,
		},
		{
			name: 'Passes with URL containing query parameters',
			html: '<img src="photo.jpg" longdesc="details.html?id=123">',
			shouldPass: true,
		},
		{
			name: 'Passes with relative URL',
			html: '<img src="photo.jpg" longdesc="../details/page.html">',
			shouldPass: true,
		},
		{
			name: 'Passes with URL containing hash fragment',
			html: '<img src="photo.jpg" longdesc="details.html#section2">',
			shouldPass: true,
		},

		// ✅ Hidden elements should be skipped
		{
			name: 'display:none (inline) should be skipped',
			html: '<img src="photo.jpg" longdesc="bad.png" style="display:none;">',
			shouldPass: true,
		},
		{
			name: 'visibility:hidden (inline) should be skipped',
			html: '<img src="photo.jpg" longdesc="bad.png" style="visibility:hidden;">',
			shouldPass: true,
		},
		{
			name: 'display:none via class should be skipped',
			html: '<img src="photo.jpg" class="hidden" longdesc="bad.png">',
			shouldPass: true,
		},
		{
			name: 'visibility:hidden via class should be skipped',
			html: '<img src="photo.jpg" class="invisible" longdesc="bad.png">',
			shouldPass: true,
		},
		{
			name: 'aria-hidden="true" should be skipped',
			html: '<img src="photo.jpg" longdesc="bad.png" aria-hidden="true">',
			shouldPass: true,
		},
	] )( '$name', async ( { html, shouldPass } ) => {
		document.body.innerHTML = html;

		const results = await axe.run( document.body, {
			runOnly: [ 'long_description_invalid' ],
		} );

		if ( shouldPass ) {
			expect( results.violations.length ).toBe( 0 );
		} else {
			expect( results.violations.length ).toBeGreaterThan( 0 );
		}
	} );
} );
