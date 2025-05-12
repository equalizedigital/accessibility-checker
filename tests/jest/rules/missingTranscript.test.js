import axe from 'axe-core';

beforeAll( async () => {
	const ruleModule = await import( '../../../src/pageScanner/rules/missing-transcript.js' );
	const checkModule = await import( '../../../src/pageScanner/checks/has-transcript.js' );

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
			html: '<audio src="audio.mp3" controls></audio>',
			shouldPass: false,
		},
		{
			name: 'flags <video> without transcript nearby',
			html: '<video src="video.mp4" controls></video>',
			shouldPass: false,
		},
		{
			name: 'flags iframe (YouTube) without transcript',
			html: '<iframe src="https://www.youtube.com/embed/xyz"></iframe>',
			shouldPass: false,
		},
		{
			name: 'flags iframe (Vimeo) without transcript',
			html: '<iframe src="https://player.vimeo.com/video/123"></iframe>',
			shouldPass: false,
		},
		{
			name: 'flags <a> linking to mp3 without transcript',
			html: '<a href="episode.mp3">Listen here</a>',
			shouldPass: false,
		},
		{
			name: 'flags <a> linking to mp4 without transcript',
			html: '<a href="clip.mp4">Watch now</a>',
			shouldPass: false,
		},

		// ✅ Passing cases — transcript nearby or semantically present
		{
			name: 'passes <audio> with "transcript" in sibling',
			html: '<audio src="audio.mp3"></audio><p>Transcript available below.</p>',
			shouldPass: true,
		},
		{
			name: 'passes <video> with nearby "transcription"',
			html: '<video src="video.mp4"></video><p>Full transcription is available.</p>',
			shouldPass: true,
		},
		{
			name: 'passes iframe with "text version" nearby',
			html: '<iframe src="https://www.youtube.com/embed/abc"></iframe><p>Text version of this video available.</p>',
			shouldPass: true,
		},
		{
			name: 'passes iframe with "written version" nearby',
			html: '<iframe src="https://player.vimeo.com/video/789"></iframe><p>Written version is below the video.</p>',
			shouldPass: true,
		},
		{
			name: 'passes link to audio file with transcript in wrapper',
			html: '<div><a href="song.ogg">Listen</a><p>The transcript can be found below.</p></div>',
			shouldPass: true,
		},
		{
			name: 'passes with aria-describedby pointing to transcript',
			html: `
				<p id="transcript-id">This is the transcript of the media content.</p>
				<video src="movie.mp4" aria-describedby="transcript-id"></video>
			`,
			shouldPass: true,
		},

		// ✅ Negative (non-relevant) elements — no violation
		{
			name: 'ignores mailto link',
			html: '<a href="mailto:info@example.com">Email us</a>',
			shouldPass: true,
		},
		{
			name: 'ignores tel link',
			html: '<a href="tel:1234567890">Call us</a>',
			shouldPass: true,
		},
		{
			name: 'ignores plain content block',
			html: '<div class="text">Just some info</div>',
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
