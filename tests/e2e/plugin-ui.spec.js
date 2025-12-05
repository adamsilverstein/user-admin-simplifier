const { test, expect } = require('@playwright/test');
const { loginToWordPress, navigateToPlugin, ADMIN_USER } = require('./utils/wordpress');

/**
 * Test suite for User Admin Simplifier plugin UI
 */
test.describe('Plugin UI', () => {
  test.beforeEach(async ({ page }) => {
    await loginToWordPress(page);
  });

  test('should load plugin admin page', async ({ page }) => {
    await navigateToPlugin(page);
    
    // Check that React root is present
    await expect(page.locator('#uas-react-root')).toBeVisible();
    
    // Check that main title is present
    await expect(page.locator('h1')).toContainText('User Admin Simplifier');
  });

  test('should display user selector', async ({ page }) => {
    await navigateToPlugin(page);
    
    // Wait for React to render
    await page.waitForTimeout(1000);
    
    // Check for user selector dropdown
    const userSelector = page.locator('select, [role="combobox"]').first();
    await expect(userSelector).toBeVisible();
  });

  test('should display menu list when user is selected', async ({ page }) => {
    await navigateToPlugin(page);
    
    // Wait for React to render
    await page.waitForTimeout(1000);
    
    // Select first available user
    const userSelector = page.locator('select').first();
    await userSelector.selectOption({ index: 1 });
    
    // Wait for menu list to load
    await page.waitForTimeout(500);
    
    // Check that menu checkboxes are visible
    const checkboxes = page.locator('input[type="checkbox"]');
    const count = await checkboxes.count();
    
    expect(count).toBeGreaterThan(0);
  });

  test('should display Save and Reset buttons when user is selected', async ({ page }) => {
    await navigateToPlugin(page);
    
    // Wait for React to render
    await page.waitForTimeout(1000);
    
    // Select first available user
    const userSelector = page.locator('select').first();
    await userSelector.selectOption({ index: 1 });
    
    // Wait for buttons to appear
    await page.waitForTimeout(500);
    
    // Check for Save button
    const saveButton = page.locator('button:has-text("Save")');
    await expect(saveButton).toBeVisible();
    
    // Check for Reset button
    const resetButton = page.locator('button:has-text("Reset")');
    await expect(resetButton).toBeVisible();
  });

  test('should show admin bar options section', async ({ page }) => {
    await navigateToPlugin(page);
    
    // Wait for React to render
    await page.waitForTimeout(1000);
    
    // Select first available user
    const userSelector = page.locator('select').first();
    await userSelector.selectOption({ index: 1 });
    
    // Wait for content to load
    await page.waitForTimeout(500);
    
    // Check for admin bar section
    const adminBarSection = page.locator('text=/admin bar/i');
    await expect(adminBarSection.first()).toBeVisible();
  });
});
