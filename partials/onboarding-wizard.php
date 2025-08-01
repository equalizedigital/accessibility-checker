<?php
/**
 * Onboarding wizard page.
 *
 * @package Accessibility_Checker
 */

?>
<div class="wrap edac-onboarding">
        <h1><?php esc_html_e( 'Accessibility Checker Onboarding', 'accessibility-checker' ); ?></h1>
        <p><?php esc_html_e( 'Welcome! This short guide will help you configure the plugin.', 'accessibility-checker' ); ?></p>
        <ol class="edac-onboarding-steps">
                <li>
                        <?php
                        printf(
                                wp_kses(
                                        /* translators: %s: settings page URL */
                                        __( 'Visit the <a href="%s">Settings Page</a> to choose which post types should be scanned.', 'accessibility-checker' ),
                                        [ 'a' => [ 'href' => [] ] ]
                                ),
                                esc_url( admin_url( 'admin.php?page=accessibility_checker_settings' ) )
                        );
                        ?>
                </li>
                <li><?php esc_html_e( 'After saving your settings, start a scan from the Welcome page.', 'accessibility-checker' ); ?></li>
                <li>
                        <?php
                        printf(
                                wp_kses(
                                        /* translators: %s: documentation URL */
                                        __( 'Need help? Review our <a href="%s" target="_blank" rel="noopener noreferrer">documentation</a>.', 'accessibility-checker' ),
                                        [ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ]
                                ),
                                esc_url( 'https://equalizedigital.com/accessibility-checker/documentation/' )
                        );
                        ?>
                </li>
        </ol>
</div>
