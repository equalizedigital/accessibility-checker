

const settingsOnlyPageAsPostTypeFlow = async ( page ) => {

  await page.goto('/wp-admin/admin.php?page=accessibility_checker_settings');
  await page.getByLabel('post').uncheck();
  await page.getByRole('button', { name: 'Save Changes' }).click();
  
};


export default settingsOnlyPageAsPostTypeFlow;
