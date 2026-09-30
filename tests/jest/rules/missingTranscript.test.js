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
		{
			name: 'passes YouTube iframe with transcript link nearby',
			html: `
			<figure>
				<div class="wp-block-embed__wrapper">
					<iframe src="https://www.youtube.com/embed/ABC123?feature=oembed&amp;enablejsapi=1&amp;origin=https://example.com" title="Sample Video Title" width="980" height="551" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" class="_iub_cs_activate perfmatters-lazy entered pmloaded _iub_cs_activate-activated" data-iub-purposes="3" data-src="//cdn.iubenda.com/cookie_solution/empty.html" data-ll-status="loaded" data-cmp-ab="2" data-cmp-info="8" async="false"></iframe>
					<noscript>
						<iframe title="Sample Video Title" width="980" height="551" src="//cdn.iubenda.com/cookie_solution/empty.html" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen suppressedsrc="https://www.youtube.com/embed/ABC123?feature=oembed&amp;enablejsapi=1&amp;origin=https://example.com" class=" _iub_cs_activate" data-iub-purposes="3"></iframe>
					</noscript>
				</div>
			</figure>
			<h3 class="wp-block-heading"><a href="https://example.com/transcript-sample-video/">(Access a full transcript of the above video.)</a></h3>
			`,
			shouldPass: true,
		},
		{
			name: 'passes Vimeo iframe with transcript link nearby',
			html: `
			<div>
				<div>
					<iframe src="https://player.vimeo.com/video/123456789123456789"></iframe>
				</div>
				<a href="https://www.example.com/123456789123456789-transcript">Transcript</a>
			</div>
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
		{
			name: 'finds transcript 4 additional steps up the DOM tree',
			html: `
				<div>
					<div>
						<div>
							<div>
								<div>
									<video src="video.mp4"></video>
								</div>
							</div>
						</div>
					</div>
					<p>Transcript available nearby.</p>
				</div>
			`,
			shouldPass: true,
		},
		{
			name: 'finds transcript 5 additional steps up the DOM tree',
			html: `
				<div>
					<div>
						<div>
							<div>
								<div>
									<div>
										<video src="video.mp4"></video>
									</div>
								</div>
							</div>
						</div>
					</div>
					<p>Transcript available nearby.</p>
				</div>
			`,
			shouldPass: true,
		},
		// Able Player transcripts (https://github.com/equalizedigital/accessibility-checker/issues/1740)
		{
			name: 'passes video with data-transcript-div pointing to a non-empty Able Player transcript',
			html: `
				<video id="able-player-1" src="video.mp4" data-able-player data-transcript-div="able-player-transcript-1"></video>
				<div id="able-player-transcript-1">
					<div class="able-transcript-area">
						<div class="able-transcript">
							<div class="able-transcript-container" lang="en">
								<span class="able-transcript-caption">Spoken words from the video.</span>
							</div>
						</div>
					</div>
				</div>
			`,
			shouldPass: true,
		},
		{
			name: 'flags video with data-transcript-div pointing to an empty Able Player transcript',
			html: `
				<video id="able-player-2" src="video.mp4" data-able-player data-transcript-div="able-player-transcript-2"></video>
				<div id="able-player-transcript-2">
					<div class="able-transcript-area">
						<div class="able-window-toolbar">
							<label for="autoscroll-checkbox-2">Auto scroll</label>
							<input id="autoscroll-checkbox-2" type="checkbox">
						</div>
						<div class="able-transcript">
							<div class="able-transcript-container" lang="en"></div>
						</div>
					</div>
				</div>
			`,
			shouldPass: false,
		},
		{
			name: 'passes video when data-transcript-div points directly at the transcript container element',
			html: `
				<video id="able-player-3" src="video.mp4" data-able-player data-transcript-div="able-player-transcript-3"></video>
				<div id="able-player-transcript-3" class="able-transcript-container" lang="en">Spoken words from the video.</div>
			`,
			shouldPass: true,
		},
		{
			name: 'passes video with Able Player WP plugin transcript container id pattern',
			html: `
				<video id="able_player_6" src="video.mp4"></video>
				<div id="ableplayer-transcript-able_player_6" class="ableplayer-transcript">
					<div class="able-transcript-area">
						<div class="able-transcript">
							<div class="able-transcript-container" lang="en-US">Spoken words from the video.</div>
						</div>
					</div>
				</div>
			`,
			shouldPass: true,
		},
		{
			name: 'flags video with empty Able Player WP plugin transcript container',
			html: `
				<video id="able_player_7" src="video.mp4"></video>
				<div id="ableplayer-transcript-able_player_7" class="ableplayer-transcript">
					<div class="able-transcript-area">
						<div class="able-transcript">
							<div class="able-transcript-container" lang="en-US"></div>
						</div>
					</div>
				</div>
			`,
			shouldPass: false,
		},
		{
			name: 'passes video with a non-empty Able Player transcript rendered inside the player wrapper',
			html: `
				<div class="able-wrapper">
					<video src="video.mp4"></video>
					<div class="able-transcript-area">
						<div class="able-transcript">
							<div class="able-transcript-container" lang="en">Spoken words from the video.</div>
						</div>
					</div>
				</div>
			`,
			shouldPass: true,
		},
		{
			name: 'flags video with an empty Able Player transcript rendered inside the player wrapper',
			html: `
				<div class="able-wrapper">
					<video src="video.mp4"></video>
					<div class="able-transcript-area">
						<div class="able-transcript">
							<div class="able-transcript-container" lang="en"></div>
						</div>
					</div>
				</div>
			`,
			shouldPass: false,
		},
		{
			name: 'should not find transcript 6 up the DOM tree',
			html: `
				<div>
					<div>
						<div>
							<div>
								<div>
									<div>
										<div>
											<video src="video.mp4"></video>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<p>Transcript available nearby.</p>
				</div>
			`,
			shouldPass: false,
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

	test( 'flags only the Able Player video whose own transcript is empty when multiple players are on the page', async () => {
		document.body.innerHTML = `
			<video id="able-player-1" src="video-one.mp4" data-able-player data-transcript-div="able-player-transcript-1"></video>
			<div id="able-player-transcript-1">
				<div class="able-transcript">
					<div class="able-transcript-container" lang="en">Spoken words from the first video.</div>
				</div>
			</div>
			<video id="able-player-2" src="video-two.mp4" data-able-player data-transcript-div="able-player-transcript-2"></video>
			<div id="able-player-transcript-2">
				<div class="able-transcript">
					<div class="able-transcript-container" lang="en"></div>
				</div>
			</div>
		`;

		const results = await axe.run( document.body, {
			runOnly: [ 'missing_transcript' ],
		} );

		expect( results.violations.length ).toBe( 1 );
		expect( results.violations[ 0 ].nodes.length ).toBe( 1 );
		expect( results.violations[ 0 ].nodes[ 0 ].target[ 0 ] ).toBe( '#able-player-2' );
	} );

	test( 'flags only the video without its own transcript when two players share an ancestor without explicit transcript association', async () => {
		document.body.innerHTML = `
			<div>
				<div class="able-wrapper">
					<video id="shared-ancestor-video-1" src="video-one.mp4"></video>
					<div class="able-transcript">
						<div class="able-transcript-container" lang="en">Spoken words from the first video.</div>
					</div>
				</div>
				<video id="shared-ancestor-video-2" src="video-two.mp4"></video>
			</div>
		`;

		const results = await axe.run( document.body, {
			runOnly: [ 'missing_transcript' ],
		} );

		expect( results.violations.length ).toBe( 1 );
		expect( results.violations[ 0 ].nodes.length ).toBe( 1 );
		expect( results.violations[ 0 ].nodes[ 0 ].target[ 0 ] ).toBe( '#shared-ancestor-video-2' );
	} );

	test( 'credits the iframe Able Player generates for YouTube but flags an unrelated YouTube iframe on the same page', async () => {
		document.body.innerHTML = `
			<div>
				<div class="able-wrapper">
					<div class="able">
						<div class="able-media-container">
							<video id="able-youtube-video" data-youtube-id="abc123" data-able-player></video>
							<iframe id="able-youtube-iframe" src="https://www.youtube.com/embed/abc123"></iframe>
						</div>
					</div>
					<div class="able-transcript-area">
						<div class="able-transcript">
							<div class="able-transcript-container" lang="en">Spoken words from the video.</div>
						</div>
					</div>
				</div>
				<iframe id="unrelated-youtube-iframe" src="https://www.youtube.com/embed/xyz789"></iframe>
			</div>
		`;

		const results = await axe.run( document.body, {
			runOnly: [ 'missing_transcript' ],
		} );

		expect( results.violations.length ).toBe( 1 );
		expect( results.violations[ 0 ].nodes.length ).toBe( 1 );
		expect( results.violations[ 0 ].nodes[ 0 ].target[ 0 ] ).toBe( '#unrelated-youtube-iframe' );
	} );

	describe( 'Able Player mixed with other media', () => {
		const wrapper = ( mediaHtml, transcript = 'Spoken words from the video.' ) => `
			<div class="able-wrapper">
				<div class="able">
					<div class="able-media-container">${ mediaHtml }</div>
				</div>
				<div class="able-transcript-area">
					<div class="able-transcript">
						<div class="able-transcript-container" lang="en">${ transcript }</div>
					</div>
				</div>
			</div>
		`;
		const ableVideo = ( id, transcript ) => wrapper( `<video id="${ id }" src="${ id }.mp4"></video>`, transcript );
		const ableAudio = ( id, transcript ) => wrapper( `<audio id="${ id }" src="${ id }.mp3"></audio>`, transcript );
		const ableYouTube = ( id, transcript ) => wrapper(
			`<video id="${ id }" data-youtube-id="abc"></video><iframe id="${ id }-iframe" src="https://www.youtube.com/embed/abc"></iframe>`,
			transcript
		);
		const plainVideo = ( id ) => `<video id="${ id }" src="${ id }.mp4"></video>`;
		const plainAudio = ( id ) => `<audio id="${ id }" src="${ id }.mp3"></audio>`;
		const plainYouTube = ( id ) => `<iframe id="${ id }" src="https://www.youtube.com/embed/xyz"></iframe>`;
		const plainVimeo = ( id ) => `<iframe id="${ id }" src="https://player.vimeo.com/video/123"></iframe>`;
		const plainLink = ( id ) => `<a id="${ id }" href="${ id }.mp4">Watch</a>`;
		const externalPlayer = ( id, transcriptId, transcript = 'Spoken words from the video.' ) => `
			<video id="${ id }" src="${ id }.mp4" data-able-player data-transcript-div="${ transcriptId }"></video>
			<div id="${ transcriptId }"><div class="able-transcript-container" lang="en">${ transcript }</div></div>
		`;

		test.each( [
			{
				name: 'plain video after an Able Player',
				html: `<div>${ ableVideo( 'ap' ) }${ plainVideo( 'plain' ) }</div>`,
				flagged: [ 'plain' ],
			},
			{
				name: 'plain video before an Able Player',
				html: `<div>${ plainVideo( 'plain' ) }${ ableVideo( 'ap' ) }</div>`,
				flagged: [ 'plain' ],
			},
			{
				name: 'Able Player between two plain videos',
				html: `<div>${ plainVideo( 'before' ) }${ ableVideo( 'ap' ) }${ plainVideo( 'after' ) }</div>`,
				flagged: [ 'before', 'after' ],
			},
			{
				name: 'Able Player audio next to a plain audio',
				html: `<div>${ ableAudio( 'ap' ) }${ plainAudio( 'plain' ) }</div>`,
				flagged: [ 'plain' ],
			},
			{
				name: 'Able Player next to plain YouTube, Vimeo, audio and media link',
				html: `<div>
					${ plainYouTube( 'yt' ) }${ ableVideo( 'ap' ) }${ plainVimeo( 'vimeo' ) }
					${ plainAudio( 'audio' ) }${ plainLink( 'link' ) }
				</div>`,
				flagged: [ 'yt', 'vimeo', 'audio', 'link' ],
			},
			{
				name: 'Able Player YouTube (video + generated iframe) between unrelated YouTube iframes',
				html: `<div>${ plainYouTube( 'yt-1' ) }${ ableYouTube( 'ap' ) }${ plainYouTube( 'yt-2' ) }</div>`,
				flagged: [ 'yt-1', 'yt-2' ],
			},
			{
				name: 'two populated Able Players side by side',
				html: `<div>${ ableVideo( 'ap-1' ) }${ ableVideo( 'ap-2' ) }</div>`,
				flagged: [],
			},
			{
				name: 'two Able Players where only the first is empty',
				html: `<div>${ ableVideo( 'ap-1', '' ) }${ ableVideo( 'ap-2' ) }</div>`,
				flagged: [ 'ap-1' ],
			},
			{
				name: 'two Able Players where only the second is empty',
				html: `<div>${ ableVideo( 'ap-1' ) }${ ableVideo( 'ap-2', '' ) }</div>`,
				flagged: [ 'ap-2' ],
			},
			{
				name: 'Able Player YouTube with an empty transcript flags both its video and iframe',
				html: `<div>${ ableYouTube( 'ap-1' ) }${ ableYouTube( 'ap-2', '' ) }</div>`,
				flagged: [ 'ap-2', 'ap-2-iframe' ],
			},
			{
				name: 'mixed Able Player types (video, audio, YouTube) each use their own transcript',
				html: `<div>${ ableVideo( 'ap-v' ) }${ ableAudio( 'ap-a', '' ) }${ ableYouTube( 'ap-y' ) }</div>`,
				flagged: [ 'ap-a' ],
			},
			{
				name: 'external transcript player between plain media',
				html: `<div>${ plainVideo( 'before' ) }${ externalPlayer( 'ap', 'ap-transcript' ) }${ plainYouTube( 'after' ) }</div>`,
				flagged: [ 'before', 'after' ],
			},
			{
				name: 'external transcript and in-wrapper players each resolve to their own transcript',
				html: `<div>
					${ externalPlayer( 'ext-ok', 'ext-ok-transcript' ) }
					${ externalPlayer( 'ext-empty', 'ext-empty-transcript', '' ) }
					${ ableVideo( 'wrapped-ok' ) }
					${ ableVideo( 'wrapped-empty', '' ) }
				</div>`,
				flagged: [ 'ext-empty', 'wrapped-empty' ],
			},
			{
				name: 'shortcode-pattern player next to a plain video and an unrelated iframe',
				html: `<div>
					<video id="sc_1" src="sc.mp4"></video>
					<div id="ableplayer-transcript-sc_1"><div class="able-transcript-container">Spoken words.</div></div>
					${ plainVideo( 'plain' ) }${ plainYouTube( 'yt' ) }
				</div>`,
				flagged: [ 'plain', 'yt' ],
			},
			{
				name: 'plain video with its own transcript mention passes while an empty Able Player beside it is flagged',
				html: `<div>
					<section>${ plainVideo( 'plain' ) }<p>Transcript available below.</p></section>
					<section>${ ableVideo( 'ap', '' ) }</section>
				</div>`,
				flagged: [ 'ap' ],
			},
		] )( '$name', async ( { html, flagged } ) => {
			document.body.innerHTML = html;

			const results = await axe.run( document.body, {
				runOnly: [ 'missing_transcript' ],
			} );

			const flaggedIds = results.violations
				.flatMap( ( violation ) => violation.nodes )
				.map( ( node ) => node.target[ 0 ].replace( '#', '' ) )
				.sort();

			expect( flaggedIds ).toEqual( [ ...flagged ].sort() );
		} );
	} );
} );
