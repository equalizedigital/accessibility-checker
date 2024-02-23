<?php

use PHPUnit\Framework\TestCase;
use EDAC\Admin\Purge_Post_Data;

class PurgePostDataTest extends TestCase {

	public function setUp(): void {
		$this->valid_post_id = 1;
	}

	public function purgePostDeletesDataAndMeta() {
		// Mock global $wpdb object and functions.
		$wpdb            = $this->createMock( stdClass::class );
		$wpdb->prefix    = 'wp_';
		$GLOBALS['wpdb'] = $wpdb;

		$wpdb->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'DELETE FROM' ) );

		Purge_Post_Data::delete_post( $this->valid_post_id );
	}

	public function purgePostMetaDeletesCorrectMeta() {
		// Mock global $wpdb object and functions.
		$wpdb            = $this->createMock( stdClass::class );
		$wpdb->prefix    = 'wp_';
		$GLOBALS['wpdb'] = $wpdb;

		$wpdb->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'DELETE FROM' ) );

		Purge_Post_Data::delete_post_meta( $this->valid_post_id );
	}

	public function purgeCptPostsDeletesCorrectData() {
		// Mock global $wpdb object and functions.
		$wpdb            = $this->createMock( stdClass::class );
		$wpdb->prefix    = 'wp_';
		$GLOBALS['wpdb'] = $wpdb;

		$post_type = 'post';

		$wpdb->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'DELETE FROM' ) );

		Purge_post_Data::delete_cpt_posts( $post_type );
	}

	public function testDeletePostRemovesPostFromDatabase() {
		global $wpdb;
		$wpdb = $this->createMock( wpdb::class );

		$wpdb->prefix   = 'wp_';
		$wpdb->postmeta = 'postmeta';

		// Mock the prepare method to return a SQL query.
		$wpdb->method( 'prepare' )
			->willReturn( 'DELETE FROM table WHERE postid = 1 and siteid = 1' );

		$wpdb->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'DELETE FROM' ) );

		Purge_Post_Data::delete_post( $this->valid_post_id );
	}

	public function testDeletePostMetaRemovesMetaFromDatabase() {
		$post_meta = array(
			'_edac'  => 'value',
			'_edacp' => 'value',
			'other'  => 'value',
		);
		foreach ( $post_meta as $key => $value ) {
			add_post_meta( $this->valid_post_id, $key, $value );
		}

		$this->assertNotEmpty( get_post_meta( $this->valid_post_id, '_edac', true ) );
		$this->assertNotEmpty( get_post_meta( $this->valid_post_id, '_edacp', true ) );

		Purge_Post_Data::delete_post_meta( $this->valid_post_id );

		$this->assertEmpty( get_post_meta( $this->valid_post_id, '_edac', true ) );
		$this->assertEmpty( get_post_meta( $this->valid_post_id, '_edacp', true ) );
		$other_meta = get_post_meta( $this->valid_post_id, 'other', true );
		$this->assertNotEmpty( get_post_meta( $this->valid_post_id, 'other', true ), 'other contained:' . $other_meta );
	}

	public function testDeletePostMetaDoesNothingForInvalidPostId() {
		Purge_Post_Data::delete_post_meta( 0 );

		$this->assertTrue( true );
	}

	public function testDeleteCptPostsRemovesPostsFromDatabase() {
		global $wpdb;
		$wpdb = $this->createMock( wpdb::class );
		$wpdb->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'DELETE T1,T2 from' ) );

		Purge_Post_Data::delete_cpt_posts( 'custom_post_type' );
	}

	public function testDeleteCptPostsDoesNothingForInvalidPostType() {
		Purge_Post_Data::delete_cpt_posts( '' );

		$this->assertTrue( true );
	}
}
