<?php
/**
 * Effective license context for Accessibility Checker.
 *
 * @package Accessibility_Checker
 * @since   x.x.x
 */

namespace EqualizeDigital\AccessibilityChecker\Admin;

/**
 * Class LicenseContext
 *
 * Works out which license is authoritative on a site (Pro or the free
 * connection) and whether the site is connected. Shared by the license notices
 * and the Accessibility Reports page so both agree on the answer.
 *
 * @since x.x.x
 */
class LicenseContext {

	/**
	 * Resolve the effective license context from the stored license options.
	 *
	 * Pro is only treated as authoritative when it is both installed and valid.
	 * Otherwise the free plugin state is used.
	 *
	 * @since x.x.x
	 *
	 * @return array{has_pro_plugin:bool,is_pro:bool,status:string,is_connected:bool,is_registered:bool,fallback_active:bool}
	 */
	public static function get_current(): array {
		$has_pro_plugin  = defined( 'EDACP_VERSION' );
		$pro_status      = (string) get_option( 'edacp_license_status', '' );
		$free_status     = (string) get_option( 'edac_license_status', '' );
		$site_id         = (string) get_option( 'edac_site_id', '' );
		$fallback_active = (bool) get_option( 'edac_fallback_active', false );

		return self::resolve( $has_pro_plugin, $pro_status, $free_status, $site_id, $fallback_active );
	}

	/**
	 * Resolve effective license context from current status values.
	 *
	 * @since x.x.x
	 *
	 * @param bool   $has_pro_plugin  Whether the Pro plugin is installed.
	 * @param string $pro_status      Current Pro license status.
	 * @param string $free_status     Current free license status.
	 * @param string $site_id         Current connected site ID.
	 * @param bool   $fallback_active Whether a fallback from Pro to Free is currently active.
	 * @return array{has_pro_plugin:bool,is_pro:bool,status:string,is_connected:bool,is_registered:bool,fallback_active:bool}
	 */
	public static function resolve( bool $has_pro_plugin, string $pro_status, string $free_status, string $site_id, bool $fallback_active = false ): array {
		$is_pro = $has_pro_plugin && 'valid' === $pro_status && ! $fallback_active;
		$status = $is_pro ? $pro_status : $free_status;
		// is_connected is true when:
		// 1. License status is valid AND site_id exists, OR
		// 2. Pro exists but is expired/degraded (fallback_active), AND site_id exists (reports still flowing).
		$is_connected  = ( 'valid' === $status && '' !== $site_id ) || ( $has_pro_plugin && $fallback_active && '' !== $site_id );
		$is_registered = '' !== $site_id;

		return [
			'has_pro_plugin'  => $has_pro_plugin,
			'is_pro'          => $is_pro,
			'status'          => $status,
			'is_connected'    => $is_connected,
			'is_registered'   => $is_registered,
			'fallback_active' => $fallback_active,
		];
	}
}
