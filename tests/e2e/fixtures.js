/**
 * Custom fixtures for User Admin Simplifier E2E tests
 * @see https://playwright.dev/docs/test-fixtures
 */

const { test as base } = require('@playwright/test');
const { loginToWordPress, navigateToPlugin } = require('./utils/wordpress');

/**
 * Custom test fixture that automatically logs in and navigates to plugin
 */
exports.test = base.extend({
  /**
   * Fixture that provides an authenticated page at the plugin admin
   */
  pluginPage: async ({ page }, use) => {
    await loginToWordPress(page);
    await navigateToPlugin(page);
    // Wait for React to render - use explicit element check instead of timeout
    await page.waitForSelector('select, [role="combobox"]', { timeout: 5000 });
    await use(page);
  },
});

exports.expect = base.expect;
