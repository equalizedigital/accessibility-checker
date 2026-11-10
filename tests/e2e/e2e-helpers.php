<?php
/**
 * Plugin Name: E2E helpers
 * Description: Test-only REST routes for the Playwright suite. Installed into the Playground instance by global-setup.js; never ships with the plugin.
 *
 * @package accessibility-checker
 */

/**
 * The user meta the opt-in modal reads.
 *
 * `edac_email_optin_seen_modal` records that the admin has closed the modal, and
 * `edac_email_optin` records their subscription choice. Both are written as `true`
 * (see `EDAC\Admin\Email_Opt_In`), which is why the state below is booleans.
 */
const EDAC_E2E_OPT_IN_META = [
	'edac_email_optin_seen_modal',
	'edac_email_optin',
];

/**
 * The current state of the opt-in user meta.
 *
 * `true` for a key that is set (to anything) and `false` for one that is not: an
 * absent `edac_email_optin_seen_modal` is what makes the modal show, so "not set"
 * has to survive the round trip for a restore to be exact.
 *
 * @return array<string, bool> Keyed by meta key.
 */
function edac_e2e_opt_in_state() {
	$user_id = get_current_user_id();
	$state   = [];

	foreach ( EDAC_E2E_OPT_IN_META as $key ) {
		$state[ $key ] = metadata_exists( 'user', $user_id, $key );
	}

	return $state;
}

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
					$user_id  = get_current_user_id();
					$previous = edac_e2e_opt_in_state();

					foreach ( EDAC_E2E_OPT_IN_META as $key ) {
						delete_user_meta( $user_id, $key );
					}

					// Hand the previous state back so the caller can put it back.
					return [
						'success'  => true,
						'previous' => $previous,
					];
				},
			]
		);

		register_rest_route(
			'edac-e2e/v1',
			'/restore-opt-in',
			[
				'methods'             => 'POST',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => [
					'previous' => [
						'required'          => true,
						'type'              => 'object',
						'validate_callback' => function ( $value ) {
							// Only the two known keys, so a caller cannot write arbitrary meta.
							return is_array( $value ) && ! array_diff( array_keys( $value ), EDAC_E2E_OPT_IN_META );
						},
					],
				],
				'callback'            => function ( \WP_REST_Request $request ) {
					$user_id  = get_current_user_id();
					$previous = (array) $request->get_param( 'previous' );

					foreach ( EDAC_E2E_OPT_IN_META as $key ) {
						if ( empty( $previous[ $key ] ) ) {
							delete_user_meta( $user_id, $key );
							continue;
						}

						update_user_meta( $user_id, $key, true );
					}

					return [ 'success' => true ];
				},
			]
		);
	}
);
