<?php
//Get the active tab from the $_GET param
$default_tab = null;
$tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;
?>

<div class="wrap edac-settings">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<nav class="nav-tab-wrapper">
		<a href="?page=accessibility_checker_settings" class="nav-tab <?php if($tab===null):?>nav-tab-active<?php endif; ?>">General</a>
		<a href="?page=accessibility_checker_settings&tab=license" class="nav-tab <?php if($tab==='license'):?>nav-tab-active<?php endif; ?>">License</a>
	</nav>

	<div class="tab-content">
		<?php switch($tab) :
			case 'license': ?>
				<h2><?php _e('License Settings'); ?></h2>
				<?php
				if(edac_check_plugin_active('accessibility-checker-pro/accessibility-checker-pro.php')){	
					do_action('edac_license_tab');
				}else{
					include('pro-callout.php');
				}
			break;
			default:
				?>
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
				<?php
			break;
		endswitch; ?>
	</div>

</div>