<?php
/**
 * Accessibility Checker Plugin.
 *
 * @package Accessibility_Checker
 * @link    https://a11ychecker.com
 * @since   1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       Accessibility Checker
 * Plugin URI:        https://a11ychecker.com
 * Description:       Audit and check your website for accessibility before you hit publish. In-post accessibility scanner and guidance.
 * Version:           1.3.19
 * Author:            Equalize Digital
 * Author URI:        https://equalizedigital.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       edac
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Freemius
 */
if ( ! function_exists( 'edac_fs' ) ) {
	/**
	 * Create a helper function for easy SDK access.
	 *
	 * @return mixed
	 */
	function edac_fs() {
		global $edac_fs;

		if ( ! isset( $edac_fs ) ) {
			// Include Freemius SDK.
			require_once dirname( __FILE__ ) . '/vendor/freemius/wordpress-sdk/start.php';

			$edac_fs = fs_dynamic_init(
				array(
					'id'             => '7322',
					'slug'           => 'accessibility-checker',
					'type'           => 'plugin',
					'public_key'     => 'pk_5146b841819550deb8874ca70bc89',
					'is_premium'     => false,
					'has_addons'     => false,
					'has_paid_plans' => false,
					'menu'           => array(
						'slug'       => 'accessibility_checker',
						'first-path' => 'admin.php?page=accessibility_checker',
						'account'    => false,
						'contact'    => false,
						'support'    => false,
					),
				)
			);
		}

		return $edac_fs;
	}

	// Init Freemius.
	edac_fs();
	// Signal that SDK was initiated.
	do_action( 'edac_fs_loaded' );
}


/**
 * Setup constants.
 */

// Current plugin version.
if ( ! defined( 'EDAC_VERSION' ) ) {
	define( 'EDAC_VERSION', '1.3.19' );
}

// Current database version.
if ( ! defined( 'EDAC_DB_VERSION' ) ) {
	define( 'EDAC_DB_VERSION', '1.0.3' );
}

