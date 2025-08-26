<?php
/**
 * PHPStan bootstrap file to define constants for static analysis
 * 
 * @package EqualizeDigital\AccessibilityChecker
 */

// PHPStan isn't able to resolve the constants defined in the plugin that aren't at root level so
// we define them here to avoid notices about them in the results.
define( 'EDAC_VERSION', '1.29.0' );
define( 'EDAC_DB_VERSION', '1.0.4' );
define( 'EDAC_PLUGIN_URL', '' );
define( 'EDAC_PLUGIN_DIR', '' );
define( 'EDAC_PLUGIN_FILE', '' );
define( 'EDAC_SVG_IGNORE_ICON', '' );
define( 'EDAC_KEY_VALID', false );
define( 'EDAC_DEBUG', false );
define( 'EDAC_ANWW_ACTIVE', false );
define( 'EDAC_GAAD_NOTICE_START_DATE', '2025-05-13' );
define( 'EDAC_GAAD_NOTICE_END_DATE', '2025-05-21' );

// Pro plugin constants.
define( 'EDACAH_VERSION', '1.0.0' );
