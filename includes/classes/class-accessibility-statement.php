<?php
/**
 * Accessibility Checker plugin file.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

/**
 * A class that handles accessibility statement.
 */
class Accessibility_Statement {

	/**
	 * Constructor.
	 */
	public function __construct() {
	}

	/**
	 * Initialize WordPress hooks.
	 */
	public function init_hooks() {
		add_action( 'wp_footer', [ $this, 'output_accessibility_statement' ] );
	}

	/**
	 * Get accessibility statement
	 *
	 * @return string
	 */
	public function get_accessibility_statement() {
		$statement              = '';
		$add_footer_statement   = get_option( 'edac_add_footer_accessibility_statement' );
		$include_statement_link = get_option( 'edac_include_accessibility_statement_link' );
		$policy_page            = get_option( 'edac_accessibility_policy_page' );
		$policy_page            = is_numeric( $policy_page ) 
			? get_page_link( $policy_page )
			: $policy_page;

		if ( $add_footer_statement ) {
			$statement .= sprintf(
				// translators: %1$s is the site name, %2$s is a link with the plugin name.
				esc_html__( '%1$s uses %2$s to monitor our website\'s accessibility.', 'accessibility-checker' ),
				get_bloginfo( 'name' ),
				sprintf(
					'<a href="https://equalizedigital.com/accessibility-checker" target="_blank" aria-label="%1$s">%2$s</a>',
					esc_attr__( 'Accessibility Checker (opens in a new window)', 'accessibility-checker' ),
					esc_html__( 'Accessibility Checker', 'accessibility-checker' )
				)
			);
		}

		if ( $include_statement_link && $policy_page ) {
			$statement .= ( ! empty( $statement ) ? ' ' : '' ) . sprintf(
				// translators: %1$s is a link to the accessibility policy page, with text "Accessibility Policy".
				esc_html__( 'Read our %s', 'accessibility-checker' ),
				'<a href="' . esc_url( $policy_page ) . '">' . esc_html__( 'Accessibility Policy', 'accessibility-checker' ) . '</a>.'
			);
		}

		return $statement;
	}

	/**
	 * Output accessibility statement
	 *
	 * @return void
	 */
	public function output_accessibility_statement() {
		$statement = $this->get_accessibility_statement();
		if ( ! empty( $statement ) ) {
			echo '<p class="edac-accessibility-statement"><small>' . wp_kses_post( $statement ) . '</small></p>';
		}
	}
}
