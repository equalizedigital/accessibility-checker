<?php
/**
 * Plugin Name: E2E helpers
 * Description: Test-only REST routes for the Playwright suite. Installed into the Playground instance by global-setup.js; never ships with the plugin.
 *
 * @package accessibility-checker
 */

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'edac-e2e/v1',
			'/reset-opt-in',
			[
				'methods'             => 'POST',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'callback'            => function () {
					delete_user_meta( get_current_user_id(), 'edac_email_optin_seen_modal' );
					delete_user_meta( get_current_user_id(), 'edac_email_optin' );
					return [ 'success' => true ];
				},
			]
		);
	}
);
