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
			name: 'passes when image has role="none"',
			html: '<a href="page.html"><img src="decorative.jpg" role="none"></a>',
			shouldPass: true,
		},
		{
			name: 'passes when image has role="none presentation" (multi-value token)',
			html: '<a href="page.html"><img src="decorative.jpg" role="none presentation"></a>',
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
		// Links inside closed tabs, accordions or sliders inherit visibility:hidden from the panel.
		{
			name: 'fails for linked image without alt inside a visibility:hidden container',
			html: '<div style="visibility: hidden;"><a href="page.html"><img src="logo.jpg"></a></div>',
			shouldPass: false,
		},
		{
			name: 'passes for linked image with alt text inside a visibility:hidden container',
			html: '<div style="visibility: hidden;"><a href="page.html"><img src="logo.jpg" alt="Home"></a></div>',
			shouldPass: true,
		},
		{
			name: 'passes when image is hidden by visibility:hidden inside a visible link',
			html: '<a href="page.html"><img src="logo.jpg" style="visibility: hidden;"></a>',
			shouldPass: true,
		},
		// Links in display:none / hidden / aria-hidden subtrees are not rendered at all,
		// so they are skipped even though these rules scan visibility:hidden panels.
		{
			name: 'passes when link is inside a display:none container',
			html: '<div style="display: none;"><a href="page.html"><img src="logo.jpg"></a></div>',
			shouldPass: true,
		},
		{
			name: 'passes when link is inside a container hidden by class',
			html: '<div class="hidden"><a href="page.html"><img src="logo.jpg"></a></div>',
			shouldPass: true,
		},
		{
			name: 'passes when link is inside a container with the hidden attribute',
			html: '<div hidden><a href="page.html"><img src="logo.jpg"></a></div>',
			shouldPass: true,
		},
		{
			name: 'passes when link is inside an aria-hidden container',
			html: '<div aria-hidden="true"><a href="page.html"><img src="logo.jpg"></a></div>',
			shouldPass: true,
		},
		// A link hidden by its own visibility:hidden with a visible parent is deliberately
		// hidden, so its images are not reported either.
		{
			name: 'passes when link is hidden by visibility:hidden with a visible parent',
			html: '<a href="page.html" style="visibility: hidden;"><img src="logo.jpg"></a>',
			shouldPass: true,
		},
		{
			name: 'fails for image overriding a hidden link with visibility:visible and no alt',
			html: '<a href="page.html" style="visibility: hidden;"><img src="logo.jpg" style="visibility: visible;"></a>',
			shouldPass: false,
		},
		// Inside a hidden panel an image's own visibility:hidden cannot be told apart from
		// the inherited value, so it is still scanned. This pins that documented behaviour.
		{
			name: 'fails for image hidden by visibility:hidden inside a hidden panel',
			html: '<div style="visibility: hidden;"><a href="page.html"><img src="logo.jpg" style="visibility: hidden;"></a></div>',
			shouldPass: false,
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
