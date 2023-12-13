<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

namespace EDAC;

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
		add_action( 'wp_footer', array( $this, 'output_accessibility_statement' ) );
	}

	/**
	 * Get accessibility statement.
	 *
	 * @return string
	 */
	public function get_accessibility_statement() {
		$statement              = '';
		$add_footer_statement   = get_option( 'edac_add_footer_accessibility_statement' );
		$include_statement_link = get_option( 'edac_include_accessibility_statement_link' );
		$policy_page            = get_option( 'edac_accessibility_policy_page' );
		$policy_page            = is_numeric( $policy_page ) ? get_page_link( $policy_page ) : $policy_page;

		if ( $add_footer_statement ) {
			$statement .= get_bloginfo( 'name' ) . ' ' . esc_html__( 'uses', 'accessibility-checker' ) . ' <a href="https://equalizedigital.com/accessibility-checker" target="_blank" aria-label="' . esc_attr__( 'Accessibility Checker', 'accessibility-checker' ) . ', opens a new window">' . esc_html__( 'Accessibility Checker', 'accessibility-checker' ) . '</a> ' . esc_html__( 'to monitor our website\'s accessibility. ', 'accessibility-checker' );
		}

		if ( $include_statement_link && $policy_page ) {
			$statement .= esc_html__( 'Read our ', 'accessibility-checker' ) . '<a href="' . $policy_page . '">' . esc_html__( 'Accessibility Policy', 'accessibility-checker' ) . '</a>.';
		}

		return $statement;
	}

	/**
	 * Output simplified summary.
	 *
	 * @return void
	 */
	public function output_accessibility_statement() {
		$statement = $this->get_accessibility_statement();
		if ( ! empty( $statement ) ) {
			echo '<p class="edac-accessibility-statement" style="text-align: center; max-width: 800px; margin: auto; padding: 15px;"><small>' . wp_kses_post( $statement ) . '</small></p>';
		}
	}
}

if ( ! is_admin() ) {
	$accessibility_statement = new \EDAC\Accessibility_Statement();
	$accessibility_statement->init_hooks();
}
