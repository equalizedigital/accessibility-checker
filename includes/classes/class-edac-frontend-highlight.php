<?php

class EDAC_Frontend_Highlight {

	public function __construct() {
		add_action( 'wp_ajax_edac_frontend_highlight_ajax', array( $this, 'ajax' ) );
		add_action( 'wp_ajax_nopriv_edac_frontend_highlight_ajax', array( $this, 'ajax' ) );
		add_action( 'wp_head', array( $this, 'panel' ) );
	}

	public function get_issues( $post_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';
		$post_id    = intval( $post_id );
		$siteid     = get_current_blog_id();
		$results    = $wpdb->get_results( $wpdb->prepare( 'SELECT id, rule, object, ruletype FROM ' . $table_name . ' where postid = %d and siteid = %d', $post_id, $siteid ), ARRAY_A );
		if ( ! $results ) {
			return null;
		}
		return $results;
	}

	public function ajax() {
		//alert('test');
		// nonce security.
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'ajax-nonce' ) ) {
			$error = new WP_Error( '-1', 'Permission Denied' );
			wp_send_json_error( $error );
		}

		if ( ! isset( $_REQUEST['post_id'] ) ) {
			$error = new WP_Error( '-2', 'The id value was not set' );
			wp_send_json_error( $error );
		}

		$results = $this->get_issues( $_REQUEST['post_id'] );

		if ( ! $results ) {
			$error = new WP_Error( '-3', 'Issue query returned no results' );
			wp_send_json_error( $error );
		}

		$rules = edac_register_rules();
		//$rule  = edac_filter_by_value( $rules, 'slug', $results['rule'] );

		// if ( ! $rule ) {
		// 	$error = new WP_Error( '-4', 'Rule value not set' );
		// 	wp_send_json_error( $error );
		// }

		// $results['rule_title'] = $rule[0]['title'];
		// $results['summary']    = $rule[0]['summary'];
		// $results['link']       = edac_documentation_link( $rule[0] );
		// $results['object']     = html_entity_decode( esc_html( $results['object'] ) );

		$output = [];
		foreach ( $results as $result ) {
			$array = [];
			$rule  = edac_filter_by_value( $rules, 'slug', $result['rule'] );

			$array['rule_type']  = $rule[0]['rule_type'];
			$array['rule_title'] = $rule[0]['title'];
			$array['summary']    = $rule[0]['summary'];
			$array['link']       = edac_documentation_link( $rule[0] );
			$array['object']     = html_entity_decode( esc_html( $result['object'] ) );

			$output[] = $array;
		}

		if ( ! $output ) {

			$error = new WP_Error( '-5', 'Object query returned no results' );
			wp_send_json_error( $error );

		}

		wp_send_json_success( wp_json_encode( $output ) );
	}

	public function panel() {
		?>

		<div class="edac-highlight-panel">
			<button id="edac-highlight-panel-toggle" class="edac-highlight-panel-toggle" title="Toggle accessibility tools"></button>
			<div class="edac-highlight-panel-description">
				<div class="edac-highlight-panel-description-title">Text Justified</div>
				<p>A warning about missing headings means that your post or page does not contain any heading elements (<h1>–<h6>) within the content of the post or page body section, which can make it especially difficult for screen reader users to navigate through the content on the page. To fix a page with no headings, you will need to add heading elements. At a minimum, every page should have one <h1> tag, which is typically the page title. Add additional subheadings as appropriate for your content. If you have determined that headings are definitely not needed on the page, then you can “Ignore” the warning.</p>
				<a href="#" class="edac-highlight-panel-description-reference" target="_self" aria-label="Read documentation for ${value.rule_title}, opens new window">Full Documentation</a>
			</div>
			<div id="edac-highlight-panel-controls" class="edac-highlight-panel-controls">
			Errors
					Warnings
				
				
				<button class="edac-highlight-panel-close" aria-label="Close accessibility highlights panel"></button>
				<button id="edac-highlight-previous">previous</button>
				<button id="edac-highlight-next">Next</button>
				<button id="edac-highlight-disable-styles">Disable Styles</button>
			</div>
		</div>


		<?php
	}

}

new EDAC_Frontend_Highlight();
//var_dump( $frontend_highlight->get_issues( 3842 ) );
