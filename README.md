# User Admin Simplifier

Lets any Administrator simplify the WordPress Admin interface, on a per-user basis, by turning specific menu/submenu sections off.

## Description

Lets any Administrator simplify the WordPress Admin interface, on a per-user basis. Hide any specific menu or submenu (including in the admin bar) or hide the admin bar entirely.

## Development

### Requirements

* PHP 7.4 or higher
* Composer (for managing PHP dependencies)
* Node.js 18 or higher and npm (for building JavaScript assets)

### Setup

1. Clone the repository
2. Run `composer install` to install PHP dependencies
3. Run `npm install` to install JavaScript dependencies
4. Run `npm run build` to build the React application

### Code Quality

This plugin uses PHPStan for static analysis to ensure code quality and type safety.

To run PHPStan:

```bash
composer install  # first time only
npm run phpstan
```

Or directly with PHP:

```bash
php vendor/bin/phpstan analyse
```

The project is configured to run PHPStan at level 3 for improved reliability and maintainability.

## Testing

### PHP Unit Tests

Run PHP unit tests:

```bash
npm run test:php
```

### Visual Regression Testing

This plugin uses **Playwright** for automated visual regression testing to catch unintended visual changes during development.

#### Why Visual Regression Testing?

Visual differences can negatively impact user experience. Automated visual regression testing helps identify these differences early by:
- Capturing screenshots of UI components
- Comparing them to baseline images
- Detecting unintended layout changes, CSS issues, and responsive design regressions
- Providing detailed reports showing exactly what changed

#### Prerequisites

- Node.js 18 or higher
- Playwright browsers installed (Chromium by default)

#### Initial Setup

1. Install dependencies:
   ```bash
   npm install
   ```

2. Install Playwright browsers:
   ```bash
   npx playwright install --with-deps chromium
   ```

3. Build the application:
   ```bash
   npm run build
   ```

#### Running Visual Tests

```bash
# Run all visual tests
npm run test:visual

# Run tests in UI mode (interactive)
npm run test:visual:ui

# Update baseline snapshots after intentional UI changes
npm run test:visual:update
```

#### How It Works

Visual regression tests:
1. Start a local web server serving the built application
2. Navigate to test pages using a headless browser
3. Capture screenshots of UI components
4. Compare screenshots to baseline images stored in `tests/visual/app.spec.js-snapshots/`
5. Report any differences as test failures

When tests fail, Playwright generates detailed reports showing:
- What changed visually
- Side-by-side comparison of expected vs actual
- Highlighted differences

#### Test Coverage

The visual tests cover:
- **Initial state**: App before user selection
- **User selector**: Dropdown component
- **Menu interface**: Full menu system with user selected
- **Menu list**: Toggle functionality and menu items
- **Admin bar options**: Toggle and menu controls
- **Button components**: Save and reset buttons
- **Checkbox states**: Checked/unchecked menu items
- **Expandable submenus**: Collapsed and expanded states
- **Responsive layouts**: Tablet (768px) and mobile (375px) views

#### Updating Snapshots

When you make intentional UI changes:

1. Update the baseline snapshots:
   ```bash
   npm run test:visual:update
   ```

2. Review the changes in `tests/visual/app.spec.js-snapshots/` to ensure they match your intended changes

3. Commit the updated snapshots with your code changes

#### CI Integration

Visual regression tests run automatically on GitHub Actions for:
- All pull requests
- Pushes to main/master branches

The workflow:
1. Checks out the code
2. Installs dependencies
3. Builds the application
4. Runs visual tests
5. Uploads test reports and snapshots as artifacts (available for 30 days)

See `.github/workflows/visual-regression.yml` for the full configuration.

#### Troubleshooting

**Tests fail after updating dependencies:**
- Run `npm run test:visual:update` to regenerate baselines

**Tests fail on CI but pass locally:**
- Snapshots are platform-specific
- Ensure you're running tests in the same environment (Linux in CI)
- Font rendering may differ between platforms

**Need to see what failed:**
- Check the Playwright HTML report in CI artifacts
- Run `npm run test:visual:ui` locally for interactive debugging

## License

MIT License - see LICENSE file for details.
