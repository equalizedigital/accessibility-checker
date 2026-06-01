<?php
/**
 * Accessibility Checker plugin file.
 *
 * @package Accessibility_Checker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filter the settings tab items.
 *
 * @since 1.4.0
 *
 * @param array $edac_settings_tab_items The settings tab items as an array of arrays. Needs a 'slug', 'label', and 'order'.
 */
$edac_settings_tab_items = apply_filters(
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
if ( is_array( $edac_settings_tab_items ) ) {
	$edac_settings_tab_items = array_values(
		array_filter(
			$edac_settings_tab_items,
			function ( $tab ) {
				if ( empty( $tab['capability'] ) ) {
					return true;
				}

				return current_user_can( $tab['capability'] );
			}
		)
	);

	$edac_tab_aliases     = [
		'connected-services' => 'license',
	];
	$edac_normalized_tabs = [];
	$edac_seen_tab_slugs  = [];

	foreach ( $edac_settings_tab_items as $edac_settings_tab_item ) {
		$edac_settings_tab_item['slug'] = $edac_tab_aliases[ $edac_settings_tab_item['slug'] ] ?? $edac_settings_tab_item['slug'];

		if ( in_array( $edac_settings_tab_item['slug'], $edac_seen_tab_slugs, true ) ) {
			continue;
		}

		$edac_seen_tab_slugs[]  = $edac_settings_tab_item['slug'];
		$edac_normalized_tabs[] = $edac_settings_tab_item;
	}

	$edac_settings_tab_items = $edac_normalized_tabs;

	usort(
		$edac_settings_tab_items,
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

// Null represents the default tab (empty slug) in the navigation logic.
$edac_default_tab = null;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce verification not required for tab display.
if ( isset( $_GET['tab'] ) ) {
	$edac_settings_tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
} else {
	$edac_settings_tab = $edac_default_tab;
}
// phpcs:enable WordPress.Security.NonceVerification.Recommended

if ( 'connected-services' === $edac_settings_tab && array_search( 'license', array_column( $edac_settings_tab_items, 'slug' ), true ) !== false ) {
	$edac_settings_tab = 'license';
}

if ( 'license' === $edac_settings_tab && array_search( 'license', array_column( $edac_settings_tab_items, 'slug' ), true ) === false && array_search( 'accessibility-reports', array_column( $edac_settings_tab_items, 'slug' ), true ) !== false ) {
	$edac_settings_tab = 'accessibility-reports';
}

$edac_settings_tab     = ( array_search( $edac_settings_tab, array_column( $edac_settings_tab_items, 'slug' ), true ) !== false ) ? $edac_settings_tab : $edac_default_tab;
$edac_settings_classes = [ 'wrap', 'edac-settings' ];

if ( ! EDAC_KEY_VALID ) {
	$edac_settings_classes[] = 'pro-callout-wrapper';
}

if ( 'accessibility-reports' === $edac_settings_tab ) {
	$edac_settings_classes[] = 'edac-settings--reports';
}
?>

<div class="<?php echo esc_attr( implode( ' ', $edac_settings_classes ) ); ?>">

	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php
	if ( $edac_settings_tab_items ) {
		echo '<nav class="nav-tab-wrapper" aria-label="Settings Tabs">';
		foreach ( $edac_settings_tab_items as $edac_settings_tab_item ) {
			$edac_slug      = $edac_settings_tab_item['slug'] ? $edac_settings_tab_item['slug'] : null;
			$edac_query_var = $edac_slug ? '&tab=' . $edac_slug : '';
			$edac_label     = $edac_settings_tab_item['label'];
			$edac_badge     = $edac_settings_tab_item['badge'] ?? '';
			?>
			<a
			<?php
			if ( $edac_settings_tab === $edac_slug ) :
				?>
				aria-current="true" <?php endif; ?>href="?page=accessibility_checker_settings<?php echo esc_html( $edac_query_var ); ?>" class="nav-tab
				<?php
				if ( $edac_settings_tab === $edac_slug ) :
					?>
				nav-tab-active<?php endif; ?>">
				<span class="edac-settings-tab__label"><?php echo esc_html( $edac_label ); ?></span>
				<?php if ( $edac_badge ) : ?>
					<span class="edac-settings-tab__badge"><?php echo esc_html( $edac_badge ); ?></span>
				<?php endif; ?>
			</a>
			<?php
		}
		echo '</nav>';
	}
	?>

	<div class="tab-content">

		<?php if ( null === $edac_settings_tab ) { ?>
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
		do_action( 'edac_settings_tab_content', $edac_settings_tab );
		?>
	</div>

</div>
