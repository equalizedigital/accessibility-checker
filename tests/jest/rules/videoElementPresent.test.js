import axe from 'axe-core';

beforeAll( async () => {
	// Dynamically import the modules
	const videoPresentRuleModule = await import( '../../../src/pageScanner/rules/video-present.js' );
	const videoPresentCheckModule = await import( '../../../src/pageScanner/checks/video-element-present.js' );

	const videoPresentRule = videoPresentRuleModule.default;
	const videoPresentCheck = videoPresentCheckModule.default;

	// Configure axe with the imported rules
	axe.configure( {
		rules: [ videoPresentRule ],
		checks: [ videoPresentCheck ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Video Element Detection', () => {
	const testCases = [
		// Passing (should trigger detection)
		{
			name: 'should detect <video> element',
			html: '<video src="movie.mp4" controls></video>',
			shouldPass: false,
		},
		{
			name: 'should detect element with role="video"',
			html: '<div role="video"></div>',
			shouldPass: false,
		},
		{
			name: 'should detect WordPress video block',
			html: '<div class="is-type-video"><div>Video here</div></div>',
			shouldPass: false,
		},
		{
			name: 'should detect YouTube iframe',
			html: '<iframe src="https://www.youtube.com/embed/videoId"></iframe>',
			shouldPass: false,
		},
		{
			name: 'should detect Vimeo iframe',
			html: '<iframe src="https://player.vimeo.com/video/123456"></iframe>',
			shouldPass: false,
		},
		{
			name: 'should detect <object> with video type',
			html: '<object data="movie.mp4" type="video/mp4"></object>',
			shouldPass: false,
		},
		{
			name: 'should detect element with .mp4 in src',
			html: '<source src="movie.mp4" type="video/mp4">',
			shouldPass: false,
		},
		{
			name: 'should detect known video player classes',
			html: '<div class="video-player"></div>',
			shouldPass: false,
		},

		// Negative cases (should NOT trigger detection)
		{
			name: 'should not detect unrelated div',
			html: '<div class="text-content">Just text</div>',
			shouldPass: true,
		},
		{
			name: 'should not detect iframe with non-video src',
			html: '<iframe src="https://example.com"></iframe>',
			shouldPass: true,
		},
		{
			name: 'should not detect object without video type',
			html: '<object data="something.swf" type="application/x-shockwave-flash"></object>',
			shouldPass: true,
		},
	];

	testCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			document.body.innerHTML = testCase.html;

			const results = await axe.run( document.body, {
				runOnly: [ 'video_present' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
			}
		} );
	} );
} );
