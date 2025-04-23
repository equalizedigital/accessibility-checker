import axe from 'axe-core';

beforeAll( async () => {
	const ruleModule = await import( '../../../src/pageScanner/rules/img-linked-alt-empty.js' );
	const checkModule = await import( '../../../src/pageScanner/checks/linked-image-alt-not-empty.js' );

	axe.configure( {
		rules: [ ruleModule.default ],
		checks: [ checkModule.default ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';

	const style = document.createElement( 'style' );
	style.innerHTML = `
		.hidden { display: none; }
		.invisible { visibility: hidden; }
	`;
	document.head.appendChild( style );
} );

describe( 'Linked Image Empty Alternative Text Rule', () => {
	test.each( [
		// ❌ Failing cases
		{
			name: 'fails when linked image has empty alt text',
			html: '<a href="page.html"><img src="image.jpg" alt=""></a>',
			shouldPass: false,
		},
		{
			name: 'fails when linked image has alt attribute with only spaces',
			html: '<a href="page.html"><img src="image.jpg" alt="   "></a>',
			shouldPass: false,
		},
		{
			name: 'fails when image has empty alt and anchor has less than 5 characters',
			html: '<a href="#"><img src="icon.png" alt=""> hi</a>',
			shouldPass: false,
		},

		// ✅ Passing cases
		{
			name: 'passes when linked image has non-empty alt text',
			html: '<a href="page.html"><img src="image.jpg" alt="Go to page"></a>',
			shouldPass: true,
		},
		{
			name: 'passes when anchor has aria-label',
			html: '<a href="page.html" aria-label="Home link"><img src="image.jpg" alt=""></a>',
			shouldPass: true,
		},
		{
			name: 'passes when anchor has title',
			html: '<a href="page.html" title="More Info"><img src="info.jpg" alt=""></a>',
			shouldPass: true,
		},
		{
			name: 'passes when image has role="presentation"',
			html: '<a href="page.html"><img src="decorative.jpg" role="presentation" alt=""></a>',
			shouldPass: true,
		},
		{
			name: 'passes when image has aria-hidden="true"',
			html: '<a href="page.html"><img src="decorative.jpg" aria-hidden="true" alt=""></a>',
			shouldPass: true,
		},
		{
			name: 'passes when non-image anchor content is present',
			html: '<a href="page.html">Click here for more information <img src="arrow.jpg" alt=""></a>',
			shouldPass: true,
		},
		{
			name: 'passes when image is hidden by display:none',
			html: '<a href="page.html"><img src="logo.jpg" alt="" style="display:none;"></a>',
			shouldPass: true,
		},
		{
			name: 'passes when image is hidden by class',
			html: '<a href="page.html"><img src="logo.jpg" alt="" class="hidden"></a>',
			shouldPass: true,
		},
	] )( '$name', async ( { html, shouldPass } ) => {
		document.body.innerHTML = html;

		const results = await axe.run( document.body, {
			runOnly: [ 'img_linked_alt_empty' ],
		} );

		if ( shouldPass ) {
			expect( results.violations.length ).toBe( 0 );
		} else {
			expect( results.violations.length ).toBeGreaterThan( 0 );
		}
	} );
} );
