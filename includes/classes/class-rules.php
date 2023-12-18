<?php
/**
 * The EDAC/Rules class is a collection of rules.
 *
 * @package Accessibility_Checker
 */

namespace EDAC;

/**
 * The EDAC/Rules class is a collection of rules.
 */
class Rules {

	/**
	 * An array of Rule objects.
	 *
	 * @var object[]
	 */
	protected static $rules = array();

	/**
	 * Add a rule to the collection.
	 *
	 * @param string $id   The rule ID.
	 * @param object $rule The rule to add.
	 */
	public static function add_rule( $id, $rule ) {
		self::$rules[ $id ] = $rule;
	}

	/**
	 * Get all rules.
	 *
	 * @return object[] An array of Rule objects.
	 */
	public static function get_rules() {
		return self::$rules;
	}

	/**
	 * Get a rule by ID.
	 *
	 * @param string $id The rule ID.
	 * @return object The rule.
	 */
	public static function get_rule( $id ) {
		return self::$rules[ $id ];
	}
}
