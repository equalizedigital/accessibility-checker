<?php
/**
 * Aria-hidden rule.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Rules;

/**
 * An abstract class for rules.
 */
class Rule_Aria_Hidden extends Rule {

	/**
	 * The rule ID.
	 *
	 * @var string
	 */
	protected $slug = 'aria-hidden';

	/**
	 * The rule type.
	 *
	 * @var string
	 */
	protected $type = 'warning';

	/**
	 * Get the rule title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'ARIA Hidden', 'accessibility-checker' );
	}

	/**
	 * Get the info URL.
	 *
	 * @return string
	 */
	public function get_info_url() {
		return 'https://a11ychecker.com/help1979';
	}

	/**
	 * Get the rule summary.
	 *
	 * @return string
	 */
	public function get_summary() {
		return esc_html__( 'The ARIA Hidden warning appears when content on your post or page has been hidden using the aria-hidden="true" attribute. When this attribute is added to an HTML element, screen readers will not read it out to users. Sometimes it is correct for the element to be hidden from screen readers (such as with a decorative icon) but other times this is not correct. When you see this warning, you need to determine if the element is supposed to be hidden from people who are blind or visually impaired. If it is correctly hidden, "Ignore" the warning. If it is incorrectly hidden and should be visible, remove the aria-hidden="true" attribute to resolve the warning.', 'accessibility-checker' );
	}

	/**
	 * Validation method.
	 *
	 * Scans the content and populates the $errors in the object.
	 *
	 * @return void
	 */
	public function validate() {
		$dom      = $this->content['html'];
		$elements = $dom->find( '[aria-hidden="true"]' );

		if ( $elements ) {
			foreach ( $elements as $element ) {

				if ( stristr( $element->getAttribute( 'class' ), 'wp-block-spacer' ) ) {
					continue;
				}

				$this->errors[] = $element;
			}
		}
	}
}
