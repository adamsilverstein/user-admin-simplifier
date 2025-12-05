/**
 * Visual regression tests for User Admin Simplifier
 * These tests capture screenshots and compare them to baseline images
 * to detect unintended visual changes.
 */

const { test, expect } = require('@playwright/test');

// Test constants
const SELECTORS = {
  ROOT: '#uas-react-root .wrap',
  USER_SELECT: 'select[name="uas_user_select"]',
  CHOOSE_USER: '#chooseauser',
  CHOOSE_MENUS: '#choosemenus',
  MENU_LIST: '.menu-list',
  ADMIN_BAR_TOGGLE: '.uas-admin-bar-toggle',
  BUTTONS: '.uas-buttons',
  SUBMENU_TOGGLE: '.submenu-toggle',
  WRAP: '.wrap'
};

const USERS = {
  ADMIN: 'admin',
  EDITOR: 'editor'
};

const VIEWPORT = {
  TABLET: { width: 768, height: 1024 },
  MOBILE: { width: 375, height: 667 }
};

test.describe('User Admin Simplifier Visual Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to the test page
    await page.goto('/test-app.html');
    
    // Wait for the React app to load
    await page.waitForSelector(SELECTORS.ROOT);
  });

  test('initial state - no user selected', async ({ page }) => {
    // Capture the initial state before any user is selected
    await expect(page.locator(SELECTORS.WRAP)).toHaveScreenshot('initial-state.png');
  });

  test('user selector dropdown', async ({ page }) => {
    // Take a screenshot of the user selector area
    await expect(page.locator(SELECTORS.CHOOSE_USER)).toHaveScreenshot('user-selector.png');
  });

  test('user selected - shows full menu interface', async ({ page }) => {
    // Select a user from the dropdown
    await page.selectOption(SELECTORS.USER_SELECT, USERS.ADMIN);
    
    // Wait for the menu sections to appear
    await page.waitForSelector(SELECTORS.CHOOSE_MENUS);
    
    // Capture the full interface with menus visible
    await expect(page.locator(SELECTORS.WRAP)).toHaveScreenshot('user-selected-full-view.png');
  });

  test('menu list with toggle all button', async ({ page }) => {
    // Select a user
    await page.selectOption(SELECTORS.USER_SELECT, USERS.EDITOR);
    await page.waitForSelector(`${SELECTORS.CHOOSE_MENUS} ${SELECTORS.MENU_LIST}`);
    
    // Capture just the menu list section
    await expect(page.locator(`${SELECTORS.CHOOSE_MENUS} ${SELECTORS.MENU_LIST}`)).toHaveScreenshot('menu-list-section.png');
  });

  test('admin bar toggle and menus', async ({ page }) => {
    // Select a user
    await page.selectOption(SELECTORS.USER_SELECT, USERS.EDITOR);
    await page.waitForSelector(SELECTORS.ADMIN_BAR_TOGGLE);
    
    // Capture from the admin bar title through the admin bar menus
    const adminBarSection = page.locator(SELECTORS.WRAP).locator('xpath=//h3[contains(text(), "admin bar")]/following-sibling::*');
    await expect(adminBarSection.first()).toHaveScreenshot('admin-bar-section.png');
  });

  test('save and reset buttons', async ({ page }) => {
    // Select a user
    await page.selectOption(SELECTORS.USER_SELECT, USERS.ADMIN);
    await page.waitForSelector(SELECTORS.BUTTONS);
    
    // Capture the button area
    await expect(page.locator(SELECTORS.BUTTONS)).toHaveScreenshot('save-reset-buttons.png');
  });

  test('menu items with some checked', async ({ page }) => {
    // Select the admin user (which has some items pre-checked in mock data)
    await page.selectOption(SELECTORS.USER_SELECT, USERS.ADMIN);
    await page.waitForSelector(SELECTORS.CHOOSE_MENUS);
    
    // Capture just the menu area with checked items
    await expect(page.locator(SELECTORS.MENU_LIST)).toHaveScreenshot('checked-menu-items.png');
  });

  test('expandable submenu - click to expand', async ({ page }) => {
    // Select a user
    await page.selectOption(SELECTORS.USER_SELECT, USERS.EDITOR);
    await page.waitForSelector(SELECTORS.SUBMENU_TOGGLE);
    
    // Find and click to expand a menu with submenus
    const toggleButton = page.locator(SELECTORS.SUBMENU_TOGGLE).first();
    await toggleButton.click();
    
    // Wait for the submenu to be visible (proper waiting instead of timeout)
    await page.locator('.submenuinner').first().waitFor({ state: 'visible' });
    
    // Capture the expanded state
    await expect(page.locator(SELECTORS.MENU_LIST)).toHaveScreenshot('submenu-expanded.png');
  });

  test('responsive layout - tablet view', async ({ page }) => {
    // Set viewport to tablet size
    await page.setViewportSize(VIEWPORT.TABLET);
    
    // Reload to get proper responsive rendering
    await page.reload();
    await page.waitForSelector(SELECTORS.ROOT);
    
    // Select a user
    await page.selectOption(SELECTORS.USER_SELECT, USERS.ADMIN);
    await page.waitForSelector(SELECTORS.CHOOSE_MENUS);
    
    // Capture tablet view
    await expect(page.locator(SELECTORS.WRAP)).toHaveScreenshot('tablet-view.png');
  });

  test('responsive layout - mobile view', async ({ page }) => {
    // Set viewport to mobile size
    await page.setViewportSize(VIEWPORT.MOBILE);
    
    // Reload to get proper responsive rendering
    await page.reload();
    await page.waitForSelector(SELECTORS.ROOT);
    
    // Select a user
    await page.selectOption(SELECTORS.USER_SELECT, USERS.ADMIN);
    await page.waitForSelector(SELECTORS.CHOOSE_MENUS);
    
    // Capture mobile view
    await expect(page.locator(SELECTORS.WRAP)).toHaveScreenshot('mobile-view.png');
  });
});
