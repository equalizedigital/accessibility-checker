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
		{
			name: 'detects .ogg file used as a native <video> element src',
			html: '<video src="movie.ogg" controls></video>',
			shouldPass: false,
		},
		{
			name: 'detects .ogg source that is not inside an <audio> element',
			html: '<source src="clip.ogg">',
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
			// Direct/minimal case: the <audio> element itself has a .ogg src, exercising the
			// `tag === 'audio'` path in is-video-detected.js without any wrapping markup.
			name: 'does not detect a plain <audio> element with a .ogg src',
			html: '<audio controls src="simple-guitar-melody.ogg"></audio>',
			shouldPass: true,
		},
		{
			// Regression test for https://github.com/equalizedigital/accessibility-checker/issues/1816 (PRO-1168).
			// The Gutenberg Audio block's default sample audio is an .ogg (Ogg Vorbis) file, which was
			// being misidentified as video content because .ogg is also a valid Ogg Theora video extension.
			name: 'does not detect .ogg audio file in a <figure class="wp-block-audio"> Audio block',
			html: '<figure class="wp-block-audio"><audio controls src="simple-guitar-melody.ogg"></audio><figcaption>Simple Guitar Melody</figcaption></figure>',
			shouldPass: true,
		},
		{
			name: 'does not detect .ogg source element inside an <audio> element',
			html: '<audio controls><source src="simple-guitar-melody.ogg" type="audio/ogg"></audio>',
			shouldPass: true,
		},
		{
			name: 'does not detect YouTube API script tag',
			html: '<script type="text/javascript" src="https://www.youtube.com/iframe_api?ver=1.2.6" id="youtube-scripts-js"></script>',
			shouldPass: true,
		},
		{
			// Regression test for https://linear.app/equalize-digital/issue/PRO-1229.
			// A featured image whose filename happens to contain "youtube" (e.g. a
			// screenshot of a YouTube video) was being misidentified as an embedded
			// video because the keyword match applied to any [src] element, not just
			// iframe/embed-style elements.
			name: 'does not detect an <img> whose filename merely contains the keyword "youtube"',
			html: '<img src="screenshot-2026-05-21-at-16-53-07-claims-department-youtube.jpg" alt="Claims Department" />',
			shouldPass: true,
		},
		{
			name: 'does not detect an <img> whose filename merely contains the keyword "vimeo"',
			html: '<img src="team-photo-vimeo-conference-2026.png" alt="Team photo" />',
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
