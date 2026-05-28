<?php
/**
 * Test cases for the SystemInfo class.
 *
 * @package accessibility-checker
 */

use EqualizeDigital\AccessibilityChecker\SystemInfo\SystemInfo;

/**
 * Tests for SystemInfo static helper methods.
 */
class SystemInfoTest extends WP_UnitTestCase {
	/**
	 * Returns normalized tags for a theme.
	 *
	 * @param WP_Theme $theme Theme object.
	 * @return array<int, string>
	 */
	private function get_theme_tags( WP_Theme $theme ) {
		$tags = $theme->get( 'Tags' );
		return is_array( $tags ) ? $tags : [];
	}
	/**
	 * Computes expected accessibility-ready status from theme and parent tags.
	 *
	 * @param WP_Theme $theme Theme object.
	 * @return bool
	 */
	private function compute_expected_accessibility_ready( WP_Theme $theme ) {
		$tags = $this->get_theme_tags( $theme );
		if ( $theme->parent() ) {
			$tags = array_merge( $tags, $this->get_theme_tags( $theme->parent() ) );
		}
		return in_array( 'accessibility-ready', $tags, true );
	}
	/**
	 * Finds the first installed theme matching the given predicate.
	 *
	 * @param callable $predicate Matcher callback.
	 * @return WP_Theme|null
	 */
	private function find_installed_theme( $predicate ) {
		foreach ( wp_get_themes() as $theme ) {
			if ( ! $theme instanceof WP_Theme || ! $theme->exists() ) {
				continue;
			}
			if ( call_user_func( $predicate, $theme ) ) {
				return $theme;
			}
		}
		return null;
	}

	/**
	 * A plugin living in its own directory should return the directory name.
	 *
	 * @return void
	 */
	public function testGetPluginSlugFromPathReturnsDirectoryNameForStandardPlugin() {
		$path   = WP_PLUGIN_DIR . '/my-plugin/my-plugin.php';
		$result = SystemInfo::get_plugin_slug_from_path( $path );

		$this->assertSame( 'my-plugin', $result );
	}

	/**
	 * A single-file plugin (directly in the plugins root) returns the filename without extension.
	 *
	 * @return void
	 */
	public function testGetPluginSlugFromPathReturnsBareFilenameForSingleFilePlugin() {
		$path   = WP_PLUGIN_DIR . '/hello.php';
		$result = SystemInfo::get_plugin_slug_from_path( $path );

		$this->assertSame( 'hello', $result );
	}

	/**
	 * An empty string returns an empty string without error.
	 *
	 * @return void
	 */
	public function testGetPluginSlugFromPathReturnsEmptyStringForEmptyInput() {
		$result = SystemInfo::get_plugin_slug_from_path( '' );

		$this->assertSame( '', $result );
	}

	/**
	 * A non-string value returns an empty string without error.
	 *
	 * @return void
	 */
	public function testGetPluginSlugFromPathReturnsEmptyStringForNonStringInput() {
		// @phpstan-ignore-next-line
		$result = SystemInfo::get_plugin_slug_from_path( null );

		$this->assertSame( '', $result );
	}

	/**
	 * The return value is always an array.
	 *
	 * @return void
	 */
	public function testGetActivePluginsReturnsArray() {
		$result = SystemInfo::get_active_plugins();

		$this->assertIsArray( $result );
	}

	/**
	 * Every entry must contain name, slug, and version keys.
	 *
	 * @return void
	 */
	public function testGetActivePluginsEntriesHaveRequiredKeys() {
		$result = SystemInfo::get_active_plugins();
		$this->assertIsArray( $result );

		if ( [] === $result ) {
			$this->assertEmpty( $result );
			return;
		}

		foreach ( $result as $plugin ) {
			$this->assertArrayHasKey( 'name', $plugin );
			$this->assertArrayHasKey( 'slug', $plugin );
			$this->assertArrayHasKey( 'version', $plugin );
		}
	}

	/**
	 * Every entry must have string values for name, slug, and version.
	 *
	 * @return void
	 */
	public function testGetActivePluginsEntriesAreAllStrings() {
		$result = SystemInfo::get_active_plugins();
		$this->assertIsArray( $result );

		if ( [] === $result ) {
			$this->assertEmpty( $result );
			return;
		}

		foreach ( $result as $plugin ) {
			$this->assertIsString( $plugin['name'] );
			$this->assertIsString( $plugin['slug'] );
			$this->assertIsString( $plugin['version'] );
		}
	}

	/**
	 * Active plugin slugs match WordPress active-and-valid plugin list.
	 *
	 * @return void
	 */
	public function testGetActivePluginsMatchesActiveAndValidPluginList() {
		$expected_slugs = [];
		$active_paths   = wp_get_active_and_valid_plugins();

		if ( is_array( $active_paths ) ) {
			$expected_slugs = array_map( [ SystemInfo::class, 'get_plugin_slug_from_path' ], $active_paths );
		}

		$actual_slugs = array_map(
			static function ( $plugin ) {
				return is_array( $plugin ) && isset( $plugin['slug'] ) ? $plugin['slug'] : '';
			},
			SystemInfo::get_active_plugins()
		);

		sort( $expected_slugs );
		sort( $actual_slugs );

		$this->assertSame( $expected_slugs, $actual_slugs );
	}

