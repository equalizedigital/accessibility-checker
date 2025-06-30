<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

// Check if running in GitHub Actions environment.
$env = getenv( 'GITHUB_ACTIONS' );

$composer_json_path = 'composer.json';
$composer_config    = json_decode( file_get_contents( $composer_json_path ), true );

// if ( 'true' === $env ) {
// 	// Running in GitHub Actions environment.
// 	// Remove local specific packages for GitHub Actions environment.
// 	unset( $composer_config['require-dev']['equalizedigital/accessibility-checker-wp-env'] );
// } else {
// 	// Not running in GitHub Actions, assuming local environment.
// 	// Add your local specific packages.
// 	$composer_config['require-dev']['equalizedigital/accessibility-checker-wp-env'] = '*';
// }

file_put_contents( $composer_json_path, json_encode( $composer_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
