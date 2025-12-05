# End-to-End (E2E) Tests

This directory contains end-to-end tests for the User Admin Simplifier plugin using [Playwright](https://playwright.dev/).

## Overview

The E2E tests validate critical user flows and integrations:

- **Plugin UI Tests** (`plugin-ui.spec.js`): Tests that the admin interface loads correctly
- **Menu Management Tests** (`menu-management.spec.js`): Tests menu hiding/showing functionality
- **Admin Bar Tests** (`admin-bar.spec.js`): Tests admin bar customization features
- **Menu Visibility Tests** (`menu-visibility.spec.js`): Tests that menus are actually hidden for restricted users

## Prerequisites

- Node.js 18.x or higher
- Docker (for WordPress environment)
- npm or yarn

## Setup

1. **Install dependencies:**
   ```bash
   npm install
   ```

2. **Build the plugin:**
   ```bash
   npm run build
   ```

3. **Install Playwright browsers:**
   ```bash
   npx playwright install
   ```

## Running Tests

### Quick Start

Run all E2E tests:
```bash
npm run test:e2e
```

### WordPress Environment

The tests require a WordPress environment. You can manage it with these commands:

```bash
# Start WordPress environment (Docker)
npm run env:start

# Stop WordPress environment
npm run env:stop

# Clean/reset WordPress environment
npm run env:clean
npm run env:reset
```

The WordPress environment will be available at:
- Frontend: http://localhost:8888
- Admin: http://localhost:8888/wp-admin
- Default credentials: admin / password

### Test Execution Modes

```bash
# Run tests in headless mode (default)
npm run test:e2e

# Run tests with visible browser
npm run test:e2e:headed

# Run tests in UI mode (interactive)
npm run test:e2e:ui

# Run tests in debug mode
npm run test:e2e:debug

# Run specific test file
npx playwright test tests/e2e/plugin-ui.spec.js

# Run tests matching a pattern
npx playwright test --grep "should load plugin"
```

### Viewing Test Reports

After running tests, view the HTML report:
```bash
npm run test:e2e:report
```

Reports are saved in `test-results/html/` and include:
- Test results and timing
- Screenshots of failures
- Video recordings of failed tests
- Detailed logs

## Writing Tests

### Test Structure

```javascript
const { test, expect } = require('@playwright/test');
const { loginToWordPress, navigateToPlugin } = require('./utils/wordpress');

test.describe('Feature Name', () => {
  test.beforeEach(async ({ page }) => {
    await loginToWordPress(page);
    await navigateToPlugin(page);
  });

  test('should do something', async ({ page }) => {
    // Your test code
    await expect(page.locator('#element')).toBeVisible();
  });
});
```

### Helper Functions

The `utils/wordpress.js` file provides helpful utilities:

- `loginToWordPress(page, credentials)` - Login to WordPress admin
- `navigateToPlugin(page)` - Navigate to the plugin admin page
- `createTestUser(page, userData)` - Create a test user
- `deleteTestUser(page, username)` - Delete a test user
- `getMenuItems(page)` - Get all WordPress menu items
- `isMenuVisible(page, menuName)` - Check if a menu is visible

### Best Practices

1. **Use descriptive test names**: Tests should clearly describe what they're testing
2. **Clean up after yourself**: Use `beforeAll` and `afterAll` hooks to create/delete test data
3. **Use proper waits**: Use `waitForSelector` or `waitForTimeout` when needed
4. **Make tests independent**: Each test should work standalone
5. **Use page object patterns**: Consider creating page objects for complex interactions

## Continuous Integration

The E2E tests run automatically on:
- Every push to `main` or `develop` branches
- Every pull request

See `.github/workflows/e2e-tests.yml` for the CI configuration.

### CI Environment

In CI, tests run with:
- Headless Chromium browser
- WordPress environment in Docker
- Test retries enabled (2 retries on failure)
- Automatic artifact upload for failed tests

## Troubleshooting

### Tests Timing Out

If tests are timing out, try:
1. Increasing timeout in `playwright.config.js`
2. Adding explicit waits: `await page.waitForTimeout(1000)`
3. Checking if WordPress environment is fully started

### WordPress Environment Issues

```bash
# Reset the environment
npm run env:reset

# Check Docker containers
docker ps

# View WordPress logs
npm run env:logs
```

### Browser Installation Issues

```bash
# Reinstall Playwright browsers
npx playwright install --force
```

### Debugging Failed Tests

1. Run in headed mode to see browser:
   ```bash
   npm run test:e2e:headed
   ```

2. Use debug mode with breakpoints:
   ```bash
   npm run test:e2e:debug
   ```

3. Check screenshots in `test-results/` directory

4. View the HTML report:
   ```bash
   npm run test:e2e:report
   ```

## Code Coverage

To generate code coverage for E2E tests:

```bash
# Coverage is automatically included in test reports
npm run test:e2e
```

Coverage reports show:
- Which parts of the plugin are exercised by tests
- Lines/functions that need more test coverage

## Contributing

When adding new features:

1. Write E2E tests for critical user flows
2. Ensure tests pass locally before submitting PR
3. Tests will run automatically in CI
4. Update this README if adding new test utilities or patterns

## Resources

- [Playwright Documentation](https://playwright.dev/docs/intro)
- [WordPress E2E Testing Best Practices](https://make.wordpress.org/core/handbook/testing/automated-testing/end-to-end-testing/)
- [Plugin Repository](https://github.com/adamsilverstein/user-admin-simplifier)
