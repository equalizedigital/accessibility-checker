<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Filter the settings tab items.
 *
 * @since 1.4.0
 *
 * @param array $settings_tab_items The settings tab items as an array of arrays. Needs a 'slug', 'label', and 'order'.
 */
$settings_tab_items = apply_filters(
	'edac_filter_settings_tab_items',
	[
		[
			'slug'  => '',
			'label' => esc_html__( 'General', 'accessibility-checker' ),
			'order' => 1,
		],
	]
);

// sort settings tab items.
if ( is_array( $settings_tab_items ) ) {
	usort(
		$settings_tab_items,
		function ( $a, $b ) {
			if ( $a['order'] < $b['order'] ) {
				return -1;
			}
			if ( $a['order'] === $b['order'] ) {
				return 0;
			}
			return 1;
		}
	);
}

// Get the active tab from the $_GET param.
$default_tab  = null;
$settings_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : $default_tab; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verification and sanitization not required for tab display.
$settings_tab = ( array_search( $settings_tab, array_column( $settings_tab_items, 'slug' ), true ) !== false ) ? $settings_tab : $default_tab;
?>

<div class="wrap edac-settings <?php echo EDAC_KEY_VALID ? '' : 'pro-callout-wrapper'; ?>">

	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php
	if ( $settings_tab_items ) {
		echo '<nav class="nav-tab-wrapper">';
		foreach ( $settings_tab_items as $settings_tab_item ) {
			$slug      = $settings_tab_item['slug'] ? $settings_tab_item['slug'] : null;
			$query_var = $slug ? '&tab=' . $slug : '';
			$label     = $settings_tab_item['label'];
			?>
			<a
			<?php
			if ( $settings_tab === $slug ) :
				?>
				aria-current="true" <?php endif; ?>href="?page=accessibility_checker_settings<?php echo esc_html( $query_var ); ?>" class="nav-tab
				<?php
				if ( $settings_tab === $slug ) :
					?>
				nav-tab-active<?php endif; ?>"><?php echo esc_html( $label ); ?></a>
			<?php
		}
		echo '</nav>';
	}
	?>

	<div class="tab-content">

		<?php if ( null === $settings_tab ) { ?>
			<div class="edac-settings-general
			<?php
			if ( EDAC_KEY_VALID === false ) {
				echo 'edac-show-pro-callout';}
			?>
			">
				<form action="options.php" method="post">
					<?php
						settings_fields( 'edac_settings' );
						do_settings_sections( 'edac_settings' );
						submit_button();
					?>
				</form>
				<?php if ( EDAC_KEY_VALID === false ) { ?>
					<div><?php include 'pro-callout.php'; ?></div>
				<?php } ?>
			</div>
		<?php } ?>

		<?php
		/**
		 * Fires after the settings tab content has maybe been displayed.
		 *
		 * This can be used to add content after a settings tab or to include
		 * a new settings tab content for custom tabs.
		 *
		 * @since 1.4.0
		 *
		 * @param string $settings_tab The current settings tab.
		 */
		do_action( 'edac_settings_tab_content', $settings_tab );
		?>
	</div>

</div>
