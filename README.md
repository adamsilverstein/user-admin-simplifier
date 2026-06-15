# User Admin Simplifier

Lets any Administrator simplify the WordPress Admin interface, on a per-user basis, by turning specific menu/submenu sections off.

## Description

Lets any Administrator simplify the WordPress Admin interface, on a per-user basis. Hide any specific menu or submenu (including in the admin bar) or hide the admin bar entirely.

## Role-based menu control

User Admin Simplifier offers three menu control modes:

- **Per-user only** - the default mode. Configure menu, submenu, and admin bar visibility for each individual user. This is the original behavior and is unchanged.
- **Role-based only** - configure menu, submenu, and admin bar visibility (and menu order) once per WordPress role. Every user inherits the settings for their role. Users with multiple roles get the union of all their roles' settings for visibility, while menu order comes from the primary role only.
- **Role-based with per-user overrides** - applies the role defaults, but lets you override each menu item per user with an Inherit / Show / Hide control.

Existing per-user configurations are preserved, and existing installs see no change unless an Administrator switches modes. Regardless of mode, the User Admin Simplifier settings page (under Tools) always remains accessible to Administrators, so you can never lock yourself out.

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

## Deployment

Releases are deployed to the [WordPress.org plugin directory](https://wordpress.org/plugins/user-admin-simplifier/) automatically via the [10up/action-wordpress-plugin-deploy](https://github.com/10up/action-wordpress-plugin-deploy) GitHub Action. See `.github/workflows/deploy.yml`.

### How it works

Publishing a GitHub release for a semantic version tag (e.g. `3.0.1` or `v3.0.1`) triggers the deployment workflow, which:

1. Verifies the release is not a draft or prerelease
2. Verifies the release tag, the `Stable tag` in `readme.txt`, and the `Version` in the plugin header all match
3. Builds the JavaScript assets with `npm run build`
4. Commits the plugin to the WordPress.org SVN repository (trunk and a version tag), excluding development files listed in `.distignore`

### Releasing a new version

1. Update the version number in `useradminsimplifier.php` (`Version` header), `readme.txt` (`Stable tag`), and `package.json`
2. Update the changelog in `readme.txt`
3. Merge the changes to `main`
4. Create and publish a GitHub release with a tag matching the new version (e.g. `3.0.1`)

The workflow then deploys to WordPress.org. If any version check fails, the workflow exits without deploying.

### Required repository secrets

The workflow needs two [GitHub Actions secrets](https://docs.github.com/en/actions/security-for-github-actions/security-guides/using-secrets-in-github-actions) configured under **Settings → Secrets and variables → Actions**:

| Secret | Description |
|--------|-------------|
| `SVN_USERNAME` | WordPress.org username with commit access to the plugin SVN repository |
| `SVN_PASSWORD` | The corresponding WordPress.org password |

### Excluded files

Development files (source, tests, build tooling, CI configuration) are excluded from the deployed package via `.distignore`. The deployed package contains only the runtime files: the main plugin file, `uninstall.php`, `readme.txt`, and the `build/`, `includes/`, and `images/` directories.

## License

MIT License - see LICENSE file for details.
