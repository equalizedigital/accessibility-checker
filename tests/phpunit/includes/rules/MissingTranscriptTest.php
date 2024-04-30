<?php
/**
 * Test the missing transcript rule functionality.
 *
 * @package accessibility-checker
 */

/**
 * Test vases for the missing transcript rule.
 *
 * @group rules
 */
class MissingTranscriptTest extends WP_UnitTestCase {

	/**
	 * Test the rule catches a video with a missing transcript.
	 */
	public function test_catch_video_with_missing_transcript() {
		// Create a new post.
		$post_id = $this->factory()->post->create();

		// Create a new attachment.
		$attachment_id = $this->factory()->attachment->create_object(
			'video.mp4',
			$post_id,
			[
				'post_mime_type' => 'video/mp4',
			]
		);

		// add the attachement to the post content.
		$post               = get_post( $post_id );
		$post->post_content = '<a href="' . wp_get_attachment_url( $attachment_id ) . '">Video</a>';
		wp_update_post( $post );

		// Run the rule.
		$errors = edac_rule_missing_transcript( [], $post );

		$this->assertNotEmpty( $errors );
	}

	/**
	 * Test the rule doesn't flag a link that has an extension we match against in the url.
	 */
	public function test_doesnt_flag_link_with_extension() {
		// Create a new post.
		$post_id = $this->factory()->post->create();

		$post               = get_post( $post_id );
		$post->post_content = '<a href="https://www.mpdl.mpg.de/en/ ">Some Link</a>';
		wp_update_post( $post );

		// Run the rule.
		$errors = edac_rule_missing_transcript( [], $post );

		$this->assertEmpty( $errors );
	}

	/**
	 * Test the rule captures a video attachment in the post with no transcript.
	 */
	public function test_flags_video_only_post_as_missing_transcript() {
		// Create a new post.
		$post_id = $this->factory()->post->create();

		// Create a new attachment.
		$attachment_id = $this->factory()->attachment->create_object(
			'video.mp4',
			$post_id,
			[
				'post_mime_type' => 'video/mp4',
			]
		);

		// add the attachment to the post content.
		$post               = get_post( $post_id );
		$post->post_content = '<a href="' . wp_get_attachment_url( $attachment_id ) . '">Video</a>';

		$this->assertNotEmpty( edac_rule_missing_transcript( [], $post ) );
	}

	/**
	 * Verify that the rule detects a transcript nearby.
	 */
	public function test_detects_transcript_nearby() {
		$post_id = $this->factory()->post->create();

		$attachment_id = $this->factory()->attachment->create_object(
			'video.mp4',
			$post_id,
			[
				'post_mime_type' => 'video/mp4',
			]
		);

		$post               = get_post( $post_id );
		$post->post_content = '<a href="' . wp_get_attachment_url( $attachment_id ) . '">Video</a><p>Transcript</p>';

		$this->assertEmpty( edac_rule_missing_transcript( [], $post ) );
	}

	/**
	 * Test that it only detects transcripts in close enough proximity.
	 */
	public function test_doesnt_detect_transcript_far_away() {
		// Create a new post.
		$post_id = $this->factory()->post->create();

		// Create a new attachment.
		$attachment_id = $this->factory()->attachment->create_object(
			'video.mp4',
			$post_id,
			[
				'post_mime_type' => 'video/mp4',
			]
		);

		$attachment_string = '<a href="' . wp_get_attachment_url( $attachment_id ) . '">Video</a>';
		$transcript_string = '<p>Transcript</p>';

		// add the attachment to the post content.
		$post               = get_post( $post_id );
		$post->post_content = $attachment_string . $transcript_string;

		// should detect the transcript.
		$this->assertEmpty( edac_rule_missing_transcript( [], $post ) );

		// still should find it.
		$post->post_content = $attachment_string . 'Some short text' . $transcript_string;
		$this->assertEmpty( edac_rule_missing_transcript( [], $post ) );

		// should find even if there is a tag with a lot of characters in the attributes between the two.
		$post->post_content = $attachment_string . '<span class="aReallyReallyLongClassname" aria-hidden="true">Icon</span>' . $transcript_string;
		$this->assertEmpty( edac_rule_missing_transcript( [], $post ) );

		// should still find it even with a long svg and long attributes between both.
		$post->post_content = $attachment_string . '<span class="aReallyReallyLongClassname" aria-hidden="true"><svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="40" stroke="green" stroke-width="4" fill="yellow" /></svg></span>' . $transcript_string;
		$this->assertEmpty( edac_rule_missing_transcript( [], $post ) );

		// should not find it since it's over 25 characters of content away.
		$post->post_content = $attachment_string . 'Some long text that is longer than 25 characters' . $transcript_string;
		$this->assertNotEmpty( edac_rule_missing_transcript( [], $post ) );
	}
}
