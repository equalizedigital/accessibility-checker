<?php
/**
 * Backwards-compatibility wrapper for the Dismiss_Reasons class.
 *
 * This class has been renamed to IgnoreUI. This file is kept so that
 * code referencing the old class name (e.g. in the pro plugin) continues
 * to work without changes.
 *
 * @deprecated Use EqualizeDigital\AccessibilityChecker\Admin\IgnoreUI instead.
 * @package    Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Backwards-compat alias — extends the renamed IgnoreUI class.
 *
 * @deprecated Use IgnoreUI directly.
 */
class Dismiss_Reasons extends IgnoreUI {}
