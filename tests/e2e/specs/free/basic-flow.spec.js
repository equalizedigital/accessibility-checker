import { test, expect } from '@playwright/test';
import activateFreeFlow from '../../flows/activate-free';
import addPostFlow from '../../flows/add-post';
import updatePostFlow from '../../flows/update-post';

import settingsOnlyPageAsPostTypeFlow from '../../flows/update-settings-post-types';

test.describe.serial("Free plugin - basic flow, no username/password r@critical", () => {

  
  test('disable-welcome-guide', async ({ page }) => {
    await page.goto('/wp-admin/post.php?post=1&action=edit');
    const body = await page.waitForSelector('body', { timeout: 5000 });
    body.press('Escape');
    await page.getByLabel('Options').click();
    await page.getByRole('menuitemcheckbox', { name: 'Welcome Guide' }).click();
    await page.getByLabel('Close', { exact: true }).click();
    await page.close();
  });
  

  test('activates-free', async ({ page }) => {
    await activateFreeFlow( page );
    const text = page.getByText('Plugin activated.');
    await expect( text ).toBeVisible();
  });

  test('adds-post-with-issues', async ({ page }) => {
  // Listen for all console logs
    page.on('console', msg => console.log(msg.text()));
    const result = await addPostFlow( page, '<h3>an h3</h3><h1>an h1</h1>', 'Incorrect Heading Order', 1 );
    expect( result ).toBeTruthy();
  });

  test('update-post-fix-issues', async ({ page }) => {
    // Listen for all console logs
      page.on('console', msg => console.log(msg.text()));
      const result = await updatePostFlow( page, 8, '', 'Incorrect Heading Order', 0);
      expect( result ).toBeTruthy();
  });
  
  
  test('update-post-add-more-issues', async ({ page }) => {
    // Listen for all console logs
      page.on('console', msg => console.log(msg.text()));
      const result = await updatePostFlow( page, 8, '<a href="test"></a>', 'Empty Link', 1 );
      expect( result ).toBeTruthy();
  });
  


  test('disables-if-post-type-is-not-enabled', async ({ page }) => {
    await settingsOnlyPageAsPostTypeFlow( page );
    await page.goto('/wp-admin/post.php?post=1&action=edit');
    const header = page.getByRole('heading', { name: 'Accessibility Checker' });
    await expect( header ).not.toBeVisible();
  });

  test('enables-if-post-type-is-enabled', async ({ page }) => {
    await settingsOnlyPageAsPostTypeFlow( page );
    await page.goto('/wp-admin/post.php?post=2&action=edit');
    const header = page.getByRole('heading', { name: 'Accessibility Checker' });
    await expect( header ).toBeVisible();
  });


});

