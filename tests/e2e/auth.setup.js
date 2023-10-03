import { test, expect } from '@playwright/test';

const authFile = './tests/e2e/.auth/user.json';

//See: https://playwright.dev/docs/auth
test('authenticate', async ({ page }) => {
  await page.goto('/wp-login.php');
  await page.getByLabel('Username or Email Address').fill('admin');
  await page.getByLabel('Password', { exact: true }).fill('password');
  await page.getByRole('button', { name: 'Log In' }).click();

  await page.context().storageState({ path: authFile });
});