// Plugin Folder Path.
if ( ! defined( 'EDAC_PLUGIN_DIR' ) ) {
	define( 'EDAC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Plugin Folder URL.
if ( ! defined( 'EDAC_PLUGIN_URL' ) ) {
	define( 'EDAC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Plugin Root File.
if ( ! defined( 'EDAC_PLUGIN_FILE' ) ) {
	define( 'EDAC_PLUGIN_FILE', __FILE__ );
}

// Accessibility New Window Warning Plugin Active.
if ( ! defined( 'EDAC_ANWW_ACTIVE' ) ) {
	if ( is_plugin_active( 'accessibility-new-window-warnings/accessibility-new-window-warnings.php' ) ) {
		define( 'EDAC_ANWW_ACTIVE', true );
	} else {
		define( 'EDAC_ANWW_ACTIVE', false );
	}
}

/**
 * Key Valid.
 */
if ( 'valid' === get_option( 'edacp_license_status' ) ) {
	define( 'EDAC_KEY_VALID', true );
} else {
	define( 'EDAC_KEY_VALID', false );
}

// Enable EDAC_DEBUG mode.
if ( ! defined( 'EDAC_DEBUG' ) ) {
	define( 'EDAC_DEBUG', false );
}

// SVG Icons.
define(
	'EDAC_SVG_IGNORE_ICON',
	'<?xml version="1.0" standalone="no"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 20010904//EN"
 "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">
<svg version="1.0" xmlns="http://www.w3.org/2000/svg"
 width="568.000000pt" height="568.000000pt" viewBox="0 0 568.000000 568.000000"
 preserveAspectRatio="xMidYMid meet">

<g transform="translate(0.000000,568.000000) scale(0.100000,-0.100000)"
fill="#000000" stroke="none">
<path d="M2558 5585 c-289 -49 -525 -173 -735 -387 -166 -168 -277 -338 -363
-557 -89 -224 -118 -380 -130 -696 -17 -428 -40 -640 -106 -964 -86 -426 -235
-825 -399 -1072 -37 -55 -104 -136 -155 -187 -103 -102 -135 -160 -125 -222
12 -73 68 -126 200 -191 l79 -39 27 27 c15 16 691 842 1504 1837 l1477 1809
-38 54 c-292 424 -793 662 -1236 588z"/>
<path d="M4508 5323 c-36 -43 -930 -1138 -1988 -2433 -1057 -1295 -1931 -2364
-1942 -2376 -18 -21 -18 -21 112 -127 71 -59 134 -107 140 -107 5 0 883 1070
1952 2377 1068 1308 1970 2412 2005 2454 l63 76 -130 107 c-72 58 -134 106
-139 106 -5 0 -38 -35 -73 -77z"/>
<path d="M3013 2494 l-1102 -1349 912 -3 c585 -2 971 1 1077 8 590 39 965 171
995 350 10 62 -22 120 -125 222 -164 163 -300 406 -406 726 -132 397 -207 783
-237 1221 l-12 173 -1102 -1348z"/>
<path d="M2220 939 c0 -32 73 -142 135 -204 112 -112 241 -165 400 -165 159 0
288 53 400 165 63 62 135 171 135 204 0 8 -150 11 -535 11 -385 0 -535 -3
-535 -11z"/>
</g>
</svg>'
);

/**
 * Plugin Activation & Deactivation
 */
register_activation_hook( __FILE__, 'edac_activation' );
register_deactivation_hook( __FILE__, 'edac_deactivation' );

/**
 * Define file path and basename
 */
$edac_plugin_directory = __FILE__;
$edac_plugin_basename  = plugin_basename( __FILE__ );

/**
 * Add simple dom support (need to over ride max file size, if clashes with another install of simple dom there the max file size will be dependednt upon that installation)
 */
if ( ! defined( 'MAX_FILE_SIZE' ) ) {
	define( 'MAX_FILE_SIZE', 6000000 );
}
if ( ! class_exists( 'simple_html_dom' ) ) {
	include_once plugin_dir_path( __FILE__ ) . 'includes/simplehtmldom/simple_html_dom.php';
	include_once plugin_dir_path( __FILE__ ) . 'includes/classes/class_edac_dom.php';
}

/**
 * Include TextStatistics
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/TextStatistics/Maths.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/TextStatistics/Pluralise.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/TextStatistics/Resource.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/TextStatistics/Syllables.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/TextStatistics/Text.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/TextStatistics/TextStatistics.php';
use DaveChild\TextStatistics as TS;

/**
 * Import Resources
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/activation.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/deactivation.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/helper-functions.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/enqueue-scripts.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/meta-boxes.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/options-page.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/validate.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/insert.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/purge.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/system-info.php';

/**
 * Filters and Actions
 */
add_action( 'admin_enqueue_scripts', 'edac_admin_enqueue_scripts' );
add_action( 'admin_enqueue_scripts', 'edac_admin_enqueue_styles' );
add_action( 'admin_init', 'edac_update_database', 10 );
add_action( 'add_meta_boxes', 'edac_register_meta_boxes' );
add_action( 'admin_menu', 'edac_add_options_page' );
add_action( 'admin_init', 'edac_register_setting' );
add_action( 'admin_head', 'edac_post_on_load' );
add_filter( 'save_post', 'edac_save_post', 10, 3 );
add_action( 'wp_ajax_edac_summary_ajax', 'edac_summary_ajax' );
add_action( 'wp_ajax_edac_details_ajax', 'edac_details_ajax' );
add_action( 'wp_ajax_edac_readability_ajax', 'edac_readability_ajax' );
add_action( 'wp_ajax_edac_insert_ignore_data', 'edac_insert_ignore_data' );
add_action( 'wp_ajax_edac_update_simplified_summary', 'edac_update_simplified_summary' );
add_filter( 'the_content', 'edac_output_simplified_summary' );
add_filter( 'wp_footer', 'edac_output_accessibility_statement' );
add_action( 'wp_trash_post', 'edac_delete_post' );
add_action( 'pre_get_posts', 'edac_show_draft_posts' );
add_action( 'admin_init', 'edac_process_actions' );
add_action( 'edac_download_sysinfo', 'edac_tools_sysinfo_download' );
if ( edac_check_plugin_active( 'oxygen/functions.php' ) ) {
	add_action( 'added_post_meta', 'edac_oxygen_builder_save_post', 10, 4 );
	add_action( 'updated_post_meta', 'edac_oxygen_builder_save_post', 10, 4 );
}
add_action( 'admin_init', 'edac_anww_update_post_meta' );
add_action( 'admin_notices', 'edac_review_notice' );
add_action( 'admin_notices', 'edac_password_protected_notice' );
add_action( 'wp_ajax_edac_review_notice_ajax', 'edac_review_notice_ajax' );
add_action( 'in_admin_header', 'edac_remove_admin_notices', 1000 );
add_action( 'admin_notices', 'edac_black_friday_notice' );

/**
 * Create/Update database
 *
 * @return void
 */
function edac_update_database() {

	global $wpdb;
	$table_name = $wpdb->prefix . 'accessibility_checker';

	$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );
	if ( get_option( 'edac_db_version' ) !== EDAC_DB_VERSION || $wpdb->get_var( $query ) !== $table_name ) {

		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			postid bigint(20) NOT NULL,
			siteid text NOT NULL,
			type text NOT NULL,
			rule text NOT NULL,
			ruletype text NOT NULL,
			object mediumtext NOT NULL,
			recordcheck mediumint(9) NOT NULL,
			created timestamp NOT NULL default CURRENT_TIMESTAMP,
			user bigint(20) NOT NULL,
			ignre mediumint(9) NOT NULL,
			ignre_global mediumint(9) NOT NULL,
			ignre_user bigint(20) NULL,
			ignre_date timestamp NULL,
			ignre_comment mediumtext NULL,
			UNIQUE KEY id (id),
			KEY postid_index (postid)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

	}

	// Update database version option.
	$option_name = 'edac_db_version';
	$new_value = EDAC_DB_VERSION;
	if ( get_option( $option_name ) !== false ) {
		update_option( $option_name, $new_value );
	} else {
		add_option( $option_name, $new_value );
	}

}

/**
 * Register Rules
 *
 * @return array
 */
function edac_register_rules() {
	$rules = array();

	array_push(
		$rules,
		array(
			'title'     => 'Image Missing Alternative Text',
			'info_url'  => 'https://a11ychecker.com/help1927',
			'slug'      => 'img_alt_missing',
			'rule_type' => 'error',
			'summary'   => esc_html( 'An Image Missing Alternative Text error means that your image does not have an alt attribute (alt="") contained in the image tag (<a>) at all. To fix an Image Missing Alternative Text error, you will need to add an alt tag to the image with appropriate text describing the purpose of the image in the page. If the image is decorative, the alt attribute can be empty, but the HTML alt="" tag still needs to be present.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Image Empty Alternative Text',
			'info_url'  => 'https://a11ychecker.com/help4991',
			'slug'      => 'img_alt_empty',
			'rule_type' => 'warning',
			'summary'   => esc_html( 'An Image Empty Alternative Text warning appears if you have an image with an alt attribute (alt="") that is empty. Alternative text tells people who cannot see what the images is and adds additional context to the post or page. It is only correct for alternative text to be empty if the image is purely decorative, like a border or decorative icon. To fix an Image Empty Alternative Text warning, you need to determine if the image is decorative or if adds something meaningful to the page. If it is not decorative, you need to add appropriate alternative text to describe the image’s purpose. If the image is decorative, then you would leave the alternative text blank and “Ignore” the warning.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Low-quality Alternative Text',
			'info_url'  => 'https://a11ychecker.com/help1977',
			'slug'      => 'img_alt_invalid',
			'rule_type' => 'warning',
			'summary'   => esc_html( 'A Low-quality Alternative Text warning appears when the alternative text on an image contains keywords that are unnecessary in alternative text (such as "image" or "graphic"), a file extension (such as .JPG), that may be describing a decorative image (such as "spacer" or "arrow"). To fix this warning, you will need to rewrite the alternative text for any images that flagged the Low-Quality Alternative Text warning, ensuring the alternative text is accurate, unique, contextually appropriate, and does not contain redundant or unnecessary descriptors. If the image is purely decorative, it is correct to leave the alternative text blank.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Linked Image Missing Alternative Text',
			'info_url'  => 'https://a11ychecker.com/help1930',
			'slug'      => 'img_linked_alt_missing',
			'rule_type' => 'error',
			'summary'   => esc_html( 'A Low-quality Alternative Text warning appears when the alternative text on an image contains keywords that are unnecessary in alternative text (such as "image" or "graphic"), a file extension (such as .JPG), that may be describing a decorative image (such as "spacer" or "arrow"). To fix this warning, you will need to rewrite the alternative text for any images that flagged the Low-Quality Alternative Text warning, ensuring the alternative text is accurate, unique, contextually appropriate, and does not contain redundant or unnecessary descriptors. If the image is purely decorative, it is correct to leave the alternative text blank.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Linked Image Empty Alternative Text',
			'info_url'  => 'https://a11ychecker.com/help1930',
			'slug'      => 'img_linked_alt_empty',
			'rule_type' => 'error',
			'summary'   => esc_html( 'A Linked Image Empty Alternative Text error appears when an image that is linked to a URL has an alt attribute (alt="") with nothing in it. Linked images must have accurate alternative text that describes where the link goes so that screen reader users know where the link is pointing. To resolve this error you need to add meaningful alt text to the image. Your alt text should describe the link purpose not what the image looks like.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Duplicate Alternative Text',
			'info_url'  => 'https://a11ychecker.com/help1976',
			'slug'      => 'img_alt_redundant',
			'rule_type' => 'warning',
			'summary'   => esc_html( '' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Incorrect Heading Order',
			'info_url'  => 'https://a11ychecker.com/help1940',
			'slug'      => 'incorrect_heading_order',
			'rule_type' => 'error',
			'summary'   => esc_html( 'An Incorrect Heading Order error means your heading structure has skipped over a level. For example, if your page structure has a level 3 heading (<h3>) under a level 1 heading (<h1>), an Incorrect Heading Order error will be flagged because there is no <h2> tag between the H1 and H2. To fix incorrect heading order errors, you will need to either change the incorrect heading level to the correct heading level, or add content with the correct heading level in between the two already existing levels.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'iFrame Missing Title',
			'info_url'  => 'https://a11ychecker.com/help1953',
			'slug'      => 'iframe_missing_title',
			'rule_type' => 'error',
			'summary'   => esc_html( 'An Incorrect Heading Order error means your heading structure has skipped over a level. For example, if your page structure has a level 3 heading (<h3>) under a level 1 heading (<h1>), an Incorrect Heading Order error will be flagged because there is no <h2> tag between the H1 and H2. To fix incorrect heading order errors, you will need to either change the incorrect heading level to the correct heading level, or add content with the correct heading level in between the two already existing levels.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Missing Subheadings',
			'info_url'  => 'https://a11ychecker.com/help1967',
			'slug'      => 'missing_headings',
			'rule_type' => 'warning',
			'summary'   => esc_html( 'A warning about missing headings means that your post or page does not contain any heading elements (<h1>–<h6>) within the content of the post or page body section, which can make it especially difficult for screen reader users to navigate through the content on the page. To fix a page with no headings, you will need to add heading elements. At a minimum, every page should have one <h1> tag, which is typically the page title. Add additional subheadings as appropriate for your content. If you have determined that headings are definitely not needed on the page, then you can “Ignore” the warning.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Text Justified',
			'info_url'  => 'https://a11ychecker.com/help1980',
			'slug'      => 'text_justified',
			'rule_type' => 'warning',
			'summary'   => esc_html( 'A warning about missing headings means that your post or page does not contain any heading elements (<h1>–<h6>) within the content of the post or page body section, which can make it especially difficult for screen reader users to navigate through the content on the page. To fix a page with no headings, you will need to add heading elements. At a minimum, every page should have one <h1> tag, which is typically the page title. Add additional subheadings as appropriate for your content. If you have determined that headings are definitely not needed on the page, then you can “Ignore” the warning.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Link Opens New Window or Tab',
			'info_url'  => 'https://a11ychecker.com/help1982',
			'slug'      => 'link_blank',
			'rule_type' => 'warning',
			'summary'   => esc_html( 'A Link Opens New Window or Tab warning appears when there is a link on your website that has been set to open in a new tab or window when clicked. It is considered best practice to not open new tabs or windows with links. If links do open new tabs or windows, there must be a visual and auditory warning announcing that the link will open a new window or tab so that users will expect that behavior and know how to go back after clicking the link. To fix this warning, either change the link not to open in a new tab or ensure "opens new window" is included in the link text then "Ignore" the warning. To automatically add notices to all links on your site and dismiss all these warnings, install our free' ) . ' <a href="https://wordpress.org/plugins/accessibility-new-window-warnings/" target="_blank">Accessibility New Window Warnings plugin.</a> ',
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Image Map Missing Alternative Text',
			'info_url'  => 'https://a11ychecker.com/help1938',
			'slug'      => 'image_map_missing_alt_text',
			'rule_type' => 'error',
			'summary'   => esc_html( 'The Image Map Missing Alternative Text error means that one of the <area> elements within your image map does not have alternative text added in an alt="" attribute. To fix this error, you will need to add missing alt text to any area tags that do not have alt text. The alt text needs to describe the function of the link contained in the area, not necessarily describe what the area looks like.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Tab Order Modified',
			'info_url'  => 'https://a11ychecker.com/help1974',
			'slug'      => 'tab_order_modified',
			'rule_type' => 'warning',
			'summary'   => esc_html( 'A Tab Order Modified Warning appears when the logical tab order on the page has been changed by adding an attribute for tabindex that is greater than 0 to an HTML element (for example, tabindex="1"). This can cause navigation issues for keyboard-only users. To resolve a Tab Order Modified warning you need to view the front end of your website on the page or post where the tab order has been modified and test to see if the modification is correct or not. If the tab order modification does not cause problems, then you can "Ignore" the warning. If the modified tab order causes information to be presented out of order, then you need to remove the tabindex attribute from the flagged element.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Empty Heading Tag',
			'info_url'  => 'https://a11ychecker.com/help1957',
			'slug'      => 'empty_heading_tag',
			'rule_type' => 'error',
			'summary'   => esc_html( 'An Empty Heading Tag error means that there is a heading tag present on your post or page that does not contain content. In code, this error would look like this: <h1></h1>. To fix an empty heading, you will need to add content to the heading tag that has flagged the Empty Heading Tag error or remove the empty tag if it is not needed on your page.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Empty Link',
			'info_url'  => 'https://a11ychecker.com/help4108',
			'slug'      => 'empty_link',
			'rule_type' => 'error',
			'summary'   => esc_html( 'An Empty Link error means that one of the links present on the web page is empty or contains no text describing where the link will go if clicked. This commonly occurs with links that contain webfonts, icon fonts, and SVGs, or when a link has accidentally been created in the content editor. To fix an empty link error, you will need to find the link that is being flagged and add descriptive text to it. You will need to either: add text content within an empty <a> element or, if your link contains an SVG or Webfont icon, hide that element with aria-hidden="true" and add an aria-label attribute to the <a> tag or screen reader text. The text or label you add should be descriptive of wherever the link points and not ambiguous.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Empty Button',
			'info_url'  => 'https://a11ychecker.com/help1960',
			'slug'      => 'empty_button',
			'rule_type' => 'error',
			'summary'   => esc_html( 'An Empty Button error means that one of the buttons present on the web page is empty or contains no text describing the function of the button. Or, if it’s an image button, the image contained in the button is missing alternative text. To fix an empty button error, you will need to find the button that is being flagged and add descriptive text to it. You will need to either: add text content within an empty <button> element, add a value attribute to an <input> that is missing one, or add alternative text to a button image. The text should be descriptive of whatever your button is being used for or the action that the button triggers.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Image Long Alternative Text',
			'info_url'  => 'https://a11ychecker.com/help1966',
			'slug'      => 'img_alt_long',
			'rule_type' => 'warning',
			'summary'   => esc_html( 'An Image Long Alternative Text warning appears if there are more than 100 characters in your alternative text. Alternative text is meant to be descriptive of the image but in a succinct manner, without being too wordy. To fix this warning, you need to shorten your alt text for any images that have been flagged to 100 characters or less. If you have determined that yoru alternative text is good as-is, then "Ignore" the warning.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'ARIA Hidden',
			'info_url'  => 'https://a11ychecker.com/help1979',
			'slug'      => 'aria_hidden',
			'rule_type' => 'warning',
			'summary'   => esc_html( 'The ARIA Hidden warning appears when content on your post or page has been hidden using the aria-hidden="true" attribute. When this attribute is added to an HTML element, screen readers will not read it out to users. Sometimes it is correct for the element to be hidden from screen readers (such as with a decorative icon) but other times this is not correct. When you see this warning, you need to determine if the element is supposed to be hidden from people who are blind or visually impaired. If it is correctly hidden, "Ignore" the warning. If it is incorrectly hidden and should be visible, remove the aria-hidden="true" attribute to resolve the warning.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Empty Table Header',
			'info_url'  => 'https://a11ychecker.com/help1963',
			'slug'      => 'empty_table_header',
			'rule_type' => 'error',
			'summary'   => esc_html( 'An Empty Table Header error means that one of the table headers on your post or page does not contain any text. This means that the <th> element is present but looks like this <th></th> with nothing between the opening and closing tags. To fix an empty table header, you need to find the correct HTML element (<th>) and add text to it that describes the row or column that it applies to.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Link to MS Office File',
			'info_url'  => 'https://a11ychecker.com/help1970',
			'slug'      => 'link_ms_office_file',
			'rule_type' => 'warning',
			'summary'   => esc_html( 'A Link to MS Office File warning means that one or more of the links on your page or post directs to a file with one of the following file extensions: .doc, .docx, .xls, .xlsx, .ppt, .pptx, .pps or .ppsx. This warning appears when an MS Office file is present as a reminder to manually test your Word documents, PowerPoint presentations, and Excel spreadsheets for accessibility and to confirm that they conform to all relevant WCAG guidelines. To resolve a Link to MS Office File warning, you need to: (1) ensure a direct link to view or download the document is present if you\'re using a plugin to embed it on the page; (2) ensure the link to the document warns users it is a link to a document by displaying the specific file extension in the link anchor; and (3) test and remediate your MS Office file for accessibility errors. After determining your file is fully accessible, you can safely “Ignore” the warning.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Link to PDF',
			'info_url'  => 'https://a11ychecker.com/help1972',
			'slug'      => 'link_pdf',
			'rule_type' => 'warning',
			'summary'   => esc_html( 'A Link to PDF warning means that one or more of the links on your page or post directs to a PDF file. This warning is a reminder to manually test the linked PDF for accessibility and to confirm that it conforms to all relevant WCAG guidelines. To resolve a Link to PDF warning, you need to: (1) ensure a direct link to view or download the document is present if you\'re using a plugin to embed it on the page; (2) ensure the link to the document warns users it is a link to a document by displaying the specific file extension in the link anchor; and (3) test and remediate your document for accessibility errors. After determining your file is fully accessible, you can safely “Ignore” the warning.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Link to Non-HTML File',
			'info_url'  => 'https://a11ychecker.com/help1973',
			'slug'      => 'link_non_html_file',
			'rule_type' => 'warning',
			'summary'   => esc_html( 'A  Link to Non-HTML Document warning means that one or more of the links on your page or post directs to a file with one of the following file extensions: .rtf, .wpd, .ods, .odt, .odp, .sxw, .sxc, .sxd, .sxi, .pages, or .key. This warning is a reminder to manually test the linked document for accessibility and to confirm that it conforms to all relevant WCAG guidelines. To resolve a Link to Non-HTML Document warning, you need to: (1) ensure a direct link to view or download the document is present if you\'re using a plugin to embed it on the page; (2) ensure the link to the document warns users it is a link to a document by displaying the specific file extension in the link anchor; and (3) test and remediate your document for accessibility errors. After determining your file is fully accessible, you can safely “Ignore” the warning.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Long Description Invalid',
			'info_url'  => 'https://a11ychecker.com/help1948',
			'slug'      => 'long_description_invalid',
			'rule_type' => 'error',
			'summary'   => esc_html( 'The Long Description Invalid error means that a long description attribute (longdesc="") on an image does not have an appropriate URL, filename, or file extension. It may also mean that the long description is not a URL, or it has been left blank. The longdesc attribute is not fully supported opens a new window by HTML5, browsers, and all screen readers. Due to this lack of support, the best fix for this error is to remove longdesc from your image tag completely.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Empty Form Label',
			'info_url'  => 'https://a11ychecker.com/help4109',
			'slug'      => 'empty_form_label',
			'rule_type' => 'error',
			'summary'   => esc_html( 'An Empty Form Label error is triggered when a <label> tag is present in your form and associated with an input (form field), but does not contain any text. To fix empty form label errors, you’ll need to determine how the field and form were created and then add text to the label for the field that is currently blank.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Missing Form Label',
			'info_url'  => 'https://a11ychecker.com/help1949',
			'slug'      => 'missing_form_label',
			'rule_type' => 'error',
			'summary'   => esc_html( 'A Missing Form Label error is triggered when an <input> (form field) is present in your form and but is not associated with a <label> element. This could mean the label is present but is missing a for="" attribute to connect it to the applicable field or there could be no label present at all and only an <input> tag. To fix missing form label errors, you’ll need to determine how the field and form were created and then add field labels or a correct for="" attribute to exisiting labels that are not connected to a field.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Ambiguous Anchor Text',
			'info_url'  => 'https://a11ychecker.com/help1944',
			'slug'      => 'link_ambiguous_text',
			'rule_type' => 'error',
			'summary'   => esc_html( 'Ambiguous Anchor Text errors appear when there is linked text that has no meaning outside of its surronding content. Common examples of this include linking phrases like "click here" or "learn more." To resolve this error, change the link text to be less generic so that it has meaning if heard on its own.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Underlined Text',
			'info_url'  => 'https://a11ychecker.com/help1978',
			'slug'      => 'underlined_text',
			'rule_type' => 'warning',
			'summary'   => esc_html( 'An Underlined Text warning appears if any text on your page is wrapped in an HTML underline tag (<u>). In an online environment, underlined text is generally used to indicate linked text and it is not considerd a best practice to underline text for emphasis only. To fix underlined text, you will need to remove the <u> element from the text or CSS styles that are making it underlined. Try using other stylization, such as italics, colored text, or bolding to emphasize or differentiate between words or phrases.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Broken Skip or Anchor Link',
			'info_url'  => 'https://a11ychecker.com/help1962',
			'slug'      => 'broken_skip_anchor_link',
			'rule_type' => 'error',
			'summary'   => esc_html( 'An anchor link, sometimes called a jump link, is a link that, rather than opening a new page or URL when clicked, jumps or scrolls you to a different section on the same page. These links go to an element that starts with a hashtag rather than a full URL. For example, you might scroll someone to the about section of your home page by linking to #about. Broken Skip or Anchor Link errors appear when there is a link that targets another section on the same page but there is not an element present on the page that has the referenced id. This error will also appear if you\'re linking to just a #. To resolve this error, manually test the link to confirm it works and then either fix it or "Ignore" the error as applicable.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Missing Table Header',
			'info_url'  => 'https://a11ychecker.com/help1963',
			'slug'      => 'missing_table_header',
			'rule_type' => 'error',
			'summary'   => esc_html( 'A Missing Table Header error means that one of your tables contains data (information contained in a <td> tag) that does not have a corresponding header (<th>) tag. When looking at the HTML for your form, there will be more <td> elements in a row than <th> elements in the table. To fix a missing table header, you need to find the section of code that has less <th> elements in it than should be present for the number of rows or columns of data, and add one or more additional <th> elements containing text that describes the data in that row or column.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Duplicate Form Label',
			'info_url'  => 'https://a11ychecker.com/help1954',
			'slug'      => 'duplicate_form_label',
			'rule_type' => 'error',
			'summary'   => esc_html( 'Duplicate Form Label errors appear when there is more than one label associated with a single field on a form. If there are too many form labels present, a screen reader may not be able to successfully read the form fields to help a visually impaired user navigate through and complete the form. To fix duplicate form label errors, you’ll need to determine how the field and form were created and then ensure that each field has only one label associated with it.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Text Too Small',
			'info_url'  => 'https://a11ychecker.com/help1975',
			'slug'      => 'text_small',
			'rule_type' => 'warning',
			'summary'   => esc_html( 'A Text Too Small warning occurs when there is text on your website that is less than 10px in size. The warning is an indication that you may want to rethink the font size and make it larger so that it can be more easily read without a user needing zoom in on their browser. To fix text that is too small, you will need to ensure that all text elements on your website are at least 10 points.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Possible Heading',
			'info_url'  => 'https://a11ychecker.com/help1969',
			'slug'      => 'possible_heading',
			'rule_type' => 'warning',
			'summary'   => esc_html( 'A Possible Heading warning occurs when there is text on a page that appears to be a heading, but has not been coded with proper heading tags. This warning is appears if there are short phrases or strings of text less than 50 characters in length that are formatted in a way which suggests they might be being used as headers (they\'re 20 pixels or bigger, or are 16 pixels or bigger and bold and/or italicized). To fix a Possible Heading warning, you will need to determine if the flagged text is indeed intended to be a heading. If so, you need to change it from a paragraph to a heading at the proper level. If it is not supposed to be a heading then you can safely “Ignore” the warning.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Blinking or Scrolling Content',
			'info_url'  => 'https://a11ychecker.com/help1965',
			'slug'      => 'text_blinking_scrolling',
			'rule_type' => 'error',
			'summary'   => esc_html( 'Blinking or Scrolling Content errors appear when elements on your website have a blinking or scrolling function applied to them either via CSS or in the HTML. Specifically, the following will create this error: the <blink> or <marquee> HTML tags or CSS text-decoration: blink. To resolve this error remove the HTML tags or CSS that is causing content to blink.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Insufficient Color Contrast',
			'info_url'  => 'https://a11ychecker.com/help1983',
			'slug'      => 'color_contrast_failure',
			'rule_type' => 'error',
			'summary'   => esc_html( 'Insufficient Color Contrast errors means that we have identified that one or more of the color combinations on your post or page do not meet the minimum color contrast ratio of 4.5:1. Depending upon how your site is built there may be "false positives" for this error as some colors are contained in different HTML layers on the page. To fix an Insufficient Color Contrast error, you will need to ensure that flagged elements meet the minimum required ratio of 4.5:1. To do so, you will need to find the hexadecimal codes of your foreground and background color, and test them in a color contrast checker. If these color codes have a ratio of 4.5:1 or greater you can “Ignore” this error. If the color codes do not have a ratio of at least 4.5:1, you will need to make adjustments to your colors.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Missing Transcript',
			'info_url'  => 'https://a11ychecker.com/help1947',
			'slug'      => 'missing_transcript',
			'rule_type' => 'error',
			'summary'   => esc_html( 'A missing transcript error means that there is an audio or video clip on your website that does not have a transcript or there is a transcript but it is not labelled as a transcript or is positioned more than 25 characters away from the embedded or linked to media. To fix a missing transcript error, you will need to create a transcript for any of the video or audio clips that have been flagged as missing a transcript. Once you have created the transcript, you can either add the transcript content directly within your post or page or link to the transcript if you’re including it as a downloadable doc or PDF file. You need to explicitly include the word “transcript” within a heading before the transcript on the page or in the link to your file, and it needs to be within 25 characters of the audio or video embed or link.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Broken ARIA Reference',
			'info_url'  => 'https://a11ychecker.com/help1956',
			'slug'      => 'broken_aria_reference',
			'rule_type' => 'error',
			'summary'   => esc_html( 'Broken ARIA Reference errors appear if an aria-labeledby or aria-describedby element is present on the page or post but its reference target does not exist. This means that the element being referred to by the specific ARIA attribute you’re using either does not have a proper label or descriptor, or it is not present on the page. To fix a broken ARIA reference, you will need to find the ARIA elements that are being flagged, and ensure that their reference targets are present and properly labeled.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Missing Language Declaration',
			'info_url'  => 'https://a11ychecker.com/help4429',
			'slug'      => 'missing_lang_attr',
			'rule_type' => 'error',
			'summary'   => esc_html( 'A language declaration is an HTML attribute that denotes the default language of the content on a page or post. Language declarations should be coded into your website theme and appear automatically in the head of the website. A Missing Lanaguage Declaration error appears if the <HTML> tag on the page does not contain a lang or XML:lang attribute, or one of these attributes is present but is empty. To fix a Missing Language Declaration error, you will need to edit your theme files to add the missing language attribute to the HTML tag at the very top of your website header. If you’re using a theme that receives updates, then you’ll need to make the change in a child theme to ensure the fix does not get overwritten when you next update your theme.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Image Animated GIF',
			'info_url'  => 'https://a11ychecker.com/help4428',
			'slug'      => 'img_animated_gif',
			'rule_type' => 'warning',
			'summary'   => esc_html( 'Image Animated GIF warnings appear when there is an animated GIF on your post or page. This warning is a reminder to manually review any animated GIFs on your website for their accessibility and/or to reconsider using animated GIFs, replacing them instead with static images or videos. To resolve this warning, you need to review any GIFs that are present to ensure that they meet all applicable guidelines for accessibility and then either “Ignore” the warning or remove the GIF from your page or post if it is not accessible.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'A Video is Present',
			'info_url'  => 'https://a11ychecker.com/help4414',
			'slug'      => 'video_present',
			'rule_type' => 'warning',
			'summary'   => esc_html( 'Because videos frequently contain accessibility problems, many of which can only be identified by a person, The A Video is Present warning appears anytime a video is detected on a post or page as a reminder that you need to manually test your video for accessibilty. To resolve this warning, you need to visit the front end of your website and confirm that the video in the warning is accessible. Once you have fully tested the video for accessibility, you need to fix any errors that may be present and then can “Ignore” the warning to mark it as complete.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'A Slider is Present',
			'info_url'  => 'https://a11ychecker.com/help3264',
			'slug'      => 'slider_present',
			'rule_type' => 'warning',
			'summary'   => esc_html( 'Because sliders frequently contain accessibility problems, many of which can only be identified by a person, the A Slider is Present warning appears anytime a slider is detected on a post or page as a reminder that you need to manually test your slider for accessibilty. To resolve this warning, you need to visit the front end of your website and confirm all sliders on the page are accessible. Once you have fully tested your sliders for accessibility, you need to fix any errors that may be present and then can “Ignore” the warning to mark it as complete.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Missing Title',
			'info_url'  => 'https://a11ychecker.com/help4431',
			'slug'      => 'missing_title',
			'rule_type' => 'error',
			'summary'   => esc_html( 'An iFrame Missing title error means that one or more of the iFrames on your post or page does not have an accessible title describing the contents of the iFrame. An iFrame title is an attribute that can be added to the <iframe> tag to describe the contents of the frame to people using assistive technology. To fix a missing iFrame title, you will need to add a title or an aria-label attribute to the <iframe> tag. The attribute should accurately describe the contents of the iFrame.' ),
		)
	);

	array_push(
		$rules,
		array(
			'title'     => 'Improper Use of Link',
			'info_url'  => 'https://a11ychecker.com/help6473',
			'slug'      => 'link_improper',
			'rule_type' => 'error',
			'summary'   => esc_html( 'An Improper Use of Link error appears if you have links that are missing an href attribute or are only linked to a #, and do not have role="button" on them. Links should be used to direct people to other parts of your site. Any other functionality that triggers an action should be a button. To resolve this error you need to recode the link to use a <button> tag (preferable) or add role="button" to the existing <a> tag. If the element is a toggle button (such as an accordion), additional ARIA attributes are required.' ),
		)
	);

	// filter rules.
	if ( has_filter( 'edac_filter_register_rules' ) ) {
		$rules = apply_filters( 'edac_filter_register_rules', $rules );
	}

	return $rules;
}

/**
 * Include Rules
 *
 * @var object $rules
 */
$rules = edac_register_rules();
if ( $rules ) {
	foreach ( $rules as $rule ) {
		if ( $rule['slug'] ) {
			require_once plugin_dir_path( __FILE__ ) . 'includes/rules/' . $rule['slug'] . '.php';
		}
	}
}

/**
 * Summary Ajax
 *
 * @return void
 *
 *  - '-1' means that nonce could not be varified
 *  - '-2' means that the post ID was not specified
 *  - '-3' means that there isn't any summary data to return
 */
function edac_summary_ajax() {

	// nonce security.
	if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'ajax-nonce' ) ) {

		$error = new WP_Error( '-1', 'Permission Denied' );
		wp_send_json_error( $error );

	}

	if ( ! isset( $_REQUEST['post_id'] ) ) {

		$error = new WP_Error( '-2', 'The post ID was not set' );
		wp_send_json_error( $error );

	}

	// password check.
	if ( get_option( 'edac_password_protected' ) === true ) {
		$html['password_protected'] = edac_password_protected_notice_text();
	} else {

		$post_id = intval( $_REQUEST['post_id'] );
		$summary = edac_summary( $post_id );
		$html = '';
		if ( $summary['readability'] <= 9 ) {
			$simplified_summary_text = 'Your content has a reading level at or below 9th grade and does not require a simplified summary.';
		} else {
			$simplified_summary_text = $summary['simplified_summary'] ? 'A Simplified summary has been included for this content.' : 'A Simplified summary has not been included for this content.';
		}

		$html .= '<div class="edac-summary-total">';

			$html .= '<div class="edac-summary-total-progress-circle ' . ( ( $summary['passed_tests'] > 50 ) ? ' over50' : '' ) . '">
				<div class="edac-summary-total-progress-circle-label">
					<div class="edac-panel-number">' . $summary['passed_tests'] . '%</div>
					<div class="edac-panel-number-label">Passed Tests<sup>*</sup></div>
				</div>
				<div class="left-half-clipper">
					<div class="first50-bar"></div>
					<div class="value-bar" style="transform: rotate(' . $summary['passed_tests'] * 3.6 . 'deg);"></div>
				</div>
			</div>';

			$html .= '<div class="edac-summary-total-mobile">
				<div class="edac-panel-number">' . $summary['passed_tests'] . '%</div>
				<div class="edac-panel-number-label">Passed Tests<sup>*</sup></div>
				<div class="edac-summary-total-mobile-bar"><span style="width:' . ( $summary['passed_tests'] ) . '%;"></span></div>
			</div>';

		$html .= '</div>';

		$html .= '
		<div class="edac-summary-stats">
			<div class="edac-summary-stat edac-summary-errors' . ( ( $summary['errors'] > 0 ) ? ' has-errors' : '' ) . '">
				<div class="edac-panel-number">
					' . $summary['errors'] . '
				</div>
				<div class="edac-panel-number-label">Error' . ( ( 1 !== $summary['errors'] ) ? 's' : '' ) . '</div>
			</div>
			<div class="edac-summary-stat edac-summary-contrast' . ( ( $summary['contrast_errors'] > 0 ) ? ' has-errors' : '' ) . '">
				<div class="edac-panel-number">
					' . $summary['contrast_errors'] . '
				</div>
				<div class="edac-panel-number-label">Contrast Error' . ( ( 1 !== $summary['contrast_errors'] ) ? 's' : '' ) . '</div>
			</div>
			<div class="edac-summary-stat edac-summary-warnings' . ( ( $summary['warnings'] > 0 ) ? ' has-warning' : '' ) . '">
				<div class="edac-panel-number">
					' . $summary['warnings'] . '
				</div>
				<div class="edac-panel-number-label">Warning' . ( ( 1 !== $summary['warnings'] ) ? 's' : '' ) . '</div>
			</div>
			<div class="edac-summary-stat edac-summary-ignored">
				<div class="edac-panel-number">
					' . $summary['ignored'] . '
				</div>
				<div class="edac-panel-number-label">Ignored Item' . ( ( 1 !== $summary['ignored'] ) ? 's' : '' ) . '</div>
			</div>
		</div>
		<div class="edac-summary-readability">
			<div class="edac-summary-readability-level">
				<div><img src="' . plugin_dir_url( __FILE__ ) . 'assets/images/readability icon navy.png" alt="" width="54"></div>
				<div class="edac-panel-number' . ( ( $summary['readability'] <= 9 ) ? ' passed-text-color' : ' failed-text-color' ) . '">
					' . $summary['readability'] . '
				</div>
				<div class="edac-panel-number-label' . ( ( $summary['readability'] <= 9 ) ? ' passed-text-color' : ' failed-text-color' ) . '">Reading <br />Level</div>
			</div>
			<div class="edac-summary-readability-summary">
				<div class="edac-summary-readability-summary-icon' . ( ( $summary['simplified_summary'] || $summary['readability'] <= 9 ) ? ' active' : '' ) . '"></div>
				<div class="edac-summary-readability-summary-text' . ( ( $summary['simplified_summary'] || $summary['readability'] <= 9 ) ? ' active' : '' ) . '">' . $simplified_summary_text . '</div>
			</div>
		</div>
		<div class="edac-summary-disclaimer"><small>* Accessibility Checker uses automated scanning to help you to identify if common accessibility errors are present on your website. Automated tools are great for catching some accessibility problems and are part of achieving and maintaining an accessible website, however not all accessibility problems can be identified by a scanning tool. Learn more about <a href="https://a11ychecker.com/help4280" target="_blank">manual accessibility testing</a> and <a href="https://a11ychecker.com/help4279" target="_blank">why 100% passed tests does not necessarily mean your website is accessible</a>.</small></div>
		';
	}

	if ( ! $html ) {

		$error = new WP_Error( '-3', 'No summary to return' );
		wp_send_json_error( $error );

	}

	wp_send_json_success( json_encode( $html ) );

}

/**
 * Summary Data
 *
 * @param int $post_id ID of the post.
 * @return array
 */
function edac_summary( $post_id ) {
	global $wpdb;
	$summary = array();

	// Passed Tests.
	$rules = edac_register_rules();

	// if ANWW is active remove link_blank for details meta box.
	if ( EDAC_ANWW_ACTIVE ) {
		$rules = edac_remove_element_with_value( $rules, 'slug', 'link_blank' );
	}

	$rules_passed = array();

	if ( $rules ) {
		foreach ( $rules as $rule ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'accessibility_checker';
			$postid     = $post_id;
			$siteid     = get_current_blog_id();
			$query = 'SELECT count(*) FROM ' . $table_name . ' where rule = %s and siteid = %d and postid = %d and ignre = %d';
			$rule_count = $wpdb->get_var( $wpdb->prepare( $query, $rule['slug'], $siteid, $postid, 0 ) );
			if ( ! $rule_count ) {
				$rules_passed[] = $rule['slug'];
			}
		}
	}

	$summary['passed_tests'] = round( count( $rules_passed ) / count( $rules ) * 100 );

	// count errors.
	$query = 'SELECT count(*) FROM ' . $wpdb->prefix . 'accessibility_checker where siteid = %d and postid = %d and ruletype = %s and ignre = %d';
	$summary['errors'] = intval( $wpdb->get_var( $wpdb->prepare( $query, get_current_blog_id(), $post_id, 'error', 0 ) ) );

	// count warnings.
	$warnings_parameters = array( get_current_blog_id(), $post_id, 'warning', 0 );
	$warnings_where = 'WHERE siteid = siteid = %d and postid = %d and ruletype = %s and ignre = %d';
	if ( EDAC_ANWW_ACTIVE ) {
		array_push( $warnings_parameters, 'link_blank' );
		$warnings_where .= ' and rule != %s';
	}
	$query = 'SELECT count(*) FROM ' . $wpdb->prefix . 'accessibility_checker ' . $warnings_where;
	$summary['warnings'] = intval( $wpdb->get_var( $wpdb->prepare( $query, $warnings_parameters ) ) );

	// count ignored issues.
	$ignored_parameters = array( get_current_blog_id(), $post_id, 1 );
	$ignored_where = 'WHERE siteid = %d and postid = %d and ignre = %d';
	if ( EDAC_ANWW_ACTIVE ) {
		array_push( $ignored_parameters, 'link_blank' );
		$ignored_where .= ' and rule != %s';
	}
	$query = 'SELECT count(*) FROM ' . $wpdb->prefix . 'accessibility_checker ' . $ignored_where;
	$summary['ignored'] = intval( $wpdb->get_var( $wpdb->prepare( $query, $ignored_parameters ) ) );

	// contrast errors.
	$query = 'SELECT count(*) FROM ' . $wpdb->prefix . 'accessibility_checker where siteid = %d and postid = %d and rule = %s and ignre = %d';
	$summary['contrast_errors'] = intval( $wpdb->get_var( $wpdb->prepare( $query, get_current_blog_id(), $post_id, 'color_contrast_failure', 0 ) ) );

	// remove color contrast from errors count.
	$summary['errors'] = $summary['errors'] - $summary['contrast_errors'];

	// reading grade level.
	$content_post = get_post( $post_id );

	$content                = $content_post->post_content;
	$content                = wp_filter_nohtml_kses( $content );
	$content                = str_replace( ']]>', ']]&gt;', $content );
	$text_statistics        = new TS\TextStatistics();
	$content_grade          = floor( $text_statistics->fleschKincaidGradeLevel( $content ) );
	$summary['readability'] = ( 0 === $content_grade ) ? 'N/A' : edac_ordinal( $content_grade );

	// simplified summary.
	$summary['simplified_summary'] = get_post_meta( $post_id, '_edac_simplified_summary', $single = true ) ? true : false;

	// save summary data as post meta.
	if ( ! add_post_meta( $post_id, '_edac_summary', $summary, true ) ) {
		update_post_meta( $post_id, '_edac_summary', $summary );
	}

	if ( ! add_post_meta( $post_id, '_edac_summary_passed_tests', $summary['passed_tests'], true ) ) {
		update_post_meta( $post_id, '_edac_summary_passed_tests', $summary['passed_tests'] );
	}

	if ( ! add_post_meta( $post_id, '_edac_summary_errors', $summary['errors'], true ) ) {
		update_post_meta( $post_id, '_edac_summary_errors', $summary['errors'] );
	}

	if ( ! add_post_meta( $post_id, '_edac_summary_warnings', $summary['warnings'], true ) ) {
		update_post_meta( $post_id, '_edac_summary_warnings', $summary['warnings'] );
	}

	if ( ! add_post_meta( $post_id, '_edac_summary_ignored', $summary['ignored'], true ) ) {
		update_post_meta( $post_id, '_edac_summary_ignored', $summary['ignored'] );
	}

	if ( ! add_post_meta( $post_id, '_edac_summary_contrast_errors', $summary['contrast_errors'], true ) ) {
		update_post_meta( $post_id, '_edac_summary_contrast_errors', $summary['contrast_errors'] );
	}

	return $summary;
}

/**
 * Update post meta when Accessibility New Window Warning pluing is installed or uninstalled
 *
 * @return void
 */
function edac_anww_update_post_meta() {

	$option_name = 'edac_anww_update_post_meta';

	if ( get_option( $option_name ) === false && EDAC_ANWW_ACTIVE === true ) {

		add_option( $option_name, true );

		edac_update_post_meta( 'link_blank' );

	} elseif ( get_option( $option_name ) === true && EDAC_ANWW_ACTIVE === false ) {

		delete_option( $option_name );

		edac_update_post_meta( 'link_blank' );

	}

}

/**
 * Update post meta by rule
 *
 * @param string $rule rule name.
 * @return void
 */
function edac_update_post_meta( $rule ) {
	global $wpdb;
	$site_id = get_current_blog_id();

	$posts = $wpdb->get_results( $wpdb->prepare( 'SELECT postid FROM ' . $wpdb->prefix . 'accessibility_checker WHERE rule = %s and siteid = %d', $rule, $site_id ), ARRAY_A );

	if ( $posts ) {
		foreach ( $posts as $post ) {
			edac_summary( $post['postid'] );
		}
	}
}

/**
 * Details Ajax
 *
 * @return void
 *
 *  - '-1' means that nonce could not be varified
 *  - '-2' means that the post ID was not specified
 *  - '-3' means that there isn't any details to return
 */
function edac_details_ajax() {

	// nonce security.
	if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'ajax-nonce' ) ) {

		$error = new WP_Error( '-1', 'Permission Denied' );
		wp_send_json_error( $error );

	}

	if ( ! isset( $_REQUEST['post_id'] ) ) {

		$error = new WP_Error( '-2', 'The post ID was not set' );
		wp_send_json_error( $error );

	}

	$html = '';
	global $wpdb;
	$table_name = $wpdb->prefix . 'accessibility_checker';
	$postid     = intval( $_REQUEST['post_id'] );
	$siteid     = get_current_blog_id();

	$rules = edac_register_rules();
	if ( $rules ) {

		// if ANWW is active remove link_blank for details meta box.
		if ( EDAC_ANWW_ACTIVE ) {
			$rules = edac_remove_element_with_value( $rules, 'slug', 'link_blank' );
		}

		// separate rule types.
		$passed_rules  = array();
		$error_rules   = edac_remove_element_with_value( $rules, 'rule_type', 'warning' );
		$warning_rules = edac_remove_element_with_value( $rules, 'rule_type', 'error' );

		// add count, unset passed error rules and add passed rules to array.
		if ( $error_rules ) {
			foreach ( $error_rules as $key => $error_rule ) {
				$count = count( $wpdb->get_results( $wpdb->prepare( 'SELECT id, postid, object, ruletype, ignre, ignre_user, ignre_date, ignre_comment FROM ' . $table_name . ' where postid = %d and rule = %s and siteid = %d and ignre = %d', $postid, $error_rule['slug'], $siteid, 0 ), ARRAY_A ) );
				if ( $count ) {
					$error_rules[ $key ]['count'] = $count;
				} else {
					$error_rule['count'] = 0;
					$passed_rules[] = $error_rule;
					unset( $error_rules[ $key ] );
				}
			}
		}

		// add count, unset passed warning rules and add passed rules to array.
		if ( $warning_rules ) {
			foreach ( $warning_rules as $key => $error_rule ) {
				$count = count( $wpdb->get_results( $wpdb->prepare( 'SELECT id, postid, object, ruletype, ignre, ignre_user, ignre_date, ignre_comment FROM ' . $table_name . ' where postid = %d and rule = %s and siteid = %d and ignre = %d', $postid, $error_rule['slug'], $siteid, 0 ), ARRAY_A ) );
				if ( $count ) {
					$warning_rules[ $key ]['count'] = $count;
				} else {
					$error_rule['count'] = 0;
					$passed_rules[] = $error_rule;
					unset( $warning_rules[ $key ] );
				}
			}
		}
	}

	// sort error rules by count.
	usort(
		$error_rules,
		function( $a, $b ) {

			return $b['count'] <=> $a['count'];

		}
	);

	// sort warning rules by count.
	usort(
		$warning_rules,
		function( $a, $b ) {

			return $b['count'] <=> $a['count'];

		}
	);

	// sort passed rules array by title.
	usort(
		$passed_rules,
		function( $a, $b ) {

			return $a['title'] <=> $b['title'];

		}
	);

	// merge rule arrays together.
	$rules = array_merge( $error_rules, $warning_rules, $passed_rules );

	if ( $rules ) {
		global $wp_version;
		$days_active = edac_days_active();
		$ignore_permission = true;
		if ( has_filter( 'edac_ignore_permission' ) ) {
			$ignore_permission = apply_filters( 'edac_ignore_permission', $ignore_permission );
		}
		foreach ( $rules as $rule ) {
			$results        = $wpdb->get_results( $wpdb->prepare( 'SELECT id, postid, object, ruletype, ignre, ignre_user, ignre_date, ignre_comment, ignre_global FROM ' . $table_name . ' where postid = %d and rule = %s and siteid = %d', $postid, $rule['slug'], $siteid ), ARRAY_A );
			$count_classes  = ( 'error' === $rule['rule_type'] ) ? ' edac-details-rule-count-error' : ' edac-details-rule-count-warning';
			$count_classes .= ( 0 !== $rule['count'] ) ? ' active' : '';

			$count_ignored = 0;
			$ignores       = array_column( $results, 'ignre' );
			if ( $ignores ) {
				foreach ( $ignores as $ignore ) {
					if ( true === (bool) $ignore ) {
						$count_ignored++;
					}
				}
			}

			$expand_rule = count( $wpdb->get_results( $wpdb->prepare( 'SELECT id FROM ' . $table_name . ' where postid = %d and rule = %s and siteid = %d', $postid, $rule['slug'], $siteid ), ARRAY_A ) );

			$tool_tip_link = $rule['info_url'] . '?utm_source=accessibility-checker&utm_medium=software&utm_term=' . esc_html( $rule['slug'] ) . '&utm_content=content-analysis&utm_campaign=wordpress-general&php_version=' . PHP_VERSION . '&platform=wordpress&platform_version=' . $wp_version . '&software=free&software_version=' . EDAC_VERSION . '&days_active=' . $days_active . '';

			$html .= '<div class="edac-details-rule">';

				$html .= '<div class="edac-details-rule-title">';

					$html .= '<h3>';
						$html .= '<span class="edac-details-rule-count' . $count_classes . '">' . $rule['count'] . '</span> ';
						$html .= esc_html( $rule['title'] );
			if ( $count_ignored > 0 ) {
				$html .= '<span class="edac-details-rule-count-ignore">' . $count_ignored . ' Ignored Items</span>';
			}
					$html .= '</h3>';
					$html .= '<a href="' . $tool_tip_link . '" class="edac-details-rule-information" target="_blank" aria-label="Read documentation for ' . esc_html( $rule['title'] ) . '"><span class="dashicons dashicons-info"></span></a>';
					$html .= ( $expand_rule ) ? '<button class="edac-details-rule-title-arrow" aria-expanded="false" aria-controls="edac-details-rule-records-' . $rule['slug'] . '" aria-label="Expand issues for ' . esc_html( $rule['title'] ) . '"><i class="dashicons dashicons-arrow-down-alt2"></i></button>' : '';

				$html .= '</div>';

			if ( $results ) {

				$html .= '<div id="edac-details-rule-records-' . $rule['slug'] . '" class="edac-details-rule-records">';

					$html .=
					'<div class="edac-details-rule-records-labels">
						<div class="edac-details-rule-records-labels-label" aria-hidden="true">
							Affected Code
						</div>
						<div class="edac-details-rule-records-labels-label" aria-hidden="true">
							Image
						</div>
						<div class="edac-details-rule-records-labels-label" aria-hidden="true">
							Actions
						</div>
					</div>';

				foreach ( $results as $row ) {

					$id                      = intval( $row['id'] );
					$ignore                  = intval( $row['ignre'] );
					$ignore_class            = $ignore ? ' active' : '';
					$ignore_label            = $ignore ? 'Ignored' : 'Ignore';
					$ignore_user             = intval( $row['ignre_user'] );
					$ignore_user_info        = get_userdata( $ignore_user );
					$ignore_username         = is_object( $ignore_user_info ) ? '<strong>Username:</strong> ' . $ignore_user_info->user_login : '';
					$ignore_date             = ( $row['ignre_date'] && '0000-00-00 00:00:00' !== $row['ignre_date'] ) ? '<strong>Date:</strong> ' . gmdate( 'F j, Y g:i a', strtotime( esc_html( $row['ignre_date'] ) ) ) : '';
					$ignore_comment          = esc_html( $row['ignre_comment'] );
					$ignore_action           = $ignore ? 'disable' : 'enable';
					$ignore_type             = $rule['rule_type'];
					$ignore_submit_label     = $ignore ? 'Stop Ignoring' : 'Ignore This ' . $ignore_type;
					$ignore_comment_disabled = $ignore ? 'disabled' : '';
					$ignore_global           = intval( $row['ignre_global'] );

					// check for images and svgs in object code.
					$object_img      = null;
					$object_svg      = null;
					$object_img_html = str_get_html( htmlspecialchars_decode( $row['object'], ENT_QUOTES ) );
					if ( $object_img_html ) {
						$object_img_elements = $object_img_html->find( 'img' );
						$object_svg_elements = $object_img_html->find( 'svg' );
						if ( $object_img_elements ) {
							foreach ( $object_img_elements as $element ) {
								$object_img = $element->getAttribute( 'src' );
								if ( $object_img ) {
									break;
								}
							}
						} elseif ( $object_svg_elements ) {
							foreach ( $object_svg_elements as $element ) {
								$object_svg = $element;
								break;
							}
						}
					}

					$html .= '<h4 class="screen-reader-text">Issue ID ' . $id . '</h4>';

					$html .= '<div id="edac-details-rule-records-record-' . $id . '" class="edac-details-rule-records-record">';

						$html .= '<div class="edac-details-rule-records-record-cell edac-details-rule-records-record-object">';

							$html .= '<code>' . esc_html( $row['object'] ) . '</code>';

						$html .= '</div>';

						$html .= '<div class="edac-details-rule-records-record-cell edac-details-rule-records-record-image">';

					if ( $object_img ) {
						$html .= '<img src="' . $object_img . '" alt="image for issue ' . $id . '" />';
					} elseif ( $object_svg ) {
						$html .= $object_svg;
					}

						$html .= '</div>';

						$html .= '<div class="edac-details-rule-records-record-cell edac-details-rule-records-record-actions">';

							$html .= '<button class="edac-details-rule-records-record-actions-ignore' . $ignore_class . '" aria-expanded="false" aria-controls="edac-details-rule-records-record-ignore-' . $row['id'] . '">' . EDAC_SVG_IGNORE_ICON . '<span class="edac-details-rule-records-record-actions-ignore-label">' . $ignore_label . '</span></button>';

						$html .= '</div>';

						$html .= '<div id="edac-details-rule-records-record-ignore-' . $row['id'] . '" class="edac-details-rule-records-record-ignore">';

							$html .= '<div class="edac-details-rule-records-record-ignore-info">';
								$html .= '<span class="edac-details-rule-records-record-ignore-info-user">' . $ignore_username . '</span>';

								$html .= ' <span class="edac-details-rule-records-record-ignore-info-date">' . $ignore_date . '</span>';
							$html .= '</div>';

							$html .= ( true === $ignore_permission || ! empty( $ignore_comment ) ) ? '<label for="edac-details-rule-records-record-ignore-comment-' . $id . '">Comment</label><br>' : '';
							$html .= ( true === $ignore_permission || ! empty( $ignore_comment ) ) ? '<textarea rows="4" class="edac-details-rule-records-record-ignore-comment" id="edac-details-rule-records-record-ignore-comment-' . $id . '" ' . $ignore_comment_disabled . '>' . $ignore_comment . '</textarea>' : '';

					if ( $ignore_global ) {
						$html .= ( true === $ignore_permission ) ? '<a href="' . admin_url( 'admin.php?page=accessibility_checker_ignored&tab=global' ) . '" class="edac-details-rule-records-record-ignore-global">Manage Globally Ignored</a>' : '';
					} else {
						$html .= ( true === $ignore_permission ) ? '<button class="edac-details-rule-records-record-ignore-submit" data-id=' . $id . ' data-action=' . $ignore_action . ' data-type=' . $ignore_type . '>' . EDAC_SVG_IGNORE_ICON . ' <span class="edac-details-rule-records-record-ignore-submit-label">' . $ignore_submit_label . '<span></button>' : '';
					}

							$html .= ( false === $ignore_permission && false === $ignore ) ? __( 'Your user account doesn\'t have permission to ignore this issue.', 'edac' ) : '';

						$html .= '</div>';

					$html .= '</div>';

				}

				$html .= '</div>';

			}

			$html .= '</div>';

		}
	}

	if ( ! $html ) {

		$error = new WP_Error( '-3', 'No details to return' );
		wp_send_json_error( $error );

	}

	wp_send_json_success( json_encode( $html ) );

}

/**
 * Readability Ajax
 *
 * @return void
 *
 *  - '-1' means that nonce could not be varified
 *  - '-2' means that the post ID was not specified
 *  - '-3' means that there isn't any readability data to return
 */
function edac_readability_ajax() {

	// nonce security.
	if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'ajax-nonce' ) ) {

		$error = new WP_Error( '-1', 'Permission Denied' );
		wp_send_json_error( $error );

	}

	if ( ! isset( $_REQUEST['post_id'] ) ) {

		$error = new WP_Error( '-2', 'The post ID was not set' );
		wp_send_json_error( $error );

	}

	$post_id                        = intval( $_REQUEST['post_id'] );
	$html                           = '';
	$simplified_summary             = get_post_meta( $post_id, '_edac_simplified_summary', $single = true ) ?: '';
	$simplified_summary_position    = get_option( 'edac_simplified_summary_position', $default = false );
	$content_post                   = get_post( $post_id );
	$content                        = $content_post->post_content;
	$content                        = apply_filters( 'the_content', $content );
	$oxygen_builder_shortcodes_meta = get_post_meta( $post_id, 'ct_builder_shortcodes', true );

	// add oxygen builder shortcode content to readability scan.
	if ( $oxygen_builder_shortcodes_meta ) {
		$oxygen_builder_shortcodes = do_shortcode( $oxygen_builder_shortcodes_meta );
		if ( $oxygen_builder_shortcodes ) {
			$content .= $oxygen_builder_shortcodes;
		}
	}

	if ( has_filter( 'edac_filter_readability_content' ) ) {
		$content = apply_filters( 'edac_filter_readability_content', $content, $post_id );
	}
	$content         = wp_filter_nohtml_kses( $content );
	$content         = str_replace( ']]>', ']]&gt;', $content );
	$text_statistics = new TS\TextStatistics();
	// $post_grade = floor($text_statistics->fleschKincaidGradeLevel($content));

	// get readability metadata and determine if a simplified summary is required.
	$edac_summary           = get_post_meta( $post_id, '_edac_summary', true );
	$post_grade_readability = ( isset( $edac_summary['readability'] ) ) ? $edac_summary['readability'] : 0;
	$post_grade             = (int) filter_var( $post_grade_readability, FILTER_SANITIZE_NUMBER_INT );
	$post_grade_failed      = ( $post_grade < 9 ) ? false : true;

	$simplified_summary_grade        = edac_ordinal( floor( $text_statistics->fleschKincaidGradeLevel( $simplified_summary ) ) );
	$simplified_summary_grade_failed = ( $simplified_summary_grade > 9 ) ? true : false;
	$simplified_summary_prompt       = get_option( 'edac_simplified_summary_prompt' );

	$html .= '<ul class="edac-readability-list">';

	$html .= '<li class="edac-readability-list-item edac-readability-grade-level">
	<span class="edac-readability-list-item-icon dashicons ' . ( ( $post_grade_failed || 0 === $post_grade ) ? 'dashicons-no-alt' : 'dashicons-saved' ) . '"></span>
	<p class="edac-readability-list-item-title">Post Reading Grade Level: <strong class="' . ( ( $post_grade_failed || 0 === $post_grade ) ? 'failed-text-color' : 'passed-text-color' ) . '">' . ( ( 0 === $post_grade ) ? 'None' : $post_grade_readability ) . '</strong><br /></p>';
	if ( $post_grade_failed ) {
		$html .= '<p class="edac-readability-list-item-description">Your post has a reading level higher than 9th grade. Web Content Accessibility Guidelines (WCAG) at the AAA level require a simplified summary of your post that is 9th grade or below.</p>';
	} elseif ( 0 === $post_grade ) {
		$html .= '<p class="edac-readability-list-item-description">Your post does not contain enough content to calculate its reading level.</p>';
	} else {
		$html .= '<p class="edac-readability-list-item-description">A simplified summary is not necessary when content reading level is 9th grade or below. Choose when to prompt for a simplified summary on the settings page.</p>';
	}
		$html .= '</li>';

	if ( $post_grade_failed ) {

		if ( $simplified_summary ) {
			$html .= '<li class="edac-readability-list-item edac-readability-summary-grade-level">
				<span class="edac-readability-list-item-icon dashicons ' . ( ( $simplified_summary_grade_failed ) ? 'dashicons-no-alt' : 'dashicons-saved' ) . '"></span>
				<p class="edac-readability-list-item-title">Simplified Summary Reading Grade Level: <strong class="' . ( ( $simplified_summary_grade_failed ) ? 'failed-text-color' : 'passed-text-color' ) . '">' . $simplified_summary_grade . '</strong></p>
				<p class="edac-readability-list-item-description">Your simplified summary has a reading level ' . ( ( $simplified_summary_grade_failed > 9 ) ? 'higher' : 'lower' ) . ' than 9th grade.</p>
			</li>';
		}

		if ( 'none' !== $simplified_summary_position ) {

			$html .=
			'<li class="edac-readability-list-item edac-readability-summary-position">
				<span class="edac-readability-list-item-icon dashicons dashicons-saved"></span>
				<p class="edac-readability-list-item-title">Simplified summary is being automatically inserted <strong>' . $simplified_summary_position . ' the content</strong>.</p>
					<p class="edac-readability-list-item-description">Set where the Simplified Summary is inserted into the content on the <a href="' . get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=accessibility_checker_settings">settings page</a>.</p>
			</li>';

		} else {

			$html .=
			'<li class="edac-readability-list-item edac-readability-summary-position">
				<span class="edac-readability-list-item-icon"><img src="' . plugin_dir_url( __FILE__ ) . 'assets/images/warning icon yellow.png" alt="" width="22"></span>
				<p class="edac-readability-list-item-title">Simplified summary is not being automatically inserted into the content.</p>
					<p class="edac-readability-list-item-description">Your Simplified Summary location is set to "manually" which requires a function be added to your page template. If you would like the simplified summary to displayed automatically, you can change this on the <a href="' . get_bloginfo( 'url' ) . '/wp-admin/options-general.php?page=edac_settings">settings page</a>.</p>
			</li>';

		}
	}

	$html .= '</ul>';

	if ( $post_grade_failed || 'always' === $simplified_summary_prompt ) {
		$html .=
		'</form>
		<form action="/" class="edac-readability-simplified-summary">
			<label for="edac-readability-text">Simplified Summary</label>
			<textarea name="" id="edac-readability-text" cols="30" rows="10">' . $simplified_summary . '</textarea>
			<input type="submit">
		</form>';
	}

	global $wp_version;
	$html .= '<span class="dashicons dashicons-info"></span><a href="https://a11ychecker.com/help3265?utm_source=accessibility-checker&utm_medium=software&utm_term=readability&utm_content=content-analysis&utm_campaign=wordpress-general&php_version=' . PHP_VERSION . '&platform=wordpress&platform_version=' . $wp_version . '&software=free&software_version=' . EDAC_VERSION . '&days_active=' . edac_days_active() . '" target="_blank">Learn more about improving readability and simplified summary requirements</a>';

	if ( ! $html ) {

		$error = new WP_Error( '-3', 'No readability data to return' );
		wp_send_json_error( $error );

	}

	wp_send_json_success( json_encode( $html ) );

}

/**
 * Update simplified summary
 *
 * @return void
 *
 *  - '-1' means that nonce could not be varified
 *  - '-2' means that the post ID was not specified
 *  - '-3' means that the summary was not specified
 *  - '-4' means that there isn't any summary to return
 */
function edac_update_simplified_summary() {

	// nonce security.
	if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'ajax-nonce' ) ) {

		$error = new WP_Error( '-1', 'Permission Denied' );
		wp_send_json_error( $error );

	}

	if ( ! isset( $_REQUEST['post_id'] ) ) {

		$error = new WP_Error( '-2', 'The post ID was not set' );
		wp_send_json_error( $error );

	}

	if ( ! isset( $_REQUEST['summary'] ) ) {

		$error = new WP_Error( '-3', 'The summary was not set' );
		wp_send_json_error( $error );

	}

	$post_id = intval( $_REQUEST['post_id'] );
	$summary = sanitize_text_field( $_REQUEST['summary'] );

	if ( ! add_post_meta( $post_id, '_edac_simplified_summary', $summary, true ) ) {
		update_post_meta( $post_id, '_edac_simplified_summary', $summary );
	}

	$simplified_summary = get_post_meta( $post_id, '_edac_simplified_summary', $single = true );

	if ( ! $simplified_summary ) {

		$error = new WP_Error( '-4', 'No simplified summary to return' );
		wp_send_json_error( $error );

	}

	wp_send_json_success( json_encode( $simplified_summary ) );

}

/**
 * Output simplified summary
 *
 * @param string $content The content.
 * @return string
 */
function edac_output_simplified_summary( $content ) {
	$simplified_summary = edac_simplified_summary_markup( get_the_ID() );
	$simplified_summary_position = get_option( 'edac_simplified_summary_position', $default = false );
	if ( $simplified_summary && 'before' === $simplified_summary_position ) {
		return $simplified_summary . $content;
	} elseif ( $simplified_summary && 'after' === $simplified_summary_position ) {
		return $content . $simplified_summary;
	} else {
		return $content;
	}
}

/**
 * Get simplified summary
 *
 * @param integer $post Post ID.
 * @return void
 */
function edac_get_simplified_summary( int $post = null ) {
	if ( null === $post ) {
		$post = get_the_ID();
	}
	echo edac_simplified_summary_markup( $post );
}

/**
 * Simplified summary markup
 *
 * @param int $post Post ID.
 * @return string
 */
function edac_simplified_summary_markup( $post ) {
	$simplified_summary         = get_post_meta( $post, '_edac_simplified_summary', $single = true ) ?: '';
	$simplified_summary_heading = 'Simplified Summary';

	// filter title.
	if ( has_filter( 'edac_filter_simplified_summary_heading' ) ) {
		$simplified_summary_heading = apply_filters( 'edac_filter_simplified_summary_heading', $simplified_summary_heading );
	}

	if ( $simplified_summary ) {
		return '<div class="edac-simplified-summary"><h2>' . $simplified_summary_heading . '</h2><p>' . sanitize_text_field( $simplified_summary ) . '</p></div>';
	} else {
		return;
	}
}

/**
 * Get accessibility statement
 *
 * @return string
 */
function edac_get_accessibility_statement() {
	$statement              = '';
	$add_footer_statement   = get_option( 'edac_add_footer_accessibility_statement' );
	$include_statement_link = get_option( 'edac_include_accessibility_statement_link' );
	$policy_page            = get_option( 'edac_accessibility_policy_page' );
	$policy_page            = is_numeric( $policy_page ) ? get_page_link( $policy_page ) : $policy_page;

	if ( $add_footer_statement ) {
		$statement .= get_bloginfo( 'name' ) . ' ' . esc_html__( 'uses', 'edac' ) . ' <a href="https://equalizedigital.com/accessibility-checker" target="_blank" aria-label="' . esc_attr__( 'Accessibility Checker', 'edac' ) . ', opens a new window">' . esc_html__( 'Accessibility Checker', 'edac' ) . '</a> ' . esc_html__( 'to monitor our website\'s accessibility. ', 'edac' );
	}

	if ( $include_statement_link && $policy_page ) {
		$statement .= esc_html__( 'Read our ', 'edac' ) . '<a href="' . $policy_page . '">' . esc_html__( 'Accessibility Policy', 'edac' ) . '</a>.';
	}

	return $statement;
}

/**
 * Output simplified summary
 *
 * @return void
 */
function edac_output_accessibility_statement() {
	$statement = edac_get_accessibility_statement();
	if ( ! empty( $statement ) ) {
		echo '<p class="edac-accessibility-statement" style="text-align: center; max-width: 800px; margin: auto; padding: 15px;"><small>' . $statement . '</small></p>';
	}
}

/**
 * Review Admin Notice
 *
 * @return void
 */
function edac_review_notice() {

	$option             = 'edac_review_notice';
	$edac_review_notice = get_option( $option );

	// exit if option is set to stop.
	if ( 'stop' === $edac_review_notice ) {
		return;
	}

	$transient = 'edac_review_notice_reminder';
	$edac_review_notice_reminder = get_transient( $transient );

	// first time if notice has never been shown wait 14 days.
	if ( false === $edac_review_notice_reminder && false === $edac_review_notice ) {
		// if option isn't set and plugin has been active for more than 14 days show notice. This is for current users.
		if ( edac_days_active() > 14 ) {
			update_option( $option, 'play' );
		} else {
			// if plugin has been active less than 14 days set transient for 14 days.
			set_transient( $transient, true, 14 * DAY_IN_SECONDS );
			// set option to pause.
			update_option( $option, 'pause' );
		}
	}

	// if transient has expired and option is set to pause update option to play.
	if ( false === $edac_review_notice_reminder && 'pause' === $edac_review_notice ) {
		update_option( $option, 'play' );
	}

	// if option is not set to play exit.
	if ( get_option( $option ) != 'play' ) {
		return;
	}

	?>
	<div class="notice notice-info edac-review-notice">
		<p>
			<?php esc_html_e( "Hello! Thank you for using Accessibility Checker as part of your accessibility toolkit. Since you've been using it for a while, would you please write a 5-star review of Accessibility Checker in the WordPress plugin directory? This will help increase our visibility so more people can learn about the importance of web accessibility. Thanks so much!", 'edac' ); ?>
		</p>
		<p>
			<button class="edac-review-notice-review"><?php esc_html_e( 'Write A Review', 'edac' ); ?></button>
			<button class="edac-review-notice-remind"><?php esc_html_e( 'Remind Me In Two Weeks', 'edac' ); ?></button>
			<button class="edac-review-notice-dismiss"><?php esc_html_e( 'Never Ask Again', 'edac' ); ?></button>
		</p>
	</div>
	<?php
}
/**
 * Review Admin Notice Ajax
 *
 * @return void
 *
 *  - '-1' means that nonce could not be varified
 *  - '-2' means that the review action value was not specified
 *  - '-3' means that update option wasn't successful
 */
function edac_review_notice_ajax() {

	// nonce security.
	if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'ajax-nonce' ) ) {

		$error = new WP_Error( '-1', 'Permission Denied' );
		wp_send_json_error( $error );

	}

	if ( ! isset( $_REQUEST['review_action'] ) ) {

		$error = new WP_Error( '-2', 'The review action value was not set' );
		wp_send_json_error( $error );

	}

	$results = update_option( 'edac_review_notice', $_REQUEST['review_action'] );

	if ( 'pause' === $_REQUEST['review_action'] ) {
		set_transient( 'edac_review_notice_reminder', true, 14 * DAY_IN_SECONDS );
	}

	if ( ! $results ) {

		$error = new WP_Error( '-3', 'Update option wasn\'t successful' );
		wp_send_json_error( $error );

	}

	wp_send_json_success( json_encode( $results ) );

}

/**
 * Remove Admin Notices
 *
 * @return void
 */
function edac_remove_admin_notices() {

	$page = isset( $_GET['page'] ) ? $_GET['page'] : false;

	if ( $page && ( 'accessibility_checker' === $page ) || 'accessibility_checker_settings' === $page || 'accessibility_checker_issues' === $page || 'accessibility_checker_ignored' === $page ) {
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
	}

}

/**
 * Password Protected Notice Text
 *
 * @return string
 */
function edac_password_protected_notice_text() {
	$notice = 'Whoops! It looks like your website is currently password protected. The free version of Accessibility Checker can only scan live websites. To scan this website for accessibility problems either remove the password protection or <a href="https://equalizedigital.com/accessibility-checker/pricing/" target="_blank" aria-label="upgrade to accessibility checker pro, opens in a new window">upgrade to pro.</a>';

	if ( has_filter( 'edac_filter_password_protected_notice_text' ) ) {
		$notice = apply_filters( 'edac_filter_password_protected_notice_text', $notice );
	}

	return $notice;
}

/**
 * Password Protected Notice
 *
 * @return string
 */
function edac_password_protected_notice() {
	if ( get_option( 'edac_password_protected' ) === true ) {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( edac_password_protected_notice_text() ) . '</p></div>';
	} else {
		return;
	}
}

/**
 * Black Friday Admin Notice
 *
 * @return void
 */
function edac_black_friday_notice() {

	// check if accessibility checker pro is active.
	$pro = edac_check_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' );
	if ( $pro ) {
		return;
	}

	// Show from November 20-30.
	$current_date = current_time( 'Ymd' );
	$start_date   = 20221120;
	$end_date     = 20221130;
	if ( $current_date >= $start_date && $current_date <= $end_date ) {
		echo '<div class="notice notice-info is-dismissible">
			<p>Black Friday special: upgrade to a paid version of Accessibility Checker from November 20-30 and get 50% off! Full site scanning, site-wide open issues report, ignore logs, and more. <a href="https://equalizedigital.com/accessibility-checker/pricing/?utm_source=WPadmin&utm_medium=banner&utm_campaign=BlackFriday">Upgrade Now</a></p>
			</div>';
	}
}
