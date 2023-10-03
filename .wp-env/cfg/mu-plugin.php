<?php


// Prevent accidental deletion of the plugin source folders.
add_filter(
	'plugin_action_links',
	function ( $actions, $plugin_file, $plugin_data, $context ) {
		if ( 'accessibility-checker/accessibility-checker.php' == $plugin_file ||
			'accessibility-checker-pro/accessibility-checker-pro.php' == $plugin_file
		) {
			unset( $actions['delete'] );
		}
		return $actions;
	},
	10,
	4
);

/**
 * Disable welcome guides in Gutenberg.
 * see: https://epiph.yt/en/blog/2022/disable-welcome-guide-in-gutenberg-even-in-widgets/
 */

function my_disable_welcome_guides() {
	wp_add_inline_script(
		'wp-data',
		"window.onload = function() {
	const selectPost = wp.data.select( 'core/edit-post' );
	const selectPreferences = wp.data.select( 'core/preferences' );
	const isFullscreenMode = selectPost.isFeatureActive( 'fullscreenMode' );
	const isWelcomeGuidePost = selectPost.isFeatureActive( 'welcomeGuide' );
	const isWelcomeGuideWidget = selectPreferences.get( 'core/edit-widgets', 'welcomeGuide' );
	
	if ( isWelcomeGuideWidget ) {
		wp.data.dispatch( 'core/preferences' ).toggle( 'core/edit-widgets', 'welcomeGuide' );
	}
	
	if ( isFullscreenMode ) {
		wp.data.dispatch( 'core/edit-post' ).toggleFeature( 'fullscreenMode' );
	}
	
	if ( isWelcomeGuidePost ) {
		wp.data.dispatch( 'core/edit-post' ).toggleFeature( 'welcomeGuide' );
	}
}" 
	);
}

add_action( 'enqueue_block_editor_assets', 'my_disable_welcome_guides', 20 );
add_action( 'wp_enqueue_scripts', 'my_disable_welcome_guides' );