<?php

function edac_activation(){
	
	// set options
	add_option( 'edac_activation_date', date('Y-m-d H:i:s') );
	add_option( 'edac_post_types', ['post','page']);
	add_option( 'edac_simplified_summary_position', 'after');
	

	// Redirect: Don't do redirects when multiple plugins are bulk activated
	if (
		( isset( $_REQUEST['action'] ) && 'activate-selected' === $_REQUEST['action'] ) &&
		( isset( $_POST['checked'] ) && count( $_POST['checked'] ) > 1 ) ) {
		return;
	}

	edac_add_accessibility_statement_page();
	
}

/**
 * Add Accessibility Statement Page
 *
 * @return void
 */
function edac_add_accessibility_statement_page(){

	if ( ! current_user_can( 'activate_plugins' ) ) return;
	
	global $wpdb;

	if ( null === $wpdb->get_row( "SELECT post_name FROM {$wpdb->prefix}posts WHERE post_name = 'accessibility-statement'", 'ARRAY_A' ) ) {

		$current_user = wp_get_current_user();
		$author_id = $current_user->ID;
		$slug = 'accessibility-statement';
		$title = __( 'Our Commitment to Web Accessibility','edacp' );
		$content = '[YOUR COMPANY NAME] is committed to providing a fully accessible website experience for all users of all abilities, including those who rely on assistive technologies like screen readers, screen enlargement software, and alternative keyboard input devices to navigate the web.<br /><br />
<h2><strong>Ongoing Efforts to Ensure Accessibility</strong></h2>
 
We follow the <a href="https://www.w3.org/TR/WCAG21/">Web Content Accessibility Guidelines (WCAG) version 2.1</a> as our guiding principle for determining accessibility. These are internationally agreed-upon standards that cover a wide range of recommendations and best practices for making content useable. As we add new pages and functionality to our website, all designs, code, and content entry practices are checked against these standards.<br /><br />
Website accessibility is an ongoing process. We continually test content and features for WCAG 2.1 Level AA compliance and remediate any issues to ensure we meet or exceed the standards. Testing of our website is performed by our team members using industry-standard tools such as the <a href="https://a11ychecker.com/">Accessibility Checker WordPress Plugin</a>, color contrast analyzers, keyboard-only navigation techniques, and Flesch-Kincaid readability tests.<br /><br />
<h2>Accessibility Features On Our Website</h2>
 
The following is a list of items we have included in our website to improve its accessibility:<br /><br />
<ul>
<li>[LIST ACCESSIBILITY FEATURES HERE]</li>
</ul>
 
<h2>Where We’re Improving</h2>
 
In our efforts to bring our website up to standard, we are targeting the following areas:<br /><br />
<ul>
<li>[LIST ITEMS YOU\'RE WORKING TO FIX HERE]</li>
<li>[IT MAY HELP TO REVIEW THE OPEN ISSUES TAB TO SEE THE MOST COMMON PROBLEMS]</li>
</ul>
This is part of our broader effort to make everyone’s experience at [COMPANY NAME] a welcoming and enjoyable one. Please note that while we make every effort to provide information accessible for all users, we cannot guarantee the accessibility of third party websites to which we may link.<br /><br />
<h2>Accessibility Support Contact</h2>
 
We welcome comments, questions, and feedback on our website. If you are using assistive technologies and are having difficulty using our website, please email [YOUR ACCESSIBILITY EMAIL ADDRESS]  or give us a call at [YOUR PHONE NUMBER]. We will do our best to assist you and resolve issues.';
	
		// create post object
		$page = array(
			'post_title'   => $title,
			'post_status'  => 'draft',
			'post_author'  => $author_id,
			'post_name'    => $slug,
			'post_content' => $content,
			'post_type'    => 'page'
		);
		
		// insert the post into the database
		wp_insert_post( $page );

	}

}