import axe from 'axe-core';

beforeAll( async () => {
	const ruleModule = await import( '../../../src/pageScanner/rules/img-linked-alt-missing.js' );
	const checkModule = await import( '../../../src/pageScanner/checks/linked-image-alt-present.js' );

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

describe( 'Linked Image Missing Alternative Text Rule', () => {
	test.each( [
		// ❌ Failing cases
		{
			name: 'fails when linked image has no alt text',
			html: '<a href="page.html"><img src="image.jpg"></a>',
			shouldPass: false,
		},
		{
			name: 'fails when linked image is only content and has no alt/title/aria-label',
			html: '<a href="page.html"><img src="logo.jpg"></a>',
			shouldPass: false,
		},
		{
			name: 'fails when image lacks alt and anchor has less than 5 characters',
			html: '<a href="#"> <img src="icon.png"> </a>',
			shouldPass: false,
		},
		{
			name: 'fails when anchor content is only whitespace',
			html: '<a href="#">   \n\t   <img src="icon.png">    \n\t   </a>',
			shouldPass: false,
		},

		// ✅ Passing cases
		{
			name: 'passes when linked image has alt text',
			html: '<a href="page.html"><img src="image.jpg" alt="Go to page"></a>',
			shouldPass: true,
		},
		{
			name: 'passes when anchor has aria-label',
			html: '<a href="page.html" aria-label="Home link"><img src="image.jpg"></a>',
			shouldPass: true,
		},
		{
			name: 'passes when anchor has title',
			html: '<a href="page.html" title="More Info"><img src="info.jpg"></a>',
			shouldPass: true,
		},
		{
			name: 'passes when image has role="presentation"',
			html: '<a href="page.html"><img src="decorative.jpg" role="presentation"></a>',
			shouldPass: true,
		},
		{
			name: 'passes when image has aria-hidden="true"',
			html: '<a href="page.html"><img src="decorative.jpg" aria-hidden="true"></a>',
			shouldPass: true,
		},
		{
			name: 'passes when non-image anchor content is present',
			html: '<a href="page.html">Click <img src="arrow.jpg"></a>',
			shouldPass: true,
		},
		{
			name: 'passes when image is hidden by display:none',
			html: '<a href="page.html"><img src="logo.jpg" style="display:none;"></a>',
			shouldPass: true,
		},
		{
			name: 'passes when image is hidden by class',
			html: '<a href="page.html"><img src="logo.jpg" class="hidden"></a>',
			shouldPass: true,
		},
	] )( '$name', async ( { html, shouldPass } ) => {
		document.body.innerHTML = html;

		const results = await axe.run( document.body, {
			runOnly: [ 'img_linked_alt_missing' ],
		} );

		if ( shouldPass ) {
			expect( results.violations.length ).toBe( 0 );
		} else {
			expect( results.violations.length ).toBeGreaterThan( 0 );
		}
	} );
} );
