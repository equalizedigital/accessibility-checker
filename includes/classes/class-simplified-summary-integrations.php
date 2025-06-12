<?php
/**
 * Simplified Summary Integrations class file.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

/**
 * Class for handling simplified summary integrations with various page builders and WordPress features.
 */
class Simplified_Summary_Integrations {

	/**
	 * Constructor.
	 */
	public function __construct() {
	}

	/**
	 * Initialize WordPress hooks.
	 */
	public function init_hooks() {
		add_action( 'init', [ $this, 'init_integrations' ] );
		add_action( 'elementor/widgets/register', [ $this, 'register_elementor_widget' ] );
		add_action( 'admin_menu', [ $this, 'add_admin_menu_documentation' ], 99 );
	}

	/**
	 * Initialize all integrations.
	 *
	 * @return void
	 */
	public function init_integrations() {
		// Initialize WordPress block
		$block = new Simplified_Summary_Block();
		// Register the block immediately since we're already in the init hook
		$block->register_block();
		// Register other hooks that don't depend on init timing
		add_action( 'enqueue_block_editor_assets', array( $block, 'enqueue_block_editor_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $block, 'enqueue_frontend_styles' ) );

		// Initialize shortcode
		$shortcode = new Simplified_Summary_Shortcode();
		// Register shortcodes immediately since we're already in the init hook
		$shortcode->register_shortcodes();
	}

	/**
	 * Register Elementor widget.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_elementor_widget( $widgets_manager ) {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		// Load the Elementor widget class file manually when needed
		require_once plugin_dir_path( __FILE__ ) . 'class-simplified-summary-elementor-widget.php';

		// Only register if our widget class was successfully loaded
		if ( class_exists( 'EDAC\Inc\Simplified_Summary_Elementor_Widget' ) ) {
			$widgets_manager->register( new Simplified_Summary_Elementor_Widget() );
		}

		// Register custom Elementor category
		add_action( 'elementor/elements/categories_registered', [ $this, 'register_elementor_category' ] );
	}

	/**
	 * Register custom Elementor category.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
	 * @return void
	 */
	public function register_elementor_category( $elements_manager ) {
		$elements_manager->add_category(
			'accessibility-checker',
			[
				'title' => esc_html__( 'Accessibility Checker', 'accessibility-checker' ),
				'icon' => 'fa fa-universal-access',
			]
		);
	}

	/**
	 * Add documentation submenu to admin.
	 *
	 * @return void
	 */
	public function add_admin_menu_documentation() {
		// Only add if we have the main plugin menu
		if ( ! menu_page_url( 'accessibility_checker_settings', false ) ) {
			return;
		}

		add_submenu_page(
			'accessibility_checker_settings',
			esc_html__( 'Simplified Summary Usage', 'accessibility-checker' ),
			esc_html__( 'Usage Guide', 'accessibility-checker' ),
			'manage_options',
			'edac_simplified_summary_usage',
			[ $this, 'render_usage_page' ]
		);
	}

	/**
	 * Render the usage documentation page.
	 *
	 * @return void
	 */
	public function render_usage_page() {
		$shortcode_info = Simplified_Summary_Shortcode::get_shortcode_info();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Simplified Summary Usage Guide', 'accessibility-checker' ); ?></h1>
			
			<div class="notice notice-info">
				<p><?php echo esc_html__( 'The simplified summary feature helps make your content more accessible by providing easy-to-read summaries. Here are the different ways you can display simplified summaries on your site.', 'accessibility-checker' ); ?></p>
			</div>

			<div class="postbox" style="margin-top: 20px;">
				<div class="postbox-header">
					<h2 class="hndle"><?php echo esc_html__( 'WordPress Block (Gutenberg)', 'accessibility-checker' ); ?></h2>
				</div>
				<div class="inside">
					<p><?php echo esc_html__( 'In the Gutenberg editor, search for "Simplified Summary" in the block inserter. The block includes options for:', 'accessibility-checker' ); ?></p>
					<ul>
						<li><?php echo esc_html__( 'Showing or hiding the heading', 'accessibility-checker' ); ?></li>
						<li><?php echo esc_html__( 'Setting the heading level (H1-H6)', 'accessibility-checker' ); ?></li>
						<li><?php echo esc_html__( 'Customizing the heading text', 'accessibility-checker' ); ?></li>
						<li><?php echo esc_html__( 'Specifying a different post ID', 'accessibility-checker' ); ?></li>
					</ul>
				</div>
			</div>

			<div class="postbox">
				<div class="postbox-header">
					<h2 class="hndle"><?php echo esc_html__( 'Shortcode Usage', 'accessibility-checker' ); ?></h2>
				</div>
				<div class="inside">
					<p><?php echo esc_html__( 'Use the following shortcodes in your content:', 'accessibility-checker' ); ?></p>
					
					<h4><?php echo esc_html__( 'Basic Usage:', 'accessibility-checker' ); ?></h4>
					<?php foreach ( $shortcode_info['examples'] as $example ) : ?>
						<code style="display: block; margin: 5px 0; padding: 5px; background: #f1f1f1;"><?php echo esc_html( $example ); ?></code>
					<?php endforeach; ?>

					<h4><?php echo esc_html__( 'Available Attributes:', 'accessibility-checker' ); ?></h4>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Attribute', 'accessibility-checker' ); ?></th>
								<th><?php echo esc_html__( 'Type', 'accessibility-checker' ); ?></th>
								<th><?php echo esc_html__( 'Default', 'accessibility-checker' ); ?></th>
								<th><?php echo esc_html__( 'Description', 'accessibility-checker' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $shortcode_info['attributes'] as $attr_name => $attr_info ) : ?>
								<tr>
									<td><code><?php echo esc_html( $attr_name ); ?></code></td>
									<td><?php echo esc_html( $attr_info['type'] ); ?></td>
									<td><?php echo esc_html( $attr_info['default'] ); ?></td>
									<td><?php echo esc_html( $attr_info['description'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>

			<div class="postbox">
				<div class="postbox-header">
					<h2 class="hndle"><?php echo esc_html__( 'Elementor Widget', 'accessibility-checker' ); ?></h2>
				</div>
				<div class="inside">
					<p><?php echo esc_html__( 'If you\'re using Elementor, you can find the "Simplified Summary" widget in the "Accessibility Checker" category. The widget includes:', 'accessibility-checker' ); ?></p>
					<ul>
						<li><?php echo esc_html__( 'Content settings for heading display and post ID', 'accessibility-checker' ); ?></li>
						<li><?php echo esc_html__( 'Style controls for heading typography and colors', 'accessibility-checker' ); ?></li>
						<li><?php echo esc_html__( 'Content styling options', 'accessibility-checker' ); ?></li>
						<li><?php echo esc_html__( 'Container styling with padding, margin, borders, and background', 'accessibility-checker' ); ?></li>
					</ul>
				</div>
			</div>

			<div class="postbox">
				<div class="postbox-header">
					<h2 class="hndle"><?php echo esc_html__( 'PHP Function', 'accessibility-checker' ); ?></h2>
				</div>
				<div class="inside">
					<p><?php echo esc_html__( 'For theme developers, you can use the PHP function directly:', 'accessibility-checker' ); ?></p>
					<code style="display: block; margin: 10px 0; padding: 10px; background: #f1f1f1;">
						&lt;?php edac_get_simplified_summary(); ?&gt;<br>
						&lt;?php edac_get_simplified_summary( $post_id ); ?&gt;
					</code>
				</div>
			</div>

			<div class="postbox">
				<div class="postbox-header">
					<h2 class="hndle"><?php echo esc_html__( 'Automatic Display Settings', 'accessibility-checker' ); ?></h2>
				</div>
				<div class="inside">
					<p><?php echo esc_html__( 'You can also configure automatic display of simplified summaries in the', 'accessibility-checker' ); ?> 
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=accessibility_checker_settings' ) ); ?>"><?php echo esc_html__( 'plugin settings', 'accessibility-checker' ); ?></a>. 
					<?php echo esc_html__( 'This allows you to automatically insert simplified summaries before or after your content without needing to manually add blocks or shortcodes.', 'accessibility-checker' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get information about all available integration methods.
	 *
	 * @return array
	 */
	public static function get_integration_info() {
		return [
			'block' => [
				'name' => __( 'WordPress Block', 'accessibility-checker' ),
				'description' => __( 'Gutenberg block for the block editor', 'accessibility-checker' ),
				'available' => function_exists( 'register_block_type' ),
			],
			'shortcode' => [
				'name' => __( 'Shortcode', 'accessibility-checker' ),
				'description' => __( 'Universal shortcode that works in any content area', 'accessibility-checker' ),
				'available' => true,
			],
			'elementor' => [
				'name' => __( 'Elementor Widget', 'accessibility-checker' ),
				'description' => __( 'Native Elementor widget with full styling controls', 'accessibility-checker' ),
				'available' => class_exists( '\Elementor\Widget_Base' ),
			],
			'function' => [
				'name' => __( 'PHP Function', 'accessibility-checker' ),
				'description' => __( 'Direct PHP function for theme developers', 'accessibility-checker' ),
				'available' => true,
			],
		];
	}
}
