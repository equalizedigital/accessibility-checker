<?php
/**
 * Tests for keeping some rules' issues when a post's issues are purged.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Purge_Post_Data;
use EDAC\Admin\Update_Database;

/**
 * Tests Purge_Post_Data::delete_post()'s $preserved_rules parameter and the
 * edac_flush_preserved_rules filter on the clear-issues REST route.
 */
class PurgePostDataPreservedRulesTest extends WP_UnitTestCase {

	/**
	 * Post the issues belong to.
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * Create the issues table and a post with one issue for each of three rules.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		( new Update_Database() )->edac_update_database();
		update_option( 'edac_post_types', [ 'post' ] );

		$this->post_id = self::factory()->post->create();

		foreach ( [ 'img_alt_missing', 'empty_link', 'manual_rule' ] as $rule ) {
			$this->insert_issue( $this->post_id, $rule );
		}
	}

	/**
	 * Insert an issue row.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $rule    Rule slug.
	 * @return void
	 */
	private function insert_issue( int $post_id, string $rule ) {
		global $wpdb;
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test fixture.
			$wpdb->prefix . 'accessibility_checker',
			[
				'postid'       => $post_id,
				'siteid'       => get_current_blog_id(),
				'type'         => 'post',
				'rule'         => $rule,
				'ruletype'     => 'error',
				'object'       => '<p>' . $rule . '</p>',
				'selector'     => 'p',
				'recordcheck'  => 1,
				'user'         => 0,
				'ignre'        => 0,
				'ignre_global' => 0,
			]
		);
	}

	/**
	 * Rule slugs of the post's remaining issues.
	 *
	 * @param int $post_id Post ID.
	 * @return string[]
	 */
	private function remaining_rules( int $post_id ): array {
		global $wpdb;
		$rules = $wpdb->get_col( $wpdb->prepare( 'SELECT rule FROM %i WHERE postid = %d ORDER BY rule', $wpdb->prefix . 'accessibility_checker', $post_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Test assertion.
		return array_map( 'strval', $rules );
	}

	/**
	 * Without preserved rules every issue is deleted, as before.
	 */
	public function test_delete_post_deletes_everything_by_default(): void {
		Purge_Post_Data::delete_post( $this->post_id );

		$this->assertSame( [], $this->remaining_rules( $this->post_id ) );
	}

	/**
	 * Preserved rules' issues are kept and everything else is deleted.
	 */
	public function test_delete_post_keeps_preserved_rules(): void {
		Purge_Post_Data::delete_post( $this->post_id, [ 'manual_rule', 'empty_link' ] );

		$this->assertSame( [ 'empty_link', 'manual_rule' ], $this->remaining_rules( $this->post_id ) );
	}

	/**
	 * Empty strings in the preserved list are ignored rather than matched.
	 */
	public function test_delete_post_ignores_empty_preserved_values(): void {
		Purge_Post_Data::delete_post( $this->post_id, [ '' ] );

		$this->assertSame( [], $this->remaining_rules( $this->post_id ) );
	}

	/**
	 * Other posts' issues are untouched.
	 */
	public function test_delete_post_only_affects_the_given_post(): void {
		$other_post = self::factory()->post->create();
		$this->insert_issue( $other_post, 'img_alt_missing' );

		Purge_Post_Data::delete_post( $this->post_id, [ 'manual_rule' ] );

		$this->assertSame( [ 'img_alt_missing' ], $this->remaining_rules( $other_post ) );
	}

	/**
	 * The clear-issues REST route keeps the rules from edac_flush_preserved_rules.
	 */
	public function test_flush_keeps_rules_from_filter(): void {
		$filter = static function ( $rules ) {
			$rules[] = 'manual_rule';
			return $rules;
		};
		add_filter( 'edac_flush_preserved_rules', $filter );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		do_action( 'rest_api_init' );

		$request = new WP_REST_Request( 'POST', '/accessibility-checker/v1/clear-issues/' . $this->post_id );
		$request->set_param( 'id', $this->post_id );
		$request->set_body( wp_json_encode( [ 'flush' => true ] ) );
		$request->set_header( 'Content-Type', 'application/json' );
		$response = rest_get_server()->dispatch( $request );

		remove_filter( 'edac_flush_preserved_rules', $filter, 10 );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( [ 'manual_rule' ], $this->remaining_rules( $this->post_id ) );
	}

	/**
	 * The filter receives the ID of the post being cleared.
	 */
	public function test_flush_filter_receives_post_id(): void {
		$received = null;
		$filter   = static function ( $rules, $post_id ) use ( &$received ) {
			$received = $post_id;
			return $rules;
		};
		add_filter( 'edac_flush_preserved_rules', $filter, 10, 2 );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		do_action( 'rest_api_init' );

		$request = new WP_REST_Request( 'POST', '/accessibility-checker/v1/clear-issues/' . $this->post_id );
		$request->set_param( 'id', $this->post_id );
		$request->set_body( wp_json_encode( [ 'flush' => true ] ) );
		$request->set_header( 'Content-Type', 'application/json' );
		rest_get_server()->dispatch( $request );

		remove_filter( 'edac_flush_preserved_rules', $filter, 10 );

		$this->assertSame( $this->post_id, $received );
	}
}
