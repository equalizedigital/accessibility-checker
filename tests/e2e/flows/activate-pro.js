const activateProFlow = async ( page ) => {

  await page.goto('/wp-admin/plugins.php');
  await page.getByRole('link', { name: 'Installed Plugins' }).click();
  await page.getByLabel('Activate Accessibility Checker Pro').click();
  await page.goto('/wp-admin/admin.php?page=accessibility_checker_settings&tab=license');

  await page.getByLabel('Enter your license key').fill(process.env.LICENSE);
  await page.getByRole('button', { name: 'Activate License' }).click();

};


export default activateProFlow;
