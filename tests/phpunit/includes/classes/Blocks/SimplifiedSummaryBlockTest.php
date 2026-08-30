<?php
/**
 * Class SimplifiedSummaryBlockTest
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Blocks\SimplifiedSummaryBlock;

/**
 * SimplifiedSummaryBlock test case.
 */
class SimplifiedSummaryBlockTest extends WP_UnitTestCase {

	/**
	 * Instance of the SimplifiedSummaryBlock class.
	 *
	 * @var SimplifiedSummaryBlock
	 */
	private $block;

	/**
	 * Set up the test fixture.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->block = new SimplifiedSummaryBlock();

		// The registry persists across tests while the scripts registry resets,
		// so unregister first to exercise a full registration every time.
		if ( WP_Block_Type_Registry::get_instance()->is_registered( SimplifiedSummaryBlock::BLOCK_NAME ) ) {
			unregister_block_type( SimplifiedSummaryBlock::BLOCK_NAME );
		}
		$this->block->register();
	}

	/**
	 * Tests that the block and its editor script are registered.
	 *
	 * @return void
	 */
	public function test_register_registers_block_and_script() {
		$this->assertTrue( WP_Block_Type_Registry::get_instance()->is_registered( SimplifiedSummaryBlock::BLOCK_NAME ) );
		$this->assertTrue( wp_script_is( SimplifiedSummaryBlock::SCRIPT_HANDLE, 'registered' ) );
		$this->assertTrue( wp_style_is( SimplifiedSummaryBlock::STYLE_HANDLE, 'registered' ) );
	}

	/**
	 * Tests that the block category is registered once and the block uses it.
	 *
	 * @return void
	 */
	public function test_register_block_category() {
		$categories = $this->block->register_block_category( [] );
		$this->assertContains( SimplifiedSummaryBlock::CATEGORY, wp_list_pluck( $categories, 'slug' ) );

		// Running the filter again must not duplicate the category.
		$categories = $this->block->register_block_category( $categories );
		$this->assertCount( 1, $categories );

		$block_type = WP_Block_Type_Registry::get_instance()->get_registered( SimplifiedSummaryBlock::BLOCK_NAME );
		$this->assertSame( SimplifiedSummaryBlock::CATEGORY, $block_type->category );
	}

	/**
	 * Tests that render uses the postId block context.
	 *
	 * @return void
	 */
	public function test_render_uses_post_id_context() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_edac_simplified_summary', 'Context summary.' );

		$block = new WP_Block(
			[ 'blockName' => SimplifiedSummaryBlock::BLOCK_NAME ],
			[ 'postId' => $post_id ]
		);

