<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

// set up tab items.
$settings_tab_items = array(
	array(
		'slug'  => '',
		'label' => 'General',
		'order' => 1,
	),
	array(
		'slug'  => 'system_info',
		'label' => 'System Info',
		'order' => 4,
	),
);
// filter settings tab items.
if ( has_filter( 'edac_filter_settings_tab_items' ) ) {
	$settings_tab_items = apply_filters( 'edac_filter_settings_tab_items', $settings_tab_items );
}
// sort settings tab items.
if ( is_array( $settings_tab_items ) ) {
	usort(
		$settings_tab_items,
		function( $a, $b ) {
			return strcmp($b['order'], $a['order']);
		}
	);
}

// Get the active tab from the $_GET param.
$default_tab = null;
$settings_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $default_tab;
$settings_tab = ( array_search( $settings_tab, array_column( $settings_tab_items, 'slug' ) ) !== false ) ? $settings_tab : $default_tab;
?>

<div class="wrap edac-settings">

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

		<?php if ( 'system_info' === $settings_tab ) { ?>
			<h2><?php esc_html_e( 'System Info' ); ?></h2>	
			<div class="edac-settings-system-info">
				<?php edac_sysinfo_display(); ?>
			</div>	
		<?php } ?>

		<?php do_action( 'edac_settings_tab_content', $settings_tab ); ?>	
	</div>

</div>
