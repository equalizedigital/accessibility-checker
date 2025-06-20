<?php
/**
 * Simplified Summary Elementor Widget class file.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Border;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;

/**
 * Simplified Summary Elementor Widget.
 *
 * Elementor widget that displays simplified summary content for accessibility.
 *
 * @since 1.24.0
 */
class Simplified_Summary_Elementor_Widget extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * Retrieve Simplified Summary widget name.
	 *
	 * @since 1.24.0
	 * @access public
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'edac_simplified_summary';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve Simplified Summary widget title.
	 *
	 * @since 1.24.0
	 * @access public
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Accessibility Summary', 'accessibility-checker' );
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve Simplified Summary widget icon.
	 *
	 * @since 1.24.0
	 * @access public
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-accessible';
	}

	/**
	 * Get custom help URL.
	 *
	 * Retrieve a URL where the user can get more information about the widget.
	 *
	 * @since 1.24.0
	 * @access public
	 * @return string Widget help URL.
	 */
	public function get_custom_help_url() {
		return 'https://equalizedigital.com/accessibility-checker/documentation/';
	}

	/**
	 * Get widget categories.
	 *
	 * Retrieve the list of categories the Simplified Summary widget belongs to.
	 *
	 * @since 1.24.0
	 * @access public
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'accessibility-checker' ];
	}

	/**
	 * Get widget keywords.
	 *
	 * Retrieve the list of keywords the Simplified Summary widget belongs to.
	 *
	 * @since 1.24.0
	 * @access public
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return [ 'accessibility', 'summary', 'simple', 'readable', 'a11y' ];
	}

	/**
	 * Register Simplified Summary widget controls.
	 *
	 * Add input fields to allow the user to customize the widget settings.
	 *
	 * @since 1.24.0
	 * @access protected
	 */
	protected function register_controls() {

		// Content Section
		$this->start_controls_section(
			'content_section',
			[
				'label' => esc_html__( 'Content', 'accessibility-checker' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'show_heading',
			[
				'label'        => esc_html__( 'Show Heading', 'accessibility-checker' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'accessibility-checker' ),
				'label_off'    => esc_html__( 'Hide', 'accessibility-checker' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'heading_level',
			[
				'label'     => esc_html__( 'Heading Level', 'accessibility-checker' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h2',
				'options'   => [
					'h1' => esc_html__( 'H1', 'accessibility-checker' ),
					'h2' => esc_html__( 'H2', 'accessibility-checker' ),
					'h3' => esc_html__( 'H3', 'accessibility-checker' ),
					'h4' => esc_html__( 'H4', 'accessibility-checker' ),
					'h5' => esc_html__( 'H5', 'accessibility-checker' ),
					'h6' => esc_html__( 'H6', 'accessibility-checker' ),
				],
				'condition' => [
					'show_heading' => 'yes',
				],
			]
		);

		$this->add_control(
			'custom_heading',
			[
				'label'       => esc_html__( 'Custom Heading Text', 'accessibility-checker' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'Simplified Summary', 'accessibility-checker' ),
				'description' => esc_html__( 'Leave empty to use the default heading text.', 'accessibility-checker' ),
				'condition'   => [
					'show_heading' => 'yes',
				],
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->end_controls_section();

		// Heading Style Section
		$this->start_controls_section(
			'heading_style_section',
			[
				'label'     => esc_html__( 'Heading', 'accessibility-checker' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_heading' => 'yes',
				],
			]
		);

		$this->add_control(
			'heading_color',
			[
				'label'     => esc_html__( 'Text Color', 'accessibility-checker' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => [
					'default' => Global_Colors::COLOR_PRIMARY,
				],
				'selectors' => [
					'{{WRAPPER}} .edac-simplified-summary h1' => 'color: {{VALUE}};',
					'{{WRAPPER}} .edac-simplified-summary h2' => 'color: {{VALUE}};',
					'{{WRAPPER}} .edac-simplified-summary h3' => 'color: {{VALUE}};',
					'{{WRAPPER}} .edac-simplified-summary h4' => 'color: {{VALUE}};',
					'{{WRAPPER}} .edac-simplified-summary h5' => 'color: {{VALUE}};',
					'{{WRAPPER}} .edac-simplified-summary h6' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'heading_typography',
				'global'   => [
					'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
				],
				'selector' => '{{WRAPPER}} .edac-simplified-summary h1, {{WRAPPER}} .edac-simplified-summary h2, {{WRAPPER}} .edac-simplified-summary h3, {{WRAPPER}} .edac-simplified-summary h4, {{WRAPPER}} .edac-simplified-summary h5, {{WRAPPER}} .edac-simplified-summary h6',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'heading_text_shadow',
				'selector' => '{{WRAPPER}} .edac-simplified-summary h1, {{WRAPPER}} .edac-simplified-summary h2, {{WRAPPER}} .edac-simplified-summary h3, {{WRAPPER}} .edac-simplified-summary h4, {{WRAPPER}} .edac-simplified-summary h5, {{WRAPPER}} .edac-simplified-summary h6',
			]
		);

		$this->add_responsive_control(
			'heading_margin',
			[
				'label'      => esc_html__( 'Margin', 'accessibility-checker' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .edac-simplified-summary h1' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .edac-simplified-summary h2' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .edac-simplified-summary h3' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .edac-simplified-summary h4' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .edac-simplified-summary h5' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .edac-simplified-summary h6' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Content Style Section
		$this->start_controls_section(
			'content_style_section',
			[
				'label' => esc_html__( 'Content', 'accessibility-checker' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'content_color',
			[
				'label'     => esc_html__( 'Text Color', 'accessibility-checker' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => [
					'default' => Global_Colors::COLOR_TEXT,
				],
				'selectors' => [
					'{{WRAPPER}} .edac-simplified-summary p' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'content_typography',
				'global'   => [
					'default' => Global_Typography::TYPOGRAPHY_TEXT,
				],
				'selector' => '{{WRAPPER}} .edac-simplified-summary p',
			]
		);

		$this->add_responsive_control(
			'content_margin',
			[
				'label'      => esc_html__( 'Margin', 'accessibility-checker' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .edac-simplified-summary p' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Container Style Section
		$this->start_controls_section(
			'container_style_section',
			[
				'label' => esc_html__( 'Container', 'accessibility-checker' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'background_color',
			[
				'label'     => esc_html__( 'Background Color', 'accessibility-checker' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .edac-simplified-summary' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'container_padding',
			[
				'label'      => esc_html__( 'Padding', 'accessibility-checker' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .edac-simplified-summary' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'container_margin',
			[
				'label'      => esc_html__( 'Margin', 'accessibility-checker' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .edac-simplified-summary' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'container_border',
				'selector' => '{{WRAPPER}} .edac-simplified-summary',
			]
		);

		$this->add_responsive_control(
			'container_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'accessibility-checker' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .edac-simplified-summary' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render Simplified Summary widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.24.0
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		// Get current post ID
		$post_id = get_the_ID();

		if ( ! $post_id ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="edac-simplified-summary-error">';
				echo '<p>' . esc_html__( 'No post ID available for simplified summary.', 'accessibility-checker' ) . '</p>';
				echo '</div>';
			}
			return;
		}

		// Check if the Simplified_Summary class exists
		if ( ! class_exists( 'EDAC\Inc\Simplified_Summary' ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="edac-simplified-summary-error">';
				echo '<p>' . esc_html__( 'Simplified Summary functionality is not available.', 'accessibility-checker' ) . '</p>';
				echo '</div>';
			}
			return;
		}

		// Get the simplified summary content
		$simplified_summary_text = get_post_meta( $post_id, '_edac_simplified_summary', true );

		if ( empty( $simplified_summary_text ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="edac-simplified-summary-placeholder">';
				echo '<p>' . esc_html__( 'No simplified summary available for this content.', 'accessibility-checker' ) . '</p>';
				echo '</div>';
			}
			return;
		}

		// Build the output
		$show_heading = 'yes' === $settings['show_heading'];
		$heading_level = $settings['heading_level'];
		$custom_heading = $settings['custom_heading'];

		// Get heading text
		if ( empty( $custom_heading ) ) {
			/**
			 * Filter the heading that gets output before the simplified summary.
			 *
			 * @since 1.4.0
			 *
			 * @param string $simplified_summary_heading The simplified summary heading.
			 */
			$heading_text = apply_filters(
				'edac_filter_simplified_summary_heading',
				esc_html__( 'Simplified Summary', 'accessibility-checker' )
			);
		} else {
			$heading_text = $custom_heading;
		}

		// Output the simplified summary
		echo '<div class="edac-simplified-summary">';
		
		if ( $show_heading ) {
			printf(
				'<%1$s>%2$s</%1$s>',
				esc_attr( $heading_level ),
				wp_kses_post( $heading_text )
			);
		}
		
		echo '<p>' . wp_kses_post( $simplified_summary_text ) . '</p>';
		echo '</div>';
	}

	/**
	 * Render Simplified Summary widget output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @since 1.24.0
	 * @access protected
	 */
	protected function content_template() {
		?>
		<#
		var show_heading = 'yes' === settings.show_heading;
		var heading_level = settings.heading_level || 'h2';
		var custom_heading = settings.custom_heading || '<?php echo esc_js( __( 'Simplified Summary', 'accessibility-checker' ) ); ?>';
		#>
		<div class="edac-simplified-summary">
			<# if ( show_heading ) { #>
				<{{{ heading_level }}}>{{{ custom_heading }}}</{{{ heading_level }}}>
			<# } #>
			<p><?php echo esc_html__( 'Simplified summary content will appear here on the frontend.', 'accessibility-checker' ); ?></p>
		</div>
		<?php
	}
}