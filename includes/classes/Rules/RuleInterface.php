<?php
/**
 * Rule interface for accessibility rules.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

declare(strict_types=1);

namespace EqualizeDigital\AccessibilityChecker\Rules;

/**
 * Interface for accessibility rules.
 */
interface RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array;
}
