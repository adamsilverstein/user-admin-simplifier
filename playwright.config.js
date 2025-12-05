const { defineConfig, devices } = require('@playwright/test');

/**
 * Playwright configuration for User Admin Simplifier e2e tests
 * @see https://playwright.dev/docs/test-configuration
 */
module.exports = defineConfig({
  testDir: './tests/e2e',
  
  // Maximum time one test can run for
  timeout: 60 * 1000, // Increased to 60 seconds for WordPress operations
  
  // Test execution settings
  fullyParallel: false, // WordPress env doesn't support parallel tests well
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 1, // Enable retries locally for flaky tests
  workers: 1, // Run tests serially for WordPress
  
  // Reporter to use
  reporter: [
    ['html', { outputFolder: 'test-results/html' }],
    ['json', { outputFile: 'test-results/results.json' }],
    ['list']
  ],
  
  // Shared settings for all the projects below
  use: {
    // Base URL for WordPress installation
    baseURL: process.env.WP_BASE_URL || 'http://localhost:8888',
    
    // Increased timeouts for WordPress operations
    navigationTimeout: 30 * 1000,
    actionTimeout: 15 * 1000,
    
    // Collect trace when retrying the failed test
    trace: 'on-first-retry',
    
    // Screenshot on failure
    screenshot: 'only-on-failure',
    
    // Video on failure
    video: 'retain-on-failure',
  },
  
  // Global expect timeout
  expect: {
    timeout: 15 * 1000,
  },

  // Configure projects for major browsers
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],

  // Run WordPress environment before starting the tests
  webServer: process.env.CI ? undefined : {
    command: 'npm run env:start',
    port: 8888,
    timeout: 120 * 1000,
    reuseExistingServer: !process.env.CI,
  },
});
