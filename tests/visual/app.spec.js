/**
 * Visual regression tests for User Admin Simplifier
 * These tests capture screenshots and compare them to baseline images
 * to detect unintended visual changes.
 */

const { test, expect } = require('@playwright/test');

test.describe('User Admin Simplifier Visual Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to the test page
    await page.goto('/test-app.html');
    
    // Wait for the React app to load
    await page.waitForSelector('#uas-react-root .wrap');
  });

  test('initial state - no user selected', async ({ page }) => {
    // Capture the initial state before any user is selected
    await expect(page.locator('.wrap')).toHaveScreenshot('initial-state.png');
  });

  test('user selector dropdown', async ({ page }) => {
    // Take a screenshot of the user selector area
    await expect(page.locator('#chooseauser')).toHaveScreenshot('user-selector.png');
  });

  test('user selected - shows full menu interface', async ({ page }) => {
    // Select a user from the dropdown
    await page.selectOption('select[name="uas_user_select"]', 'admin');
    
    // Wait for the menu sections to appear
    await page.waitForSelector('#choosemenus');
    
    // Capture the full interface with menus visible
    await expect(page.locator('.wrap')).toHaveScreenshot('user-selected-full-view.png');
  });

  test('menu list with toggle all button', async ({ page }) => {
    // Select a user
    await page.selectOption('select[name="uas_user_select"]', 'editor');
    await page.waitForSelector('#choosemenus .menu-list');
    
    // Capture just the menu list section
    await expect(page.locator('#choosemenus .menu-list')).toHaveScreenshot('menu-list-section.png');
  });

  test('admin bar toggle and menus', async ({ page }) => {
    // Select a user
    await page.selectOption('select[name="uas_user_select"]', 'editor');
    await page.waitForSelector('.uas-admin-bar-toggle');
    
    // Capture from the admin bar title through the admin bar menus
    const adminBarSection = page.locator('.wrap').locator('xpath=//h3[contains(text(), "admin bar")]/following-sibling::*');
    await expect(adminBarSection.first()).toHaveScreenshot('admin-bar-section.png');
  });

  test('save and reset buttons', async ({ page }) => {
    // Select a user
    await page.selectOption('select[name="uas_user_select"]', 'admin');
    await page.waitForSelector('.uas-buttons');
    
    // Capture the button area
    await expect(page.locator('.uas-buttons')).toHaveScreenshot('save-reset-buttons.png');
  });

  test('menu items with some checked', async ({ page }) => {
    // Select the admin user (which has some items pre-checked in mock data)
    await page.selectOption('select[name="uas_user_select"]', 'admin');
    await page.waitForSelector('#choosemenus');
    
    // Capture just the menu area with checked items
    await expect(page.locator('.menu-list')).toHaveScreenshot('checked-menu-items.png');
  });

  test('expandable submenu - click to expand', async ({ page }) => {
    // Select a user
    await page.selectOption('select[name="uas_user_select"]', 'editor');
    await page.waitForSelector('.submenu-toggle');
    
    // Find and click to expand a menu with submenus
    const toggleButton = page.locator('.submenu-toggle').first();
    await toggleButton.click();
    
    // Wait a bit for any animation
    await page.waitForTimeout(300);
    
    // Capture the expanded state
    await expect(page.locator('.menu-list')).toHaveScreenshot('submenu-expanded.png');
  });

  test('responsive layout - tablet view', async ({ page }) => {
    // Set viewport to tablet size
    await page.setViewportSize({ width: 768, height: 1024 });
    
    // Reload to get proper responsive rendering
    await page.reload();
    await page.waitForSelector('#uas-react-root .wrap');
    
    // Select a user
    await page.selectOption('select[name="uas_user_select"]', 'admin');
    await page.waitForSelector('#choosemenus');
    
    // Capture tablet view
    await expect(page.locator('.wrap')).toHaveScreenshot('tablet-view.png');
  });

  test('responsive layout - mobile view', async ({ page }) => {
    // Set viewport to mobile size
    await page.setViewportSize({ width: 375, height: 667 });
    
    // Reload to get proper responsive rendering
    await page.reload();
    await page.waitForSelector('#uas-react-root .wrap');
    
    // Select a user
    await page.selectOption('select[name="uas_user_select"]', 'admin');
    await page.waitForSelector('#choosemenus');
    
    // Capture mobile view
    await expect(page.locator('.wrap')).toHaveScreenshot('mobile-view.png');
  });
});
