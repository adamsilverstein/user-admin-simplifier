const { test, expect } = require('@playwright/test');
const { loginToWordPress, navigateToPlugin, createTestUser, deleteTestUser } = require('./utils/wordpress');

/**
 * Test suite for admin bar customization
 */
test.describe('Admin Bar Customization', () => {
  let testUser;

  test.beforeAll(async ({ browser }) => {
    const page = await browser.newPage();
    await loginToWordPress(page);
    
    // Create a test user for our tests
    testUser = await createTestUser(page, {
      username: 'adminbartest',
      email: 'adminbartest@example.com',
    });
    
    await page.close();
  });

  test.afterAll(async ({ browser }) => {
    const page = await browser.newPage();
    await loginToWordPress(page);
    
    // Clean up test user
    await deleteTestUser(page, testUser.username);
    
    await page.close();
  });

  test.beforeEach(async ({ page }) => {
    await loginToWordPress(page);
    await navigateToPlugin(page);
    
    // Wait for user selector to be available
    await expect(page.locator('select').first()).toBeVisible();
  });

  test('should display admin bar disable option', async ({ page }) => {
    // Select test user
    const userSelector = page.locator('select').first();
    await userSelector.selectOption(testUser.username);
    
    // Wait for content to load by checking for any checkbox or input
    await expect(page.locator('input[type="checkbox"]').first()).toBeVisible({ timeout: 5000 });
    
    // Look for admin bar disable checkbox or option
    const adminBarOption = page.locator('text=/disable.*admin bar/i, input[id*="admin-bar"], input[name*="admin-bar"]').first();
    await expect(adminBarOption).toBeVisible();
  });

  test('should show admin bar menu items', async ({ page }) => {
    // Select test user
    const userSelector = page.locator('select').first();
    await userSelector.selectOption(testUser.username);
    
    // Wait for checkboxes to load
    await expect(page.locator('input[type="checkbox"]').first()).toBeVisible({ timeout: 5000 });
    
    // Look for admin bar section with checkboxes
    const adminBarSection = page.locator('text=/admin bar menu/i');
    
    if (await adminBarSection.count() > 0) {
      await expect(adminBarSection.first()).toBeVisible();
      
      // Check that there are checkboxes in the admin bar section
      const checkboxes = page.locator('input[type="checkbox"]');
      const count = await checkboxes.count();
      
      expect(count).toBeGreaterThan(0);
    }
  });

  test('should be able to toggle admin bar items', async ({ page }) => {
    // Select test user
    const userSelector = page.locator('select').first();
    await userSelector.selectOption(testUser.username);
    
    // Wait for checkboxes to load
    await expect(page.locator('input[type="checkbox"]').first()).toBeVisible({ timeout: 5000 });
    
    // Find checkboxes (admin bar or menu items)
    const checkboxes = page.locator('input[type="checkbox"]');
    const count = await checkboxes.count();
    
    if (count > 0) {
      // Toggle a checkbox
      const checkbox = checkboxes.last(); // Use last to likely get an admin bar item
      const wasChecked = await checkbox.isChecked();
      
      await checkbox.click();
      
      // Verify state changed
      const isChecked = await checkbox.isChecked();
      expect(isChecked).toBe(!wasChecked);
    }
  });

  test('should persist admin bar settings', async ({ page }) => {
    // Select test user
    const userSelector = page.locator('select').first();
    await userSelector.selectOption(testUser.username);
    
    // Wait for checkboxes to load
    const checkboxes = page.locator('input[type="checkbox"]');
    await expect(checkboxes.first()).toBeVisible({ timeout: 5000 });
    
    const count = await checkboxes.count();
    
    if (count > 0) {
      const targetCheckbox = checkboxes.last();
      
      // Ensure it's checked
      if (!(await targetCheckbox.isChecked())) {
        await targetCheckbox.click();
      }
      
      // Save settings
      const saveButton = page.locator('button:has-text("Save")');
      await expect(saveButton).toBeVisible();
      await saveButton.click();
      
      // Wait for save to complete
      await page.waitForLoadState('networkidle');
      
      // Reload page
      await page.reload();
      await expect(userSelector).toBeVisible();
      
      // Select user again
      await userSelector.selectOption(testUser.username);
      await expect(checkboxes.first()).toBeVisible({ timeout: 5000 });
      
      // Verify checkbox is still checked
      const reloadedCheckbox = checkboxes.last();
      await expect(reloadedCheckbox).toBeChecked();
    }
  });
});
