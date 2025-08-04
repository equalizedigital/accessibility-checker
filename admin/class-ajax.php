<?php
/**
 * Class file for ajax requests orchestration
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

use EDAC\Admin\OptIn\Email_Opt_In;

/**
 * Class that orchestrates ajax requests by delegating to specialized classes.
 */
class Ajax {

	/**
	 * Summary Ajax handler instance.
	 *
	 * @var Summary_Ajax
	 */
	private $summary_ajax;

	/**
	 * Details Ajax handler instance.
	 *
	 * @var Details_Ajax
	 */
	private $details_ajax;

	/**
	 * Readability Ajax handler instance.
	 *
	 * @var Readability_Ajax
	 */
	private $readability_ajax;

	/**
	 * UI Ajax handler instance.
	 *
	 * @var UI_Ajax
	 */
	private $ui_ajax;

	/**
	 * Constructor function for the class.
	 */
	public function __construct() {
		$this->summary_ajax     = new Summary_Ajax();
		$this->details_ajax     = new Details_Ajax();
		$this->readability_ajax = new Readability_Ajax();
		$this->ui_ajax          = new UI_Ajax();
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init_hooks() {
		// Initialize all specialized AJAX handlers.
		$this->summary_ajax->init_hooks();
		$this->details_ajax->init_hooks();
		$this->readability_ajax->init_hooks();
		$this->ui_ajax->init_hooks();

		// Keep the existing email opt-in registration.
		( new Email_Opt_In() )->register_ajax_handlers();
	}
}
