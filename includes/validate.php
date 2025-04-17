<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Remove corrected posts
 *
 * @param int    $post_ID The ID of the post.
 * @param string $type    The type of the post.
 * @param int    $pre     The flag indicating the removal stage (1 for before validation php based rules, 2 for after validation).
 * @param string $ruleset    The type of the ruleset to correct (php or js).
 *
 * @return void
 */
function edac_remove_corrected_posts( $post_ID, $type, $pre = 1, $ruleset = 'php' ) {
	global $wpdb;

	$rules          = edac_register_rules();
	$js_rule_slugs  = [];
	$php_rule_slugs = [];
	// Separate the JS rules and the PHP rules.
	foreach ( $rules as $rule ) {
		if ( isset( $rule['ruleset'] ) && 'js' === $rule['ruleset'] ) {
			$js_rule_slugs[] = $rule['slug'];
		} else {
			$php_rule_slugs[] = $rule['slug'];
		}
	}
	// Operate only on the slugs for the ruleset we are checking in this call.
	$rule_slugs = 'js' === $ruleset ? $js_rule_slugs : $php_rule_slugs;
	if ( 0 === count( $rule_slugs ) ) {
		return;
	}

	if ( 1 === $pre ) {

		// Set record flag before validating content.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for adding data to database, caching not required for one time operation.
		$wpdb->query(
			$wpdb->prepare(
				sprintf(
					"UPDATE {$wpdb->prefix}accessibility_checker SET recordcheck = %%d WHERE siteid = %%d and postid = %%d and type = %%s AND rule IN (%s)",
					implode( ',', array_fill( 0, count( $rule_slugs ), '%s' ) )
				),
				array_merge(
					[ 0, get_current_blog_id(), $post_ID, $type ],
					$rule_slugs
				)
			)
		);

	} elseif ( 2 === $pre ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for adding data to database, caching not required for one time operation.
		$wpdb->query(
			$wpdb->prepare(
				sprintf(
					"DELETE FROM {$wpdb->prefix}accessibility_checker WHERE siteid = %%d and postid = %%d and type = %%s and recordcheck = %%d AND rule IN (%s)",
					implode( ',', array_fill( 0, count( $rule_slugs ), '%s' ) )
				),
				array_merge(
					[ get_current_blog_id(), $post_ID, $type, 0 ],
					$rule_slugs
				)
			)
		);
	}
}
