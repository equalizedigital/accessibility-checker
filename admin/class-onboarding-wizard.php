<?php
/**
 * Class for the onboarding wizard page.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

/**
 * Handles the onboarding wizard admin page.
 */
class Onboarding_Wizard {

        /**
         * Register hooks.
         *
         * @return void
         */
        public function init_hooks() {
                add_action( 'admin_menu', [ $this, 'add_menu' ] );
        }

        /**
         * Add submenu page for onboarding wizard.
         *
         * @return void
         */
        public function add_menu() {
                add_submenu_page(
                        'accessibility_checker',
                        __( 'Onboarding Wizard', 'accessibility-checker' ),
                        __( 'Onboarding Wizard', 'accessibility-checker' ),
                        'read',
                        'accessibility_checker_onboarding',
                        [ $this, 'render_page' ]
                );
        }

        /**
         * Render the onboarding wizard page.
         *
         * @return void
         */
        public function render_page() {
                include_once plugin_dir_path( __DIR__ ) . 'partials/onboarding-wizard.php';
        }
}
