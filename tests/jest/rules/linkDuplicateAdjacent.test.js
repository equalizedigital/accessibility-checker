import axe from 'axe-core';

beforeAll( async () => {
	const ruleModule = await import( '../../../src/pageScanner/rules/link-duplicate-adjacent.js' );
	const checkModule = await import( '../../../src/pageScanner/checks/link-has-adjacent-duplicate.js' );

	axe.configure( {
		rules: [ ruleModule.default ],
		checks: [ checkModule.default ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Link Duplicate Adjacent Rule', () => {
	test.each( [
		// ❌ Failing cases - adjacent links to the same destination
		{
			name: 'Fails when an image link is immediately followed by a text link to the same page',
			html: '<a href="/post-1"><img src="thumb.jpg" alt="Post one" /></a><a href="/post-1">Read the full story</a>',
			shouldPass: false,
		},
		{
			name: 'Fails when links are separated only by whitespace',
			html: '<a href="/post-1">Photo</a>\n\t<a href="/post-1">Read more</a>',
			shouldPass: false,
		},
		{
			name: 'Fails when hrefs resolve to the same absolute URL (relative vs absolute)',
			html: `<a href="/post-1">Photo</a><a href="${ window.location.origin }/post-1">Read more</a>`,
			shouldPass: false,
		},
		{
			name: 'Fails when three identical links sit in a row',
			html: '<a href="/post-1">A</a><a href="/post-1">B</a><a href="/post-1">C</a>',
			shouldPass: false,
		},

		{
			name: 'Fails when an HTML comment sits between the links',
			html: '<a href="/post-1">Photo</a><!-- template marker --><a href="/post-1">Read more</a>',
			shouldPass: false,
		},
		{
			name: 'Fails when only one link is aria-hidden but still in the tab order',
			html: '<a href="/post-1" aria-hidden="true"><img src="thumb.jpg" alt="" /></a><a href="/post-1">Read more</a>',
			shouldPass: false,
		},
		{
			name: 'Fails when only one link has tabindex -1 but is still exposed to assistive technology',
			html: '<a href="/post-1" tabindex="-1"><img src="thumb.jpg" alt="" /></a><a href="/post-1">Read more</a>',
			shouldPass: false,
		},

		// ✅ Passing cases
		{
			name: 'Passes after the documented fix (first link aria-hidden and tabindex -1)',
			html: '<a href="/post-1" aria-hidden="true" tabindex="-1"><img src="thumb.jpg" alt="" /></a><a href="/post-1">Read more</a>',
			shouldPass: true,
		},
		{
			name: 'Passes after the documented fix (second link aria-hidden and tabindex -1)',
			html: '<a href="/post-1">Read more</a><a href="/post-1" aria-hidden="true" tabindex="-1"><img src="thumb.jpg" alt="" /></a>',
			shouldPass: true,
		},
		{
			name: 'Passes when the link sits inside an aria-hidden wrapper and has tabindex -1',
			html: '<span aria-hidden="true"><a href="/post-1" tabindex="-1">Photo</a></span><a href="/post-1">Read more</a>',
			shouldPass: true,
		},
		{
			name: 'Passes for adjacent javascript: links that run different actions',
			html: '<a href="javascript:void(0)">Previous</a><a href="javascript:void(0)">Next</a>',
			shouldPass: true,
		},
		{
			name: 'Passes for javascript: links regardless of case and spacing',
			html: '<a href="  JavaScript:;">Previous</a><a href="  JavaScript:;">Next</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when links have empty hrefs',
			html: '<a href="">A</a><a href="">B</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when adjacent links point to different destinations',
			html: '<a href="/post-1">Photo</a><a href="/post-2">Read more</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when a non-whitespace element separates the links',
			html: '<a href="/post-1">Photo</a><span> | </span><a href="/post-1">Read more</a>',
			shouldPass: true,
		},
		{
			name: 'Passes for a single link with no adjacent sibling link',
			html: '<a href="/post-1">Read more</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when the duplicate link is not adjacent (separated by another element)',
			html: '<a href="/post-1">Photo</a><p>Some text</p><a href="/post-1">Read more</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when links have a placeholder # href',
			html: '<a href="#">A</a><a href="#">B</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when one of the adjacent elements is not a link',
			html: '<a href="/post-1">Photo</a><button>Read more</button>',
			shouldPass: true,
		},
	] )( '$name', async ( { html, shouldPass } ) => {
		document.body.innerHTML = html;

		const results = await axe.run( document.body, {
			runOnly: [ 'link_duplicate_adjacent' ],
		} );

		if ( shouldPass ) {
			expect( results.violations.length ).toBe( 0 );
		} else {
			expect( results.violations.length ).toBeGreaterThan( 0 );
		}
	} );
} );
