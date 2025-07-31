<?php
/**
 * Class SimplifiedSummaryBlockTest
 *
 * @package Accessibility_Checker
 */

use EDAC\Inc\Simplified_Summary_Block;

/**
 * Simplified_Summary_Block test case.
 */
class SimplifiedSummaryBlockTest extends WP_UnitTestCase {

	/**
	 * Instance of the Simplified_Summary_Block class.
	 *
	 * @var Simplified_Summary_Block $simplified_summary_block.
	 */
	private $simplified_summary_block;

	/**
	 * Set up the test fixture.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->simplified_summary_block = new Simplified_Summary_Block();
	}

	/**
	 * Test that block is registered when manual placement is enabled.
	 */
	public function test_block_registration_with_manual_placement() {
		// Set option to manual placement
		update_option( 'edac_simplified_summary_position', 'none' );
		
		// Initialize hooks
		$this->simplified_summary_block->init_hooks();
		
		// Trigger the init action to run our registration function
		do_action( 'init' );
		
		// Check if block is registered
		$this->assertTrue( WP_Block_Type_Registry::get_instance()->is_registered( 'accessibility-checker/simplified-summary' ) );
	}

	/**
	 * Test that block is not registered when manual placement is disabled.
	 */
	public function test_block_not_registered_without_manual_placement() {
		// Set option to automatic placement
		update_option( 'edac_simplified_summary_position', 'after' );
		
		// Initialize hooks
		$this->simplified_summary_block->init_hooks();
		
		// Trigger the init action to run our registration function
		do_action( 'init' );
		
		// Check if block is not registered
		$this->assertFalse( WP_Block_Type_Registry::get_instance()->is_registered( 'accessibility-checker/simplified-summary' ) );
	}

	/**
	 * Test block render with simplified summary content.
	 */
	public function test_block_render_with_content() {
		// Set option to manual placement
		update_option( 'edac_simplified_summary_position', 'none' );
		
		// Create a post with simplified summary
		$post_id = $this->factory->post->create();
		$summary_text = 'This is a test simplified summary.';
		update_post_meta( $post_id, '_edac_simplified_summary', $summary_text );
		
		// Create mock block context
		$block = new stdClass();
		$block->context = [ 'postId' => $post_id ];
		
		// Render the block
		$output = $this->simplified_summary_block->render_block( [], '', $block );
		
		// Check that output contains expected markup
		$this->assertStringContainsString( '<div class="edac-simplified-summary">', $output );
		$this->assertStringContainsString( '<h2>Simplified Summary</h2>', $output );
		$this->assertStringContainsString( '<p>' . $summary_text . '</p>', $output );
	}

	/**
	 * Test block render with no simplified summary content.
	 */
	public function test_block_render_without_content() {
		// Set option to manual placement
		update_option( 'edac_simplified_summary_position', 'none' );
		
		// Create a post without simplified summary
		$post_id = $this->factory->post->create();
		
		// Create mock block context
		$block = new stdClass();
		$block->context = [ 'postId' => $post_id ];
		
		// Render the block
		$output = $this->simplified_summary_block->render_block( [], '', $block );
		
		// Check that output is empty
		$this->assertEmpty( $output );
	}

	/**
	 * Test block render when manual placement is disabled.
	 */
	public function test_block_render_when_manual_placement_disabled() {
		// Set option to automatic placement
		update_option( 'edac_simplified_summary_position', 'after' );
		
		// Create a post with simplified summary
		$post_id = $this->factory->post->create();
		update_post_meta( $post_id, '_edac_simplified_summary', 'Test summary' );
		
		// Create mock block context
		$block = new stdClass();
		$block->context = [ 'postId' => $post_id ];
		
		// Render the block
		$output = $this->simplified_summary_block->render_block( [], '', $block );
		
		// Check that output is empty (block should not render)
		$this->assertEmpty( $output );
	}
}