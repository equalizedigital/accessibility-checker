import axe from 'axe-core';

beforeAll( async () => {
	const ruleModule = await import( '../../../src/pageScanner/rules/missing-transcript.js' );
	const checkModule = await import( '../../../src/pageScanner/checks/transcript-missing.js' );

	const missingTranscriptRule = ruleModule.default;
	const transcriptMissingCheck = checkModule.default;

	axe.configure( {
		rules: [ missingTranscriptRule ],
		checks: [ transcriptMissingCheck ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Missing Transcript Rule', () => {
	test.each( [
		// ❌ Failing cases — should trigger violations
		{
			name: 'flags <audio> without transcript nearby',
			html: '<audio src="podcast.mp3" controls></audio>',
			shouldPass: false,
		},
		{
			name: 'flags <video> without transcript nearby',
			html: '<video src="movie.mp4" controls></video>',
			shouldPass: false,
		},
		{
			name: 'flags YouTube iframe without transcript',
			html: '<iframe src="https://www.youtube.com/embed/videoid"></iframe>',
			shouldPass: false,
		},
		{
			name: 'flags Vimeo iframe without transcript',
			html: '<iframe src="https://player.vimeo.com/video/123456"></iframe>',
			shouldPass: false,
		},
		{
			name: 'flags <a> linking to .mp3 file without transcript',
			html: '<a href="episode.mp3">Listen now</a>',
			shouldPass: false,
		},
		{
			name: 'flags <a> linking to .mp4 file without transcript',
			html: '<a href="video.mp4">Watch here</a>',
			shouldPass: false,
		},

		// ✅ Passing cases — should not trigger violations
		{
			name: 'passes <audio> with transcript in sibling',
			html: '<audio src="podcast.mp3"></audio><p>Transcript available below.</p>',
			shouldPass: true,
		},
		{
			name: 'passes <video> with nearby transcript text',
			html: '<div><video src="movie.mp4"></video><p>This video has a transcript.</p></div>',
			shouldPass: true,
		},
		{
			name: 'passes iframe with transcript mention in wrapper',
			html: '<div><iframe src="https://www.youtube.com/embed/xyz"></iframe><p>Transcript available.</p></div>',
			shouldPass: true,
		},
		{
			name: 'passes <a> to media file with transcript in surrounding text',
			html: '<div><a href="file.wav">Download audio</a><p>Transcript below the player.</p></div>',
			shouldPass: true,
		},

		// ✅ Negative (non-relevant) elements — should not trigger violations
		{
			name: 'ignores mailto link',
			html: '<a href="mailto:hello@example.com">Email us</a>',
			shouldPass: true,
		},
		{
			name: 'ignores tel link',
			html: '<a href="tel:1234567890">Call us</a>',
			shouldPass: true,
		},
		{
			name: 'ignores regular div',
			html: '<div class="content">Just text here</div>',
			shouldPass: true,
		},
	] )( '$name', async ( { html, shouldPass } ) => {
		document.body.innerHTML = html;

		const results = await axe.run( document.body, {
			runOnly: [ 'missing_transcript' ],
		} );

		if ( shouldPass ) {
			expect( results.violations.length ).toBe( 0 );
		} else {
			expect( results.violations.length ).toBeGreaterThan( 0 );
		}
	} );
} );
