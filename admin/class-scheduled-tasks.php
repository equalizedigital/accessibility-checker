<?php
/**
 * Handle scheduled tasks for Accessibility Checker.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly.
}

/**
 * Class Scheduled_Tasks
 *
 * Provides a generic system for scheduling recurring cleanup or processing tasks.
 */
class Scheduled_Tasks {

		/**
		 * Cron event name used to trigger scheduled tasks.
		 *
		 * @var string
		 */
		const EVENT = 'edac_run_scheduled_tasks';

		/**
		 * Register hooks.
		 *
		 * @return void
		 */
	public function init_hooks(): void {
			self::schedule_event();
	}

		/**
		 * Schedule the recurring event if it is not already scheduled.
		 *
		 * @return void
		 */
	public static function schedule_event(): void {
		if ( ! wp_next_scheduled( self::EVENT ) ) {
				wp_schedule_event( time(), 'daily', self::EVENT );
		}
	}

		/**
		 * Unschedule the recurring event.
		 *
		 * @return void
		 */
	public static function unschedule_event(): void {
			$timestamp = wp_next_scheduled( self::EVENT );
		if ( $timestamp ) {
				wp_unschedule_event( $timestamp, self::EVENT );
		}
	}
}
