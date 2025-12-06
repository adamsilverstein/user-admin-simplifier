# Contributing to User Admin Simplifier

Thank you for your interest in contributing to User Admin Simplifier! This document provides guidelines and instructions for contributing to the project.

## Development Setup

### Prerequisites

- PHP 7.4 or higher
- Node.js 18.x or higher
- Composer
- Docker (for E2E tests)

### Initial Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/adamsilverstein/user-admin-simplifier.git
   cd user-admin-simplifier
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install Node.js dependencies:
   ```bash
   npm install
   ```

4. Build the React application:
   ```bash
   npm run build
   ```

## Development Workflow

### Building the Plugin

```bash
# Production build
npm run build

# Development build with watch mode
npm run dev
```

### Code Quality

#### Linting

```bash
# Lint JavaScript code
npm run lint

# Run PHPStan static analysis
npm run phpstan
```

Fix any linting errors before submitting your pull request.

## Testing

The project includes two types of tests:

### PHP Unit Tests

Run PHP unit tests for core functionality:

```bash
npm run test:php
```

These tests validate the menu name cleaning logic and other PHP functions.

### End-to-End (E2E) Tests

E2E tests use Playwright to test the plugin in a real WordPress environment.

#### Setting Up E2E Tests

1. Install Playwright browsers (first time only):
   ```bash
   npx playwright install
   ```

2. Start WordPress environment:
   ```bash
   npm run env:start
   ```
   
   This starts a Docker-based WordPress installation at http://localhost:8888

3. Run E2E tests:
   ```bash
   npm run test:e2e
   ```

#### E2E Test Commands

```bash
# Run tests in headless mode
npm run test:e2e

# Run tests with visible browser (useful for debugging)
npm run test:e2e:headed

# Run tests in interactive UI mode
npm run test:e2e:ui

# Run tests in debug mode with breakpoints
npm run test:e2e:debug

# View test report
npm run test:e2e:report

# Run a specific test file
npx playwright test tests/e2e/plugin-ui.spec.js
```

#### Managing WordPress Environment

```bash
# Start WordPress
npm run env:start

# Stop WordPress
npm run env:stop

# Clean WordPress data
npm run env:clean

# Reset WordPress (clean + start)
npm run env:reset
```

### Running All Tests

Run both PHP and E2E tests:

```bash
npm run test
```

## Writing Tests

### PHP Tests

PHP tests are located in `tests/test-*.php`. Follow the existing pattern for unit tests.

### E2E Tests

E2E tests are located in `tests/e2e/*.spec.js`.

#### Test Structure

```javascript
const { test, expect } = require('@playwright/test');
const { loginToWordPress, navigateToPlugin } = require('./utils/wordpress');

test.describe('Feature Name', () => {
  test.beforeEach(async ({ page }) => {
    await loginToWordPress(page);
    await navigateToPlugin(page);
  });

  test('should do something', async ({ page }) => {
    // Test implementation
    await expect(page.locator('#element')).toBeVisible();
  });
});
```

#### Helper Functions

Use helper functions from `tests/e2e/utils/wordpress.js`:

- `loginToWordPress(page, credentials)` - Login to WordPress
- `navigateToPlugin(page)` - Navigate to plugin page
- `createTestUser(page, userData)` - Create test user
- `deleteTestUser(page, username)` - Delete test user
- `getMenuItems(page)` - Get all menu items
- `isMenuVisible(page, menuName)` - Check menu visibility

#### Best Practices

1. **Clean up test data**: Use `beforeAll` and `afterAll` hooks
2. **Make tests independent**: Tests should not depend on execution order
3. **Use descriptive names**: Test names should clearly describe what they test
4. **Add waits when needed**: Use `waitForSelector` or `waitForTimeout` for dynamic content
5. **Test real user scenarios**: E2E tests should mimic actual user workflows

For more details, see `tests/e2e/README.md`.

## Pull Request Process

1. **Create a feature branch**:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes**:
   - Write clean, documented code
   - Follow existing code style
   - Add tests for new functionality

3. **Test your changes**:
   ```bash
   npm run lint
   npm run test
   ```

4. **Commit your changes**:
   ```bash
   git add .
   git commit -m "Description of your changes"
   ```

5. **Push to your fork**:
   ```bash
   git push origin feature/your-feature-name
   ```

6. **Create a Pull Request**:
   - Provide a clear description of changes
   - Reference any related issues
   - Ensure all tests pass in CI

## Continuous Integration

All pull requests automatically run:
- PHP unit tests
- E2E tests with Playwright
- Code linting

Make sure all checks pass before requesting review.

## Code Style

### JavaScript

- Use ES6+ features
- Follow React best practices
- Use meaningful variable names
- Add comments for complex logic

### PHP

- Follow WordPress coding standards
- Use type hints where possible
- Document functions with PHPDoc

## Need Help?

- Check existing issues and discussions
- Read the E2E testing documentation: `tests/e2e/README.md`
- Review existing tests for examples
- Open an issue if you have questions

## License

By contributing, you agree that your contributions will be licensed under the same MIT License that covers the project.
