<?php 

/**
 * Purge deleted posts
 *
 * @param $null
 * @param $post
 * @param $force_delete
 * @return void
 */
function edac_delete_post($post_id)
{	
	global $wpdb;
	$site_id = get_current_blog_id();

	$wpdb->query( $wpdb->prepare( 'DELETE FROM '.$wpdb->prefix.'accessibility_checker WHERE postid = %d and siteid = %d', $post_id, $site_id) );

	edac_delete_post_meta($post_id);
}

/**
 * Delete post meta
 *
 * @param int $post_id
 * @return void
 */
function edac_delete_post_meta($post_id){

	if(!$post_id) return;

	$post_meta = get_post_meta($post_id);
	if($post_meta){
		foreach ($post_meta as $key => $value) {
			if(substr( $key, 0, 5 ) === "_edac" || substr( $key, 0, 6 ) === "_edacp" ){
				delete_post_meta($post_id, $key);
			}
		}
	}
}

/**
 * Purge issues by post type
 *
 * @param string $post_type
 * @return void
 */
function edac_delete_cpt_posts($post_type){

	if(!$post_type) return;

	global $wpdb;
	$site_id = get_current_blog_id();

	// get all post of the current post type
	$posts = $wpdb->get_results( $wpdb->prepare( 'SELECT postid FROM '.$wpdb->prefix.'accessibility_checker WHERE type = %s and siteid = %d', $post_type, $site_id), ARRAY_A );

	// delete post meta
	if($posts){
		foreach ($posts as $post) {
			edac_delete_post_meta($post['postid']);
		}
	}

	// delete issues by post type
	$results = $wpdb->query( $wpdb->prepare( 'DELETE FROM '.$wpdb->prefix.'accessibility_checker WHERE type = %s and siteid = %d', $post_type, $site_id) );

	return $results;

}