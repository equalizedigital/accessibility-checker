<?php
/**
 * Class file for accessibility statement.
 *
 * @package Accessibility_Checker
 * @since 1.9.0
 */

namespace EDAC\Admin;

/**
 * Class that adds the accessibility statement page.
 */
class Accessibility_Statement {

	/**
	 * Add Accessibility Statement Page
	 *
	 * @return void
	 */
	public static function add_page() {

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_page_by_path_get_page_by_path -- Needs to suport all hosting environments.
		if ( ! get_page_by_path( 'accessibility-statement' ) ) {

			$current_user = wp_get_current_user();
			$author_id    = $current_user->ID;
			$slug         = 'accessibility-statement';
			$title        = __( 'Our Commitment to Web Accessibility', 'accessibility-checker' );
			$content      = sprintf(
				'%1$s<br /><br />
				<h2><strong>%2$s</strong></h2>
				%3$s<br /><br />
				%4$s<br /><br />
				<h2>%5$s</h2>
				%6$s<br /><br />
				<ul>
				<li>%7$s</li>
				</ul>
				<h2>%8$s</h2>
				%9$s<br /><br />
				<ul>
				<li>%10$s</li>
				<li>%11$s</li>
				</ul>
				%12$s<br /><br />
				<h2>%13$s</h2>
				%14$s',
				sprintf( __( '[YOUR COMPANY NAME] is committed to providing a fully accessible website experience for all users of all abilities, including those who rely on assistive technologies like screen readers, screen enlargement software, and alternative keyboard input devices to navigate the web.', 'accessibility-checker' ) ),
				__( 'Ongoing Efforts to Ensure Accessibility', 'accessibility-checker' ),
				sprintf( __( 'We follow the <a href="https://www.w3.org/TR/WCAG21/">Web Content Accessibility Guidelines (WCAG) version 2.1</a> as our guiding principle for determining accessibility. These are internationally agreed-upon standards that cover a wide range of recommendations and best practices for making content useable. As we add new pages and functionality to our website, all designs, code, and content entry practices are checked against these standards.', 'accessibility-checker' ) ),
				sprintf( __( 'Website accessibility is an ongoing process. We continually test content and features for WCAG 2.1 Level AA compliance and remediate any issues to ensure we meet or exceed the standards. Testing of our website is performed by our team members using industry-standard tools such as the <a href="https://a11ychecker.com/">Accessibility Checker WordPress Plugin</a>, color contrast analyzers, keyboard-only navigation techniques, and Flesch-Kincaid readability tests.', 'accessibility-checker' ) ),
				__( 'Accessibility Features On Our Website', 'accessibility-checker' ),
				__( 'The following is a list of items we have included in our website to improve its accessibility:', 'accessibility-checker' ),
				__( '[LIST ACCESSIBILITY FEATURES HERE]', 'accessibility-checker' ),
				__( 'Where We\'re Improving', 'accessibility-checker' ),
				__( 'In our efforts to bring our website up to standard, we are targeting the following areas:', 'accessibility-checker' ),
				__( '[LIST ITEMS YOU\'RE WORKING TO FIX HERE]', 'accessibility-checker' ),
				__( '[IT MAY HELP TO REVIEW THE OPEN ISSUES TAB TO SEE THE MOST COMMON PROBLEMS]', 'accessibility-checker' ),
				sprintf( __( 'This is part of our broader effort to make everyone\'s experience at [COMPANY NAME] a welcoming and enjoyable one. Please note that while we make every effort to provide information accessible for all users, we cannot guarantee the accessibility of third party websites to which we may link.', 'accessibility-checker' ) ),
				__( 'Accessibility Support Contact', 'accessibility-checker' ),
				sprintf( __( 'We welcome comments, questions, and feedback on our website. If you are using assistive technologies and are having difficulty using our website, please email [YOUR ACCESSIBILITY EMAIL ADDRESS] or give us a call at [YOUR PHONE NUMBER]. We will do our best to assist you and resolve issues.', 'accessibility-checker' ) )
			);

			$page = [
				'post_title'   => $title,
				'post_status'  => 'draft',
				'post_author'  => $author_id,
				'post_name'    => $slug,
				'post_content' => $content,
				'post_type'    => 'page',
			];

			wp_insert_post( $page );
		}
	}
}
