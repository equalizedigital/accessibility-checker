<?php
// set up tab items
$settings_tab_items = [
	['slug' => '', 'label' => 'General','order' => 1],
	['slug' => 'license', 'label' => 'License','order' => 3],
	['slug' => 'system_info', 'label' => 'System Info','order' => 4],
];
// filter settings tab items
if(has_filter('edac_filter_settings_tab_items')) {
	$settings_tab_items = apply_filters('edac_filter_settings_tab_items', $settings_tab_items);
}
// sort settings tab items
if(is_array($settings_tab_items)){
	usort($settings_tab_items, function($a, $b) {
		return $a['order'] > $b['order'];
	});
}

//Get the active tab from the $_GET param
$default_tab = null;
$tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;
$tab = (array_search($tab, array_column($settings_tab_items, 'slug')) !== FALSE) ? $tab : $default_tab;
?>

<div class="wrap edac-settings">

	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php
	if($settings_tab_items){
		echo '<nav class="nav-tab-wrapper">';
		foreach ($settings_tab_items as $settings_tab_item) {
			$slug = $settings_tab_item['slug'] ? $settings_tab_item['slug'] : null;
			$query_var = $slug ? '&tab='.$slug : '';
			$label = $settings_tab_item['label'];
			?>
			<a <?php if($tab===$slug):?>aria-current="true" <?php endif; ?>href="?page=accessibility_checker_settings<?php echo $query_var; ?>" class="nav-tab <?php if($tab===$slug):?>nav-tab-active<?php endif; ?>"><?php echo $label; ?></a>
			<?php
		}
		echo '</nav>';
	}	
	?>

	<div class="tab-content">
		
		<?php if($tab == null){ ?>
			<div class="edac-settings-general <?php if(get_transient( 'edacp_license_valid' ) == false) echo 'edac-show-pro-callout'; ?>">
				<form action="options.php" method="post">
					<?php
						settings_fields('edac_settings');
						do_settings_sections('edac_settings');
						submit_button();
					?>
				</form>
				<?php if(get_transient( 'edacp_license_valid' ) == false){ ?>
					<div><?php include('pro-callout.php'); ?></div>
				<?php } ?>
			</div>
		<?php } ?>

		<?php if($tab == 'license'){ ?>
			<h2><?php _e('License Settings'); ?></h2>
			<?php
			if(edac_check_plugin_active('accessibility-checker-pro/accessibility-checker-pro.php')){	
				do_action('edac_license_tab');
			}else{
				include('pro-callout.php');
			}
		} ?>

		<?php if($tab == 'system_info'){ ?>
			<h2><?php _e('System Info'); ?></h2>
			
			<div class="edac-settings-system-info">
				<?php edac_sysinfo_display(); ?>
			</div>
			
		<?php } ?>

		<?php do_action('edac_settings_tab_content', $tab); ?>
		
	</div>

</div>