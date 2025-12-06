const { defineConfig, devices } = require('@playwright/test');

/**
 * Determine which test suite to run based on TEST_TYPE environment variable
 * - 'e2e': End-to-end tests with WordPress environment
 * - 'visual': Visual regression tests
 * - default: Both test suites
 */
const testType = process.env.TEST_TYPE;

// Base configuration
const baseConfig = {
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  reporter: [
    ['html', { outputFolder: 'playwright-report' }],
    ['list']
  ],
  use: {
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
};

// E2E test configuration
const e2eConfig = {
  ...baseConfig,
  testDir: './tests/e2e',
  timeout: 60 * 1000,
  fullyParallel: false,
  workers: 1,
  retries: process.env.CI ? 2 : 1,
  reporter: [
    ['html', { outputFolder: 'test-results/html' }],
    ['json', { outputFile: 'test-results/results.json' }],
    ['list']
  ],
  use: {
    ...baseConfig.use,
    baseURL: process.env.WP_BASE_URL || 'http://localhost:8888',
    navigationTimeout: 30 * 1000,
    actionTimeout: 15 * 1000,
    video: 'retain-on-failure',
  },
  expect: {
    timeout: 15 * 1000,
  },
  webServer: process.env.CI ? undefined : {
    command: 'npm run env:start',
    port: 8888,
    timeout: 120 * 1000,
    reuseExistingServer: !process.env.CI,
  },
};

// Visual regression test configuration
const visualConfig = {
  ...baseConfig,
  testDir: './tests/visual',
  fullyParallel: true,
  workers: process.env.CI ? 1 : undefined,
  use: {
    ...baseConfig.use,
    baseURL: 'http://localhost:8080',
  },
  projects: [
    {
      name: 'chromium',
      use: { 
        ...devices['Desktop Chrome'],
        viewport: { width: 1280, height: 720 }
      },
    },
  ],
  webServer: {
    command: 'npm run serve',
    url: 'http://localhost:8080',
    reuseExistingServer: !process.env.CI,
    timeout: 120 * 1000,
  },
};

// Export appropriate configuration based on TEST_TYPE
module.exports = defineConfig(
  testType === 'visual' ? visualConfig : 
  testType === 'e2e' ? e2eConfig :
  // Default: run e2e tests (can be changed as needed)
  e2eConfig
);
