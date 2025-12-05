# User Admin Simplifier - Copilot Instructions

## Project Overview

User Admin Simplifier is a WordPress plugin that allows administrators to customize the WordPress admin interface on a per-user basis by hiding specific menu and submenu items, including admin bar menus.

## Architecture

- **Backend**: PHP 7.4+ WordPress plugin
- **Frontend**: React 18.2.0 application built with Webpack
- **Storage**: WordPress options API for persisting user-specific menu settings
- **AJAX Communication**: WordPress AJAX handlers for saving and resetting user settings

## Key Components

### PHP Files
- `useradminsimplifier.php` - Main plugin file with WordPress hooks and AJAX handlers
- `includes/plugin.php` - Plugin class (namespace: `User_Admin_Simplifier`)
- `uninstall.php` - Cleanup script to remove all plugin data on uninstall
- `phpstan-bootstrap.php` - Bootstrap file for PHPStan static analysis

### React Components (src/)
- `App.js` - Main application component managing state and user interactions
- `components/UserSelector.js` - User selection dropdown
- `components/MenuList.js` - Displays and manages WordPress admin menus
- `components/AdminBarMenu.js` - Displays and manages admin bar menus
- `components/SaveButton.js` - Save and reset buttons with loading states

### Build Files
- `webpack.config.js` - Webpack configuration for building React app
- `build/` - Compiled JavaScript and CSS output directory

## Development Setup

### Requirements
- PHP 7.4 or higher
- Composer (for PHP dependencies)
- Node.js and npm (for JavaScript dependencies)

### Initial Setup
```bash
composer install
npm install
npm run build
```

## Code Quality & Standards

### PHP
- **PHP Version**: 7.4+ (strict requirement)
- **Static Analysis**: PHPStan level 3
- **WordPress Standards**: Follow WordPress PHP coding standards
- **Type Safety**: Use PHPStan for type checking
- **Namespace**: Use `User_Admin_Simplifier` namespace for classes

### JavaScript/React
- **ESLint**: Configured with React and React Hooks plugins
- **React Version**: 18.2.0
- **Coding Style**: ES2021, JSX enabled
- **Prop Types**: Disabled (using TypeScript-style comments instead)

## Build Commands

### JavaScript Build
```bash
npm run build      # Production build
npm run dev        # Development build with watch mode
```

### Linting
```bash
npm run lint       # Run ESLint on src/
npm run phpstan    # Run PHPStan static analysis
```

### Testing
```bash
npm run test:php   # Run PHP unit tests
```

## Important Patterns

### WordPress Integration
- Use WordPress hooks: `add_action()`, `add_filter()`
- Nonce verification for all AJAX requests: `wp_verify_nonce()`
- Use WordPress functions: `wp_send_json_success()`, `wp_send_json_error()`
- Sanitize all input: `sanitize_text_field()`, `wp_unslash()`
- Use WordPress options API: `get_option()`, `update_option()`

### React State Management
- Use React Hooks: `useState`, `useCallback`, `useEffect`
- Global data passed via `uasData` object (localized from PHP)
- AJAX communication using Fetch API with WordPress AJAX URL

### Menu Name Handling
- Menu names may contain HTML (especially `<span>` tags for counts)
- Use `uas_clean_menu_name()` function to strip HTML from menu names
- Function must handle nested spans and complex WordPress menu structures

### Security
- Always verify nonces for AJAX requests
- Sanitize all user input before processing
- Check user capabilities before allowing modifications
- Use WordPress's built-in security functions

## File Structure Conventions

- PHP files use WordPress coding standards
- React components use functional components with hooks
- One component per file in `src/components/`
- CSS in `src/styles.css`
- All built assets go to `build/` directory

## Testing Approach

- PHP unit tests in `tests/` directory
- Tests run directly with PHP CLI
- Focus on core functionality like menu name cleaning
- Tests should include edge cases and WordPress-specific scenarios

## Common Tasks

### Adding a New Feature
1. Modify PHP backend if server-side changes needed
2. Update React components for UI changes
3. Run `npm run build` to compile assets
4. Test with `npm run lint` and `npm run phpstan`
5. Add tests if adding new PHP functions

### Fixing a Bug
1. Identify if bug is in PHP backend or React frontend
2. Make minimal surgical changes
3. Run linters and static analysis
4. Test manually in WordPress environment if possible

### Code Review Checklist
- [ ] PHPStan passes (level 3)
- [ ] ESLint passes with no errors
- [ ] All WordPress security functions used properly
- [ ] Nonces verified for AJAX requests
- [ ] Input sanitized and output escaped
- [ ] React components follow hooks patterns
- [ ] No console.log statements in production code

## WordPress Multisite Support

The plugin works on a per-site/per-user basis in multisite installations:
- Menu restrictions apply only to the current site
- User settings are site-specific, not network-wide
- User list shows only users with access to the current site

## Notes for AI Assistance

- This is a production WordPress plugin - make minimal, safe changes
- Always verify nonces for security
- React UI is built separately - run `npm run build` after JS changes
- Test PHP changes with PHPStan before committing
- The plugin stores settings in WordPress options table with key pattern `useradminsimplifier_options`
- Menu items are identified by sanitized keys using WordPress's `sanitize_key()` function
