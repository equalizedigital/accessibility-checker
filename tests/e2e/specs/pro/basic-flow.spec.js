import { test, expect } from '@playwright/test';
import activateProFlow from '../flows/activate-pro';

test.describe.serial("Activate pro plugin flow @critical", () => {

  test('activate-pro', async ({ page }) => {

    await activateProFlow( page );

    await page.goto('/wp-admin/admin.php?page=accessibility_checker_settings&tab=scan');

    const heading = page.getByRole( 'heading', { name: 'Scan Settings' } );

    await expect( heading).toBeVisible();
  });

});

