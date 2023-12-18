<?php
/**
 * An abstract class for rules.
 *
 * All individual rules classes should extend this one.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Rules;

/**
 * An abstract class for rules.
 */
abstract class Rule {

	/**
	 * The rule slug.
	 *
	 * @var string
	 */
	protected $slug;

	/**
	 * The rule type. Can be notice|warning|error.
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * The content to check.
	 *
	 * @var array
	 */
	protected $content;

	/**
	 * An array of errors.
	 *
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Set the content.
	 *
	 * @param string $content The rule ID.
	 */
	public function set_content( $content ) {
		$this->content = $content;
	}

	/**
	 * Get the rule slug.
	 *
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * Get the rule type.
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Get the rule title.
	 *
	 * @return string
	 */
	abstract public function get_title();

	/**
	 * Get the info URL.
	 *
	 * @return string
	 */
	abstract public function get_info_url();

	/**
	 * Get the rule summary.
	 *
	 * @return string
	 */
	abstract public function get_summary();

	/**
	 * Validation method.
	 *
	 * Scans the content and populates the $errors in the object.
	 *
	 * @return void
	 */
	abstract public function validate();

	/**
	 * Get the errors.
	 *
	 * @return array
	 */
	public function get_errors() {
		return $this->errors;
	}
}