	/**
	 * A non-WP_Theme value returns an empty array.
	 *
	 * @return void
	 */
	public function testGetThemeDataCollectionReturnsEmptyArrayForNonThemeObject() {
		// @phpstan-ignore-next-line
		$this->assertSame( [], SystemInfo::get_theme_data_collection( null ) );
		// @phpstan-ignore-next-line
		$this->assertSame( [], SystemInfo::get_theme_data_collection( 'twentytwentyfour' ) );
		// @phpstan-ignore-next-line
		$this->assertSame( [], SystemInfo::get_theme_data_collection( [] ) );
	}

	/**
	 * A valid WP_Theme object produces the four expected keys.
	 *
	 * @return void
	 */
	public function testGetThemeDataCollectionReturnsStructuredArrayForValidTheme() {
		$theme  = wp_get_theme();
		$result = SystemInfo::get_theme_data_collection( $theme );

		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'slug', $result );
		$this->assertArrayHasKey( 'version', $result );
		$this->assertArrayHasKey( 'tags', $result );
	}

	/**
	 * The slug value matches the theme's stylesheet directory name.
	 *
	 * @return void
	 */
	public function testGetThemeDataCollectionSlugMatchesStylesheet() {
		$theme  = wp_get_theme();
		$result = SystemInfo::get_theme_data_collection( $theme );

		$this->assertSame( $theme->get_stylesheet(), $result['slug'] );
	}

	/**
	 * The name value matches the theme's Name header.
	 *
	 * @return void
	 */
	public function testGetThemeDataCollectionNameMatchesThemeName() {
		$theme  = wp_get_theme();
		$result = SystemInfo::get_theme_data_collection( $theme );

		$this->assertSame( $theme->get( 'Name' ), $result['name'] );
	}

	/**
	 * That is_theme_accessibility_ready() matches expected tag evaluation on the active theme.
	 *
	 * @return void
	 */
	public function testIsThemeAccessibilityReadyMatchesComputedExpectationForActiveTheme() {
		$theme    = wp_get_theme();
		$expected = $this->compute_expected_accessibility_ready( $theme );
		$actual   = SystemInfo::is_theme_accessibility_ready( $theme );
		$this->assertSame( $expected, $actual );
	}
	/**
	 * A theme tagged accessibility-ready returns true.
	 *
	 * @return void
	 */
	public function testIsThemeAccessibilityReadyReturnsTrueForInstalledAccessibilityReadyTheme() {
		$theme = $this->find_installed_theme(
			function ( $candidate ) {
				return in_array( 'accessibility-ready', $this->get_theme_tags( $candidate ), true );
			}
		);
		if ( ! $theme ) {
			$this->markTestSkipped( 'No installed theme with accessibility-ready tag found.' );
		}
		$this->assertTrue( SystemInfo::is_theme_accessibility_ready( $theme ) );
	}
	/**
	 * A theme and parent without accessibility-ready tags return false.
	 *
	 * @return void
	 */
	public function testIsThemeAccessibilityReadyReturnsFalseForInstalledThemeWithoutAnyReadyTag() {
		$theme = $this->find_installed_theme(
			function ( $candidate ) {
				if ( in_array( 'accessibility-ready', $this->get_theme_tags( $candidate ), true ) ) {
					return false;
				}
				if ( ! $candidate->parent() ) {
					return true;
				}
				return ! in_array( 'accessibility-ready', $this->get_theme_tags( $candidate->parent() ), true );
			}
		);
		if ( ! $theme ) {
			$this->markTestSkipped( 'No installed theme found where both child and parent lack accessibility-ready tag.' );
		}
		$this->assertFalse( SystemInfo::is_theme_accessibility_ready( $theme ) );
	}
	/**
	 * A child theme without the tag returns true when parent has accessibility-ready tag.
	 *
	 * @return void
	 */
	public function testIsThemeAccessibilityReadyReturnsTrueWhenInstalledParentThemeHasTag() {
		$theme = $this->find_installed_theme(
			function ( $candidate ) {
				if ( ! $candidate->parent() ) {
					return false;
				}
				$child_has_tag  = in_array( 'accessibility-ready', $this->get_theme_tags( $candidate ), true );
				$parent_has_tag = in_array( 'accessibility-ready', $this->get_theme_tags( $candidate->parent() ), true );
				return ! $child_has_tag && $parent_has_tag;
			}
		);
		if ( ! $theme ) {
			$this->markTestSkipped( 'No installed child theme found where only parent is accessibility-ready.' );
		}
		$this->assertTrue( SystemInfo::is_theme_accessibility_ready( $theme ) );
	}

	/**
	 * The active theme array contains all required top-level keys.
	 *
	 * @return void
	 */
	public function testGetActiveThemeReturnsExpectedKeys() {
		$result = SystemInfo::get_active_theme();

		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'slug', $result );
		$this->assertArrayHasKey( 'version', $result );
		$this->assertArrayHasKey( 'tags', $result );
		$this->assertArrayHasKey( 'accessibility_ready', $result );
		$this->assertArrayHasKey( 'parent_theme', $result );
	}

	/**
	 * The accessibility_ready key is a boolean.
	 *
	 * @return void
	 */
	public function testGetActiveThemeAccessibilityReadyIsBool() {
		$result = SystemInfo::get_active_theme();

		$this->assertIsBool( $result['accessibility_ready'] );
	}

	/**
	 * The parent_theme key is an array (empty for non-child themes).
	 *
	 * @return void
	 */
	public function testGetActiveThemeParentThemeIsArray() {
		$result = SystemInfo::get_active_theme();

		$this->assertIsArray( $result['parent_theme'] );
	}

	/**
	 * Returns a non-empty string.
	 *
	 * @return void
	 */
	public function testGetEnvironmentTypeReturnsNonEmptyString() {
		$result = SystemInfo::get_environment_type();

		$this->assertIsString( $result );
		$this->assertNotEmpty( $result );
	}

	/**
	 * Return value is one of WordPress's recognised environment types.
	 *
	 * @return void
	 */
	public function testGetEnvironmentTypeReturnsRecognisedValue() {
		$allowed = [ 'local', 'development', 'staging', 'production' ];

		$this->assertContains( SystemInfo::get_environment_type(), $allowed );
	}

	/**
	 * Returns a non-empty string.
	 *
	 * @return void
	 */
	public function testGetWordpressVersionReturnsNonEmptyString() {
		$result = SystemInfo::get_wordpress_version();

		$this->assertIsString( $result );
		$this->assertNotEmpty( $result );
	}

	/**
	 * Matches the value returned by get_bloginfo( 'version' ).
	 *
	 * @return void
	 */
	public function testGetWordpressVersionMatchesBloginfo() {
		$this->assertSame( get_bloginfo( 'version' ), SystemInfo::get_wordpress_version() );
	}

	/**
	 * Returns a non-empty string.
	 *
	 * @return void
	 */
	public function testGetPhpVersionReturnsNonEmptyString() {
		$result = SystemInfo::get_php_version();

		$this->assertIsString( $result );
		$this->assertNotEmpty( $result );
	}

	/**
	 * Matches phpversion().
	 *
	 * @return void
	 */
	public function testGetPhpVersionMatchesPhpversion() {
		$this->assertSame( phpversion(), SystemInfo::get_php_version() );
	}

	/**
	 * Returns an array with all five expected keys.
	 *
	 * @return void
	 */
	public function testGetLicenseRequestContextReturnsExpectedKeys() {
		$result = SystemInfo::get_license_request_context();

		$this->assertArrayHasKey( 'environment', $result );
		$this->assertArrayHasKey( 'wp_version', $result );
		$this->assertArrayHasKey( 'php_version', $result );
		$this->assertArrayHasKey( 'active_plugins', $result );
		$this->assertArrayHasKey( 'active_theme', $result );
	}

	/**
	 * An active_plugins is a valid JSON string decoding to an array.
	 *
	 * @return void
	 */
	public function testGetLicenseRequestContextActivePluginsIsJsonString() {
		$result  = SystemInfo::get_license_request_context();
		$decoded = json_decode( $result['active_plugins'], true );

		$this->assertIsString( $result['active_plugins'] );
		$this->assertNotNull( $decoded );
		$this->assertIsArray( $decoded );
	}

	/**
	 * An active_theme is a valid JSON string decoding to an array.
	 *
	 * @return void
	 */
	public function testGetLicenseRequestContextActiveThemeIsJsonString() {
		$result  = SystemInfo::get_license_request_context();
		$decoded = json_decode( $result['active_theme'], true );

		$this->assertIsString( $result['active_theme'] );
		$this->assertNotNull( $decoded );
		$this->assertIsArray( $decoded );
	}

	/**
	 * An environment key matches get_environment_type() called directly.
	 *
	 * @return void
	 */
	public function testGetLicenseRequestContextEnvironmentMatchesDirectCall() {
		$result = SystemInfo::get_license_request_context();

		$this->assertSame( SystemInfo::get_environment_type(), $result['environment'] );
	}

	/**
	 * A wp_version key matches get_wordpress_version() called directly.
	 *
	 * @return void
	 */
	public function testGetLicenseRequestContextWpVersionMatchesDirectCall() {
		$result = SystemInfo::get_license_request_context();

		$this->assertSame( SystemInfo::get_wordpress_version(), $result['wp_version'] );
	}

	/**
	 * A php_version key matches get_php_version() called directly.
	 *
	 * @return void
	 */
	public function testGetLicenseRequestContextPhpVersionMatchesDirectCall() {
		$result = SystemInfo::get_license_request_context();

		$this->assertSame( SystemInfo::get_php_version(), $result['php_version'] );
	}

	/**
	 * The full context array contains exactly five keys and no extras.
	 *
	 * @return void
	 */
	public function testGetLicenseRequestContextHasExactlyFiveKeys() {
		$result = SystemInfo::get_license_request_context();

		$this->assertCount( 5, $result );
	}
}
