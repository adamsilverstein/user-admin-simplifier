const { test, expect } = require('@playwright/test');
const { loginToWordPress, navigateToPlugin, createTestUser, deleteTestUser, getMenuItems, isMenuVisible } = require('./utils/wordpress');

/**
 * Test suite for actual menu visibility based on settings
 * This tests that menus are actually hidden for users when configured
 */
test.describe('Menu Visibility', () => {
  let testUser;

  test.beforeAll(async ({ browser }) => {
    const page = await browser.newPage();
    await loginToWordPress(page);
    
    // Create a test user
    testUser = await createTestUser(page, {
      username: 'visibilitytest',
      email: 'visibilitytest@example.com',
      role: 'administrator',
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

  test('should show all menus before any restrictions', async ({ page, context }) => {
    // Login as test user
    await loginToWordPress(page, {
      username: testUser.username,
      password: testUser.password,
    });
    
    // Navigate to dashboard
    await page.goto('/wp-admin/');
    
    // Wait for menu to load
    await page.waitForSelector('#adminmenu', { timeout: 5000 });
    
    // Get menu items
    const menuItems = await getMenuItems(page);
    
    // Verify that common menus exist
    expect(menuItems.length).toBeGreaterThan(5);
  });

  test('should hide configured menus for restricted user', async ({ page, context }) => {
    // First, login as admin and configure restrictions
    await loginToWordPress(page);
    await navigateToPlugin(page);
    
    // Wait for React to render
    await page.waitForTimeout(1000);
    
    // Select test user
    const userSelector = page.locator('select').first();
    await userSelector.selectOption(testUser.username);
    
    // Wait for menu list to load
    await page.waitForTimeout(500);
    
    // Find a menu item to hide (e.g., Posts, Media, etc.)
    // We'll try to find a checkbox with a label containing common menu names
    const checkboxes = page.locator('input[type="checkbox"]');
    const labels = page.locator('label');
    
    // Try to find and check a menu item
    let menuToHide = null;
    const labelsText = await labels.allTextContents();
    
    for (let i = 0; i < labelsText.length; i++) {
      const text = labelsText[i].trim();
      if (text === 'Posts' || text === 'Media' || text === 'Comments') {
        menuToHide = text;
        // Find associated checkbox and check it
        const checkbox = page.locator(`label:has-text("${text}")`).locator('..').locator('input[type="checkbox"]').first();
        
        if (await checkbox.count() > 0) {
          const isChecked = await checkbox.isChecked();
          if (!isChecked) {
            await checkbox.click();
          }
          break;
        }
      }
    }
    
    // If we found and checked a menu, save it
    if (menuToHide) {
      const saveButton = page.locator('button:has-text("Save")');
      await saveButton.click();
      
      // Wait for save to complete
      await page.waitForTimeout(1500);
      
      // Logout admin
      await page.goto('/wp-login.php?action=logout');
      await page.locator('a:has-text("log out")').click();
      
      // Login as test user
      await page.goto('/wp-login.php');
      await page.fill('#user_login', testUser.username);
      await page.fill('#user_pass', testUser.password);
      await page.click('#wp-submit');
      
      // Wait for admin dashboard
      await page.waitForURL(/wp-admin/, { timeout: 10000 });
      await page.waitForSelector('#adminmenu', { timeout: 5000 });
      
      // Check if the menu is hidden
      const isVisible = await isMenuVisible(page, menuToHide);
      
      // The menu should not be visible
      expect(isVisible).toBe(false);
    }
  });

  test('should show menus again after resetting settings', async ({ page }) => {
    // Login as admin
    await loginToWordPress(page);
    await navigateToPlugin(page);
    
    // Wait for React to render
    await page.waitForTimeout(1000);
    
    // Select test user
    const userSelector = page.locator('select').first();
    await userSelector.selectOption(testUser.username);
    
    // Wait for menu list to load
    await page.waitForTimeout(500);
    
    // Click reset button
    const resetButton = page.locator('button:has-text("Reset")');
    await resetButton.click();
    
    // Wait for reset to complete
    await page.waitForTimeout(1500);
    
    // Logout admin
    await page.goto('/wp-login.php?action=logout');
    await page.locator('a:has-text("log out")').click();
    
    // Login as test user
    await page.goto('/wp-login.php');
    await page.fill('#user_login', testUser.username);
    await page.fill('#user_pass', testUser.password);
    await page.click('#wp-submit');
    
    // Wait for admin dashboard
    await page.waitForURL(/wp-admin/, { timeout: 10000 });
    await page.waitForSelector('#adminmenu', { timeout: 5000 });
    
    // Get menu items
    const menuItems = await getMenuItems(page);
    
    // Should have menus visible again
    expect(menuItems.length).toBeGreaterThan(5);
  });
});