		$output = $this->block->render( [], '', $block );
		$this->assertStringContainsString( 'Context summary.', $output );
		$this->assertStringContainsString( 'edac-simplified-summary', $output );
	}

	/**
	 * Tests that render falls back to the global post without context.
	 *
	 * @return void
	 */
	public function test_render_falls_back_to_global_post() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_edac_simplified_summary', 'Global post summary.' );

		global $post;
		$post = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Setting up the loop for the test.
		setup_postdata( $post );

		$block = new WP_Block( [ 'blockName' => SimplifiedSummaryBlock::BLOCK_NAME ] );

		$output = $this->block->render( [], '', $block );
		$this->assertStringContainsString( 'Global post summary.', $output );
	}

	/**
	 * Tests that render returns an empty string when there is no summary.
	 *
	 * @return void
	 */
	public function test_render_returns_empty_string_without_summary() {
		$post_id = self::factory()->post->create();

		$block = new WP_Block(
			[ 'blockName' => SimplifiedSummaryBlock::BLOCK_NAME ],
			[ 'postId' => $post_id ]
		);

		$this->assertSame( '', $this->block->render( [], '', $block ) );
	}

	/**
	 * Tests that render returns an empty string when there is no post at all.
	 *
	 * @return void
	 */
	public function test_render_returns_empty_string_without_post() {
		global $post;
		$post = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Clearing the loop for the test.

		$block = new WP_Block( [ 'blockName' => SimplifiedSummaryBlock::BLOCK_NAME ] );

		$this->assertSame( '', $this->block->render( [], '', $block ) );
	}

	/**
	 * Tests that render does not expose a password-protected post's summary
	 * when the postId comes from block context (e.g. a Query Loop).
	 *
	 * @return void
	 */
	public function test_render_does_not_render_password_protected_post_via_context() {
		$post_id = self::factory()->post->create( [ 'post_password' => 'secret' ] );
		update_post_meta( $post_id, '_edac_simplified_summary', 'Protected summary.' );

		$block = new WP_Block(
			[ 'blockName' => SimplifiedSummaryBlock::BLOCK_NAME ],
			[ 'postId' => $post_id ]
		);

		$this->assertSame( '', $this->block->render( [], '', $block ) );
	}

	/**
	 * Tests that render does not expose the current post's summary when that
	 * post itself is password-protected, since the block can sit outside the
	 * template area that enforces the password wall.
	 *
	 * @return void
	 */
	public function test_render_does_not_render_password_protected_current_post() {
		$post_id = self::factory()->post->create( [ 'post_password' => 'secret' ] );
		update_post_meta( $post_id, '_edac_simplified_summary', 'Protected summary.' );

		$this->go_to( get_permalink( $post_id ) );

		$block = new WP_Block( [ 'blockName' => SimplifiedSummaryBlock::BLOCK_NAME ] );

		$this->assertSame( '', $this->block->render( [], '', $block ) );
	}

	/**
	 * Tests that render does not expose summaries of draft or private posts
	 * referenced via block context (e.g. a Query Loop) other than the one
	 * being viewed.
	 *
	 * @return void
	 */
	public function test_render_does_not_render_non_public_post_via_context() {
		$draft_id = self::factory()->post->create( [ 'post_status' => 'draft' ] );
		update_post_meta( $draft_id, '_edac_simplified_summary', 'Draft summary.' );

		$private_id = self::factory()->post->create( [ 'post_status' => 'private' ] );
		update_post_meta( $private_id, '_edac_simplified_summary', 'Private summary.' );

		$this->go_to( '/' );

		$draft_block   = new WP_Block(
			[ 'blockName' => SimplifiedSummaryBlock::BLOCK_NAME ],
			[ 'postId' => $draft_id ]
		);
		$private_block = new WP_Block(
			[ 'blockName' => SimplifiedSummaryBlock::BLOCK_NAME ],
			[ 'postId' => $private_id ]
		);

		$this->assertSame( '', $this->block->render( [], '', $draft_block ) );
		$this->assertSame( '', $this->block->render( [], '', $private_block ) );
	}

	/**
	 * Tests that the block renders end-to-end through do_blocks.
	 *
	 * @return void
	 */
	public function test_block_renders_through_do_blocks() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_edac_simplified_summary', 'Do blocks summary.' );

		global $post;
		$post = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Setting up the loop for the test.
		setup_postdata( $post );

		$output = do_blocks( '<!-- wp:edac/simplified-summary /-->' );
		$this->assertStringContainsString( 'Do blocks summary.', $output );
		$this->assertStringContainsString( 'wp-block-edac-simplified-summary', $output );
	}

	/**
	 * Tests that the block renders even when the prompt option is set to none.
	 *
	 * Manual placement is a deliberate act and is not gated by the
	 * edac_simplified_summary_prompt option.
	 *
	 * @return void
	 */
	public function test_render_ignores_prompt_none_option() {
		update_option( 'edac_simplified_summary_prompt', 'none' );

		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_edac_simplified_summary', 'Prompt none summary.' );

		$block = new WP_Block(
			[ 'blockName' => SimplifiedSummaryBlock::BLOCK_NAME ],
			[ 'postId' => $post_id ]
		);

		$output = $this->block->render( [], '', $block );
		$this->assertStringContainsString( 'Prompt none summary.', $output );
	}
}
