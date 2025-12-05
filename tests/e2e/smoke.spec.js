/**
 * Smoke tests - Quick tests to verify basic functionality
 * These tests run fast and catch obvious issues
 */

const { test, expect } = require('@playwright/test');
const { loginToWordPress, ADMIN_USER } = require('./utils/wordpress');

test.describe('Smoke Tests', () => {
  test('WordPress is accessible', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveTitle(/.*WordPress.*/i);
  });

  test('WordPress admin is accessible', async ({ page }) => {
    await page.goto('/wp-admin');
    // Should redirect to login
    await expect(page).toHaveURL(/wp-login/);
  });

  test('can login to WordPress', async ({ page }) => {
    await loginToWordPress(page);
    
    // Should be on admin dashboard
    await expect(page).toHaveURL(/wp-admin/);
    await expect(page.locator('#wpadminbar')).toBeVisible();
  });

  test('plugin is installed and accessible', async ({ page }) => {
    await loginToWordPress(page);
    
    // Navigate to plugins page
    await page.goto('/wp-admin/plugins.php');
    
    // Look for User Admin Simplifier plugin
    const pluginRow = page.locator('tr[data-slug="user-admin-simplifier"], tr:has-text("User Admin Simplifier")');
    await expect(pluginRow).toBeVisible();
  });

  test('plugin admin page loads', async ({ page }) => {
    await loginToWordPress(page);
    
    // Navigate to plugin settings
    await page.goto('/wp-admin/admin.php?page=useradminsimplifier/useradminsimplifier.php');
    
    // Check React root loads
    await expect(page.locator('#uas-react-root')).toBeVisible({ timeout: 10000 });
    
    // Check for plugin title
    await expect(page.locator('h1')).toContainText('User Admin Simplifier');
  });

  test('React app renders without errors', async ({ page }) => {
    const consoleErrors = [];
    
    // Listen for console errors
    page.on('console', msg => {
      if (msg.type() === 'error') {
        consoleErrors.push(msg.text());
      }
    });
    
    await loginToWordPress(page);
    await page.goto('/wp-admin/admin.php?page=useradminsimplifier/useradminsimplifier.php');
    
    // Wait for React to render
    await page.waitForTimeout(2000);
    
    // Check there are no React errors
    expect(consoleErrors).toEqual([]);
  });

  test('basic UI elements are present', async ({ page }) => {
    await loginToWordPress(page);
    await page.goto('/wp-admin/admin.php?page=useradminsimplifier/useradminsimplifier.php');
    
    // Wait for React to render
    await page.waitForTimeout(1000);
    
    // Check for user selector
    await expect(page.locator('select, [role="combobox"]').first()).toBeVisible();
    
    // Select a user to reveal more UI
    const userSelector = page.locator('select').first();
    const optionCount = await userSelector.locator('option').count();
    
    if (optionCount > 1) {
      await userSelector.selectOption({ index: 1 });
      await page.waitForTimeout(500);
      
      // Check for save/reset buttons
      await expect(page.locator('button:has-text("Save"), button:has-text("Reset")')).toHaveCount(2);
    }
  });
});
