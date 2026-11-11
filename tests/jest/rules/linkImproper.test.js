import axe from 'axe-core';

beforeAll( async () => {
	const ruleModule = await import( '../../../src/pageScanner/rules/link-improper.js' );
	const checkModule = await import( '../../../src/pageScanner/checks/link-has-valid-href-or-role.js' );

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

describe( 'Link Improper Rule', () => {
	test.each( [
		// ❌ Failing cases
		{
			name: 'Fails when anchor has no href and no role',
			html: '<a>Click me</a>',
			shouldPass: false,
		},
		{
			name: 'Fails when anchor has href="#" and no role',
			html: '<a href="#">Link</a>',
			shouldPass: false,
		},
		{
			name: 'Fails when anchor has javascript:void(0) href',
			html: '<a href="javascript:void(0)">Bad practice</a>',
			shouldPass: false,
		},
		{
			name: 'Fails when anchor has javascript: href with leading whitespace',
			html: '<a href="  javascript:alert(1)">Bad practice</a>',
			shouldPass: false,
		},
		{
			name: 'Fails when anchor href contains only whitespace',
			html: '<a href="   ">Whitespace link</a>',
			shouldPass: false,
		},
		{
			name: 'Fails when anchor href contains only whitespace',
			html: '<a href="   ">Whitespace link</a>',
			shouldPass: false,
		},
		{
			name: 'Fails when anchor has malformed URL',
			html: '<a href="http://example.com:invalid-port">Invalid URL</a>',
			shouldPass: false,
		},
		{
			name: 'Fails when anchor has data: protocol',
			html: '<a href="data:text/plain;base64,SGVsbG8sIFdvcmxkIQ==">Data URL</a>',
			shouldPass: false,
		},
		{
			name: 'Fails when anchor has file: protocol',
			html: '<a href="file:///C:/example.txt">Local file</a>',
			shouldPass: false,
		},

		// ✅ Passing cases
		{
			name: 'Passes with valid absolute URL',
			html: '<a href="https://example.com/page?param=value">Valid URL</a>',
			shouldPass: true,
		},
		{
			name: 'Passes with valid relative URL',
			html: '<a href="/path/to/page?query=string">Valid relative URL</a>',
			shouldPass: true,
		},
		{
			name: 'Passes with valid href',
			html: '<a href="/about">About</a>',
			shouldPass: true,
		},
		{
			name: 'Passes with href="#" and role="button"',
			html: '<a href="#" role="button">Click</a>',
			shouldPass: true,
		},
		{
			name: 'Passes with multiple roles including button',
			html: '<a href="#" role="link button primary">Multiple roles</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when hidden with display:none inline',
			html: '<a style="display:none;">Hidden link</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when hidden with visibility:hidden inline',
			html: '<a style="visibility:hidden;">Invisible link</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when hidden via class',
			html: '<a class="hidden">Class hidden</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when visually hidden via class',
			html: '<a class="invisible">Invisible class</a>',
			shouldPass: true,
		},
		{
			name: 'Passes with aria-hidden="true"',
			html: '<a aria-hidden="true">Aria hidden</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when aria-hidden is dynamically added',
			html: '<a id="dynamic-aria">Dynamic aria</a>',
			shouldPass: true,
			setup: () => {
				const element = document.getElementById( 'dynamic-aria' );
				element.setAttribute( 'aria-hidden', 'true' );
			},
		},
		{
			name: 'Passes with role="tab"',
			html: '<a href="#" role="tab">Link with role of tab</a>',
			shouldPass: true,
		},
		{
			name: 'Passes with role="menuitem" and aria-expanded',
			html: '<a href="#" role="menuitem" aria-haspopup="true" aria-expanded="false">Mega Menu</a>',
			shouldPass: true,
		},
		{
			name: 'Passes with role="menuitem" and aria-expanded="true"',
			html: '<a href="#" role="menuitem" aria-expanded="true">Expanded Menu</a>',
			shouldPass: true,
		},
		{
			name: 'Passes with multiple roles including menuitem and aria-expanded',
			html: '<a href="#" role="foo menuitem bar" aria-expanded="false">Multiple roles with menuitem</a>',
			shouldPass: true,
		},
		{
			name: 'Fails with role="menuitem" but no aria-expanded',
			html: '<a href="#" role="menuitem">Menu without aria-expanded</a>',
			shouldPass: false,
		},

		// Named anchor (jump target) cases
		{
			name: 'Passes when empty anchor is used as a named jump target',
			html: '<a id="meet-the-team"></a>',
			shouldPass: true,
		},
		{
			name: 'Passes when whitespace-only anchor is used as a named jump target',
			html: '<a id="section-top">   </a>',
			shouldPass: true,
		},
		{
			name: 'Passes when legacy name-only anchor is used as a jump target',
			html: '<a name="section-top"></a>',
			shouldPass: true,
		},
		{
			name: 'Fails when legacy name-only anchor has text',
			html: '<a name="section-top">Text</a>',
			shouldPass: false,
		},
		{
			name: 'Fails when anchor has id and text content but no href',
			html: '<a id="meet-the-team">Meet the Team</a>',
			shouldPass: false,
		},
		{
			name: 'Fails when anchor has id and empty href',
			html: '<a id="top" href=""></a>',
			shouldPass: false,
		},
		{
			name: 'Fails when anchor has id and child image but no href',
			html: '<a id="logo"><img src="logo.png" alt="Home"/></a>',
			shouldPass: false,
		},
		{
			name: 'Fails when anchor is keyboard focusable via tabindex and has no href',
			html: '<a id="meet-the-team" tabindex="0"></a>',
			shouldPass: false,
		},
		{
			name: 'Passes when anchor has tabindex of -1 and no href',
			html: '<a id="meet-the-team" tabindex="-1"></a>',
			shouldPass: true,
		},

		// Presentational role cases. See https://github.com/equalizedigital/accessibility-checker/issues/1748
		{
			name: 'Passes with role="none" on a non-focusable dropdown wrapper',
			html: '<a role="none"><span class="nav-drop-title-wrap">eBranch<span class="dropdown-nav-toggle"><span class="kadence-svg-iconset svg-baseline"><svg aria-hidden="true" class="kadence-svg-icon" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><title>Expand</title><path d="M5.293 9.707l6 6c0.391 0.391 1.024 0.391 1.414 0l6-6z"></path> </svg></span></span></span></a>',
			shouldPass: true,
		},
		{
			name: 'Passes with role="presentation" and no href',
			html: '<a role="presentation"><span>Menu</span></a>',
			shouldPass: true,
		},
		{
			name: 'Passes with multiple roles including none and no href',
			html: '<a role="foo none bar"><span>Menu</span></a>',
			shouldPass: true,
		},
		{
			name: 'Fails with role="none" when focusable via href="#"',
			html: '<a href="#" role="none">Click</a>',
			shouldPass: false,
		},
		{
			name: 'Fails with role="none" when focusable via tabindex',
			html: '<a role="none" tabindex="0">Click</a>',
			shouldPass: false,
		},
		{
			// tabindex="-1" is focusable programmatically and by click, so the presentational
			// role is ignored per the conflict resolution.
			name: 'Fails with role="none" and tabindex of -1',
			html: '<a role="none" tabindex="-1">Click</a>',
			shouldPass: false,
		},
		{
			name: 'Fails with role="none" and a global aria attribute',
			html: '<a role="none" aria-label="Open menu"><span>Menu</span></a>',
			shouldPass: false,
		},
		{
			name: 'Passes with role="none" and aria-hidden="true"',
			html: '<a role="none" aria-hidden="true"><span>Menu</span></a>',
			shouldPass: true,
		},

		{
			// The focusable anchor makes the browser ignore role="none", exposing a link, so the
			// later widget token must not exempt it. An unknown first token still falls through.
			name: 'Fails with role="none slider" when focusable via href="#"',
			html: '<a href="#" role="none slider" aria-valuenow="5">Click</a>',
			shouldPass: false,
		},
		{
			name: 'Fails with role="presentation button" when focusable via href="#"',
			html: '<a href="#" role="presentation button">Click</a>',
			shouldPass: false,
		},
		{
			name: 'Passes with an unknown role followed by slider and href="#"',
			html: '<a href="#" role="foo slider" aria-valuenow="5">Volume</a>',
			shouldPass: true,
		},
		{
			name: 'Fails with role="none" and aria-hidden="false"',
			html: '<a role="none" aria-hidden="false"><span>Menu</span></a>',
			shouldPass: false,
		},

		// Slider role cases. See https://github.com/equalizedigital/accessibility-checker/issues/1748
		{
			name: 'Passes with role="slider" on a media player volume control',
			html: '<a class="mejs-horizontal-volume-slider" href="javascript:void(0);" aria-label="Volume Slider" aria-valuemin="0" aria-valuemax="100" aria-valuenow="100" role="slider"><span class="mejs-offscreen">Use Up/Down Arrow keys to increase or decrease volume.</span><div class="mejs-horizontal-volume-total"><div class="mejs-horizontal-volume-current"></div><div class="mejs-horizontal-volume-handle"></div></div></a>',
			shouldPass: true,
		},
		{
			name: 'Passes with role="slider" and href="#"',
			html: '<a href="#" role="slider" aria-valuenow="50">Volume</a>',
			shouldPass: true,
		},
		{
			name: 'Passes with role="slider" and no href',
			html: '<a role="slider" tabindex="0" aria-valuenow="50">Volume</a>',
			shouldPass: true,
		},
		{
			// An inactive tab in a roving tabindex tablist is correct markup, so the widget
			// role exemption must not depend on the anchor being in the tab order.
			name: 'Passes with role="tab" and tabindex of -1',
			html: '<a role="tab" tabindex="-1" id="tab-2" aria-controls="panel-2">Tab 2</a>',
			shouldPass: true,
		},
	] )( '$name', async ( { html, shouldPass, setup } ) => {
		document.body.innerHTML = html;

		if ( setup ) {
			setup();
		}

		const results = await axe.run( document.body, {
			runOnly: [ 'link_improper' ],
		} );

		if ( shouldPass ) {
			expect( results.violations.length ).toBe( 0 );
		} else {
			expect( results.violations.length ).toBeGreaterThan( 0 );
		}
	} );
} );

describe( 'Link Improper Rule: presentational and widget roles, exact elements flagged', () => {
	const flaggedIds = async () => {
		const results = await axe.run( document.body, { runOnly: [ 'link_improper' ] } );
		return results.violations
			.flatMap( ( violation ) => violation.nodes )
			.map( ( node ) => node.target[ 0 ].replace( '#', '' ) )
			.sort();
	};

	test.each( [
		// Role token parsing: the first recognized token decides.
		{ name: 'role="NONE" is case-insensitive', html: '<a id="a" role="NONE"><span>Menu</span></a>', flagged: [] },
		{ name: 'role with surrounding whitespace', html: '<a id="a" role="  none  "><span>Menu</span></a>', flagged: [] },
		{ name: 'slider before none wins and passes', html: '<a id="a" href="#" role="slider none" aria-valuenow="1">x</a>', flagged: [] },
		{ name: 'button before none wins and passes', html: '<a id="a" href="#" role="button none">x</a>', flagged: [] },
		{ name: 'none before tab is ignored on a focusable anchor', html: '<a id="a" href="#" role="none tab">x</a>', flagged: [ 'a' ] },
		{ name: 'presentation before slider is ignored on a focusable anchor', html: '<a id="a" href="#" role="presentation slider" aria-valuenow="1">x</a>', flagged: [ 'a' ] },
		{ name: 'tab-separated none slider is ignored on a focusable anchor', html: '<a id="a" href="#" role="none\tslider" aria-valuenow="1">x</a>', flagged: [ 'a' ] },
		{ name: 'newline-separated none slider is ignored on a focusable anchor', html: '<a id="a" href="#" role="none\nslider" aria-valuenow="1">x</a>', flagged: [ 'a' ] },
		{ name: 'unknown role then none still passes when non-focusable', html: '<a id="a" role="foo none"><span>Menu</span></a>', flagged: [] },
		{ name: 'only unknown roles and no href is flagged', html: '<a id="a" role="foo bar"><span>Menu</span></a>', flagged: [ 'a' ] },
		{ name: 'empty role attribute and no href is flagged', html: '<a id="a" role=""><span>Menu</span></a>', flagged: [ 'a' ] },
		{ name: 'role="none" with a real href is still a valid link', html: '<a id="a" role="none" href="/about">About</a>', flagged: [] },

		// Focusability boundaries for presentational roles.
		{ name: 'presentation with tabindex="0" is flagged', html: '<a id="a" role="presentation" tabindex="0">x</a>', flagged: [ 'a' ] },
		{ name: 'presentation with tabindex="-1" is flagged', html: '<a id="a" role="presentation" tabindex="-1">x</a>', flagged: [ 'a' ] },
		{ name: 'none with an empty href is flagged', html: '<a id="a" role="none" href="">x</a>', flagged: [ 'a' ] },
		{ name: 'none with no content and no href passes', html: '<a id="a" role="none"></a>', flagged: [] },
		{ name: 'none wrapping an svg passes', html: '<a id="a" role="none"><svg width="10" height="10"><path d="M0 0h10v10z"></path></svg></a>', flagged: [] },

		// Global ARIA boundaries for presentational roles.
		{ name: 'none with aria-label is flagged', html: '<a id="a" role="none" aria-label="Open">x</a>', flagged: [ 'a' ] },
		{ name: 'none with aria-labelledby is flagged', html: '<a id="a" role="none" aria-labelledby="l">x</a>', flagged: [ 'a' ] },
		{ name: 'none with aria-describedby is flagged', html: '<a id="a" role="none" aria-describedby="d">x</a>', flagged: [ 'a' ] },
		{ name: 'none with aria-live is flagged', html: '<a id="a" role="none" aria-live="polite">x</a>', flagged: [ 'a' ] },
		// Pinned: any aria-* attribute counts, which errs toward reporting.
		{ name: 'none with a non-global aria-expanded is flagged', html: '<a id="a" role="none" aria-expanded="false">x</a>', flagged: [ 'a' ] },
		{ name: 'none with aria-hidden="TRUE" passes', html: '<a id="a" role="none" aria-hidden="TRUE">x</a>', flagged: [] },
		{ name: 'none with aria-hidden=" true " passes', html: '<a id="a" role="none" aria-hidden=" true ">x</a>', flagged: [] },
		{ name: 'none with an empty aria-hidden is flagged', html: '<a id="a" role="none" aria-hidden="">x</a>', flagged: [ 'a' ] },
		{ name: 'none with aria-hidden="false" is flagged', html: '<a id="a" role="none" aria-hidden="false">x</a>', flagged: [ 'a' ] },
		// axe skips aria-hidden="true" subtrees before the check runs, whatever else the anchor carries.
		{ name: 'aria-hidden="true" anchors are skipped even with another aria attribute', html: '<a id="a" role="none" aria-hidden="true" aria-label="x">x</a>', flagged: [] },
		{ name: 'none with only class, id, style, title and data attributes passes', html: '<a id="a" role="none" class="c" style="color:red" title="t" data-x="1">x</a>', flagged: [] },

		// Widget roles are unconditional: no dependence on href, tabindex or ARIA.
		{ name: 'slider with javascript: href', html: '<a id="a" href="javascript:void(0)" role="slider" aria-valuenow="1">x</a>', flagged: [] },
		{ name: 'slider with tabindex="-1"', html: '<a id="a" role="slider" tabindex="-1" aria-valuenow="1">x</a>', flagged: [] },
		{ name: 'SLIDER uppercase', html: '<a id="a" href="#" role="SLIDER" aria-valuenow="1">x</a>', flagged: [] },
		{ name: 'slider with aria-label', html: '<a id="a" href="#" role="slider" aria-label="Volume" aria-valuenow="1">x</a>', flagged: [] },
		{ name: 'tab with href="#"', html: '<a id="a" href="#" role="tab">x</a>', flagged: [] },
		{ name: 'button with javascript: href', html: '<a id="a" href="javascript:void(0)" role="button">x</a>', flagged: [] },

		// Only anchors are evaluated.
		{
			name: 'non-anchor elements with these roles are not evaluated',
			html: '<div id="d" role="none">x</div><span id="s" role="slider" aria-valuenow="1">x</span><button id="b" role="presentation">x</button>',
			flagged: [],
		},

		// Mixed pages: every anchor is judged on its own markup.
		{
			name: 'plain anchors are still flagged beside presentational and slider anchors',
			html: `
				<a id="plain-1">One</a>
				<a id="none-ok" role="none"><span>Menu</span></a>
				<a id="plain-2" href="#">Two</a>
				<a id="slider-ok" href="#" role="slider" aria-valuenow="1">Vol</a>
				<a id="presentation-ok" role="presentation"><span>Menu</span></a>
			`,
			flagged: [ 'plain-1', 'plain-2' ],
		},
		{
			name: 'realistic nav and media player page',
			html: `
				<nav><ul>
					<li><a id="home" href="/">Home</a></li>
					<li><a id="drop" role="none"><span>Products</span></a><ul><li><a id="sub" href="/products">All</a></li></ul></li>
					<li><a id="bad-hash" href="#">Menu</a></li>
					<li><a id="bad-focus" href="#" role="none">Toggle</a></li>
					<li><a id="bad-aria" role="none" aria-label="Toggle">Toggle</a></li>
					<li><a id="bad-order" href="#" role="none slider" aria-valuenow="1">Toggle</a></li>
				</ul></nav>
				<div class="player">
					<a id="vol" href="javascript:void(0)" role="slider" aria-valuenow="1"></a>
					<a id="bad-mute" href="javascript:void(0)">Mute</a>
				</div>
			`,
			flagged: [ 'bad-aria', 'bad-focus', 'bad-hash', 'bad-mute', 'bad-order' ],
		},
	] )( '$name', async ( { html, flagged } ) => {
		document.body.innerHTML = html;

		expect( await flaggedIds() ).toEqual( [ ...flagged ].sort() );
	} );
} );
