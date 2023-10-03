const activateFreeFlow = async ( page ) => {

  await page.goto('/wp-admin/plugins.php');
  await page.getByRole('link', { name: 'Installed Plugins' }).click();
  await page.getByLabel('Activate Accessibility Checker', { exact: true }).click();

};


export default activateFreeFlow;
