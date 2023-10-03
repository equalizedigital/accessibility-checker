// @ts-check
const { defineConfig, devices } = require('@playwright/test');

import { config as dotenvConfig } from 'dotenv';
dotenvConfig({ path: './.wp-env/cfg/.env' });

const httpCredentials = {
  username: 'admin',
  password: 'password',
};

const btoa = (str) => Buffer.from(str).toString('base64');
const credentialsBase64 = btoa(`${httpCredentials.username}:${httpCredentials.password}`);

/**
 * @see https://playwright.dev/docs/test-configuration
 */
module.exports = defineConfig({
  globalSetup: require.resolve( './tests/e2e/globalSetup.js' ),
  testDir: './tests/e2e',
  testMatch: '**/*.js',

  /* Run tests in files in parallel */
  fullyParallel: true,
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  /* Retry on CI only */
  retries: process.env.CI ? 2 : 0,
  /* Opt out of parallel tests on CI. */
  workers: process.env.CI ? 1 : undefined,
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: 'html',
  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Base URL to use in actions like `await page.goto('/')`. */
     baseURL: 'http://localhost:8889',
     extraHTTPHeaders: {
      'Authorization': `Basic ${credentialsBase64}`
     },

    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'on-first-retry',
  },

  /* Configure projects for major browsers */
  projects: [
    { name: 'setup', testMatch: /.*\.setup\.js/ },

    {
      name: 'chromium',
      use: { 
        ...devices['Desktop Chrome'],
        storageState: './tests/e2e/.auth/user.json'
      },
      dependencies: ['setup'], //see: https://playwright.dev/docs/auth
    },

    // Add more browsers here.

  ]

});

