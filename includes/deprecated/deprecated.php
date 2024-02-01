<?php
/**
 * Functions that have been deprecated and should not be used.
 * They are still kept here for backwards-compatibility.
 * 
 * @package Accessibility_Checker
 */

/**
 * Alias of the is_plugin_active() function.
 * 
 * @deprecated 1.6.11
 *
 * @param string $plugin_slug The plugin slug.
 * @return bool
 */
function edac_check_plugin_active( $plugin_slug ) {
	_deprecated_function( __FUNCTION__, '1.6.11', 'is_plugin_active()' );
	return is_plugin_active( $plugin_slug );
}
