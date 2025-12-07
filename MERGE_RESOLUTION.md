# Merge Conflict Resolution Summary

This document describes how conflicts between the e2e testing branch and main were resolved.

## Conflicts Resolved

### 1. `.gitignore`
**Conflict**: Both branches added Playwright test directories
**Resolution**: Combined both sets of ignore patterns
- Added `blob-report/` from main
- Kept all e2e test patterns
- Kept WordPress environment patterns

### 2. `package.json`
**Conflict**: Different test scripts in both branches
**Resolution**: Merged all scripts and added TEST_TYPE environment variable
- E2E scripts: `test:e2e`, `test:e2e:headed`, `test:e2e:ui`, `test:e2e:debug`
- Visual scripts: `test:visual`, `test:visual:ui`, `test:visual:update`
- WordPress environment scripts: `env:start`, `env:stop`, `env:clean`, `env:reset`
- Server script: `serve` for visual tests
- All test scripts use `TEST_TYPE` env var to select configuration

**Dependencies Added**:
- Kept `@wordpress/env` for e2e tests
- Kept `http-server` for visual tests

### 3. `package-lock.json`
**Conflict**: Different dependency versions
**Resolution**: Deleted and regenerated with `npm install`
- Ensures all dependencies from both branches are included
- Resolves any version conflicts automatically

### 4. `playwright.config.js`
**Conflict**: Two completely different configurations
**Resolution**: Created flexible configuration supporting both test types

**Approach**:
- Uses `TEST_TYPE` environment variable to switch configurations
- `TEST_TYPE=e2e` - E2E tests with WordPress
- `TEST_TYPE=visual` - Visual regression tests
- Default: E2E tests

**E2E Configuration**:
- Test directory: `./tests/e2e`
- Timeout: 60 seconds
- Serial execution (workers: 1)
- Base URL: `http://localhost:8888` (WordPress)
- Extended timeouts for navigation and actions
- WebServer: WordPress environment

**Visual Configuration**:
- Test directory: `./tests/visual`
- Parallel execution
- Base URL: `http://localhost:8080`
- Fixed viewport: 1280x720
- WebServer: http-server

### 5. `readme.txt`
**Conflict**: Different testing sections
**Resolution**: Merged both testing documentation

**Combined Sections**:
- PHP Unit Tests (existing)
- End-to-End Tests (from this branch)
- Visual Regression Tests (from main)
- Running All Tests (combined)

## Changes from Main Branch

The merge also brought in these changes from main:

1. **Visual Regression Testing**
   - Playwright visual tests in `tests/visual/`
   - Baseline screenshots
   - Visual test documentation

2. **Copilot Instructions**
   - `.github/copilot-instructions.md`
   - Repository setup guidance

3. **Updated PHPStan Configuration**
   - Higher analysis level
   - Additional rules

4. **README.md**
   - Comprehensive visual testing guide

5. **test-app.html**
   - Test harness for visual tests

## Testing After Merge

All testing functionality is preserved:

```bash
# PHP Tests
npm run test:php

# E2E Tests
npm run test:e2e
npm run test:e2e:headed
npm run test:e2e:ui
npm run test:e2e:debug

# Visual Tests  
npm run test:visual
npm run test:visual:ui
npm run test:visual:update

# Combined
npm run test  # Runs PHP + E2E
```

## Verification

After merge resolution:
- ✅ PHP tests pass
- ✅ Build succeeds
- ✅ All npm scripts work
- ✅ No syntax errors
- ✅ Dependencies installed correctly

## Benefits of Unified Configuration

1. **Flexibility**: Easy to run either test suite
2. **Clarity**: TEST_TYPE makes it clear which tests are running
3. **Maintainability**: Single config file instead of separate configs
4. **CI Integration**: Can easily run different test suites in different jobs
5. **No Duplication**: Shared settings in baseConfig

## Future Improvements

Consider these enhancements:
- Add `test:all` script to run both e2e and visual tests
- Document TEST_TYPE in main README
- Add CI job for visual tests
- Create combined test report
