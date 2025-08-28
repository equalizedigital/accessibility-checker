import axe from 'axe-core';

beforeAll( async () => {
	// Dynamically import the modules
	const videoPresentRuleModule = await import( '../../../src/pageScanner/rules/video-present.js' );
	const videoPresentCheckModule = await import( '../../../src/pageScanner/checks/is-video-detected.js' );

	const videoPresentRule = videoPresentRuleModule.default;
	const videoPresentCheck = videoPresentCheckModule.default;

	// Configure axe with the imported rule and check
	axe.configure( {
		rules: [ videoPresentRule ],
		checks: [ videoPresentCheck ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'video_present rule', () => {
	const testCases = [
		// Should trigger violations
		{
			name: 'detects native <video> element',
			html: '<video src="movie.mp4" controls></video>',
			shouldPass: false,
		},
		{
			name: 'detects YouTube iframe embed',
			html: '<iframe src="https://www.youtube.com/embed/example"></iframe>',
			shouldPass: false,
		},
		{
			name: 'detects Vimeo iframe embed',
			html: '<iframe src="https://player.vimeo.com/video/123456"></iframe>',
			shouldPass: false,
		},
		{
			name: 'detects element with .mp4 in src',
			html: '<img src="example.mp4" />',
			shouldPass: false,
		},
		{
			name: 'detects object element with video type',
			html: '<object data="movie.mp4" type="video/mp4"></object>',
			shouldPass: false,
		},
		{
			name: 'detects source element with video type',
			html: '<source src="trailer.mov" type="video/quicktime">',
			shouldPass: false,
		},
		{
			name: 'detects element with role="video"',
			html: '<div role="video"></div>',
			shouldPass: false,
		},

		// Additional iframe tests
		{
			name: 'detects YouTube iframe embed with youtu.be shortlink',
			html: '<iframe src="https://youtu.be/abc123"></iframe>',
			shouldPass: false,
		},
		{
			name: 'detects YouTube iframe embed with query parameters',
			html: '<iframe src="https://www.youtube.com/embed/example?autoplay=1&controls=0"></iframe>',
			shouldPass: false,
		},
		{
			name: 'detects iframe with video extension in src',
			html: '<iframe src="https://example.com/presentation.mp4"></iframe>',
			shouldPass: false,
		},

		// HTML5 video source element tests
		{
			name: 'detects video element with source child',
			html: '<video controls><source src="movie.mp4" type="video/mp4"></video>',
			shouldPass: false,
		},
		{
			name: 'detects source element as direct child of video',
			html: '<video><source src="movie.webm" type="video/webm"></video>',
			shouldPass: false,
		},
		{
			name: 'detects source element with video extension but no type',
			html: '<video><source src="movie.mp4"></video>',
			shouldPass: false,
		},
		{
			name: 'detects source element with query parameters',
			html: '<video><source src="movie.mp4?version=2&token=abc"></video>',
			shouldPass: false,
		},
		{
			name: 'detects source element with mixed case extension',
			html: '<video><source src="movie.MP4"></video>',
			shouldPass: false,
		},

		// Should not trigger violations
		{
			name: 'does not detect unrelated <div>',
			html: '<div class="text-content">No video here</div>',
			shouldPass: true,
		},
		{
			name: 'does not detect iframe with non-video source',
			html: '<iframe src="https://example.com"></iframe>',
			shouldPass: true,
		},
		{
			name: 'does not detect object with non-video type',
			html: '<object data="something.swf" type="application/x-shockwave-flash"></object>',
			shouldPass: true,
		},
		{
			name: 'does not detect source with non-video extension',
			html: '<source src="audio.mp3" type="audio/mpeg">',
			shouldPass: true,
		},

		// Additional non-violation cases for better coverage
		{
			name: 'does not detect iframe with youtube in text but not src',
			html: '<iframe src="https://example.com"></iframe><p>YouTube videos are great</p>',
			shouldPass: true,
		},
		{
			name: 'does not detect source element with audio inside audio tag',
			html: '<audio controls><source src="sound.mp3" type="audio/mpeg"></audio>',
			shouldPass: true,
		},
		{
			name: 'does not detect YouTube API script tag',
			html: '<script type="text/javascript" src="https://www.youtube.com/iframe_api?ver=1.2.6" id="youtube-scripts-js"></script>',
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
