<?php 

/**
 * Purge deleted posts
 *
 * @param $null
 * @param $post
 * @param $force_delete
 * @return void
 */
function edac_delete_post( $null, $post, $force_delete )
{	
	if($post->post_type == 'revision') return;

	global $wpdb;
	$site_id = get_current_blog_id();

	$wpdb->query( $wpdb->prepare( 'DELETE FROM '.$wpdb->prefix.'accessibility_checker WHERE postid = %d and type = %s and siteid = %d', $post->ID, $post->post_type, $site_id) );

}