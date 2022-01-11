<div class="edac-pro-callout">
    <img class="edac-pro-callout-icon" src="<?php echo plugin_dir_url( __DIR__ ); ?>assets/images/edac-emblem.png" alt="Equalize Digital Logo">
    <h4 class="edac-pro-callout-title">Upgrade to Accessibility Checker Pro</h4>
    <div>
        <ul class="edac-pro-callout-list">
            <li>Scan all post types</li>
            <li>Admin columns to see accessibility status at a glance</li>
            <li>Centralized list of all open issues</li>
            <li>Ignore log</li>
            <li>Rename simplified summary</li>
            <li>User restrictions on ignoring issues</li>
            <li>Accessibility Statement draft</li>
            <li>Email support</li>
            <li>...and more</li>
        </ul>
    </div>
    <a class="edac-pro-callout-button" href="https://my.equalizedigital.com/#pricing" target="_blank">Get Accessibility Checker Pro</a>

    <?php if(is_plugin_active('accessibility-checker-pro/accessibility-checker-pro.php')){ ?>
        <br /><a class="edac-pro-callout-activate" href="<?php echo admin_url('admin.php?page=accessibility_checker_settings&tab=license'); ?>">Or activate your license key here.</a>
    <?php } ?>
     
    
</div>