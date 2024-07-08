<?php
/**
 * Equalize digital styled button.
 *
 * @package Accessibility_Checker
 */

$button_args = $button_args ?? [];
$defaults    = [
	'text'            => __( 'Get Accessibility Checker Pro', 'accessibility-checker' ),
	'url'             => 'https://equalizedigital.com/accessibility-checker/pricing/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=welcome-page',
	'new_tab'         => true,
	'classes_link'    => 'edac-pro-callout-button',
	'classes_wrapper' => 'edac-pro-callout-button--wrapper',
];

$button = wp_parse_args( $button_args, $defaults );
?>

<div class="<?php echo esc_attr( $button['classes_wrapper'] ); ?>">
	<a
		class="<?php echo esc_attr( $button['classes_link'] ); ?>"
		href="<?php echo esc_url( $button['url'] ); ?>"
		<?php echo true === $button['new_tab'] ? esc_html( 'target="_blank"' ) : ''; ?>
	>
		<?php
		echo esc_html( $button['text'] );
		if ( true === $button['new_tab'] ) :
			?>
			<span class="screen-reader-text"><?php esc_html_e( '(opens in a new window)', 'accessibility-checker' ); ?></span>
		<?php endif; ?>
	</a>
</div>
