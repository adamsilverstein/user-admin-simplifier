/**
 * WordPress utility functions for e2e tests
 */

/**
 * Default admin credentials for WordPress test environment
 */
export const ADMIN_USER = {
  username: 'admin',
  password: 'password',
};

/**
 * Login to WordPress admin
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {Object} credentials - User credentials
 */
export async function loginToWordPress(page, credentials = ADMIN_USER) {
  await page.goto('/wp-login.php', { 
    waitUntil: 'networkidle',
    timeout: 30000 
  });
  
  // Wait for login form to be visible
  await page.waitForSelector('#user_login', { visible: true, timeout: 15000 });
  
  // Fill login form
  await page.fill('#user_login', credentials.username);
  await page.fill('#user_pass', credentials.password);
  
  // Click login button
  await page.click('#wp-submit');
  
  // Wait for navigation to complete
  await page.waitForURL(/wp-admin/, { timeout: 30000 });
  await page.waitForLoadState('networkidle');
}

/**
 * Navigate to User Admin Simplifier plugin page
 * @param {import('@playwright/test').Page} page - Playwright page object
 */
export async function navigateToPlugin(page) {
  await page.goto('/wp-admin/admin.php?page=useradminsimplifier/useradminsimplifier.php', {
    waitUntil: 'networkidle',
    timeout: 30000
  });
  
  // Wait for React app to load
  await page.waitForSelector('#uas-react-root', { visible: true, timeout: 15000 });
}

/**
 * Create a test user
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {Object} userData - User data
 */
export async function createTestUser(page, userData = {}) {
  const defaultUser = {
    username: 'testuser',
    email: 'testuser@example.com',
    firstName: 'Test',
    lastName: 'User',
    password: 'testpassword123',
    role: 'administrator',
  };
  
  const user = { ...defaultUser, ...userData };
  
  await page.goto('/wp-admin/user-new.php', {
    waitUntil: 'networkidle',
    timeout: 30000
  });
  
  // Wait for form to be ready
  await page.waitForSelector('#user_login', { visible: true, timeout: 15000 });
  
  // Fill user form
  await page.fill('#user_login', user.username);
  await page.fill('#email', user.email);
  await page.fill('#first_name', user.firstName);
  await page.fill('#last_name', user.lastName);
  await page.fill('#pass1', user.password);
  await page.fill('#pass2', user.password);
  
  // Select role
  await page.selectOption('#role', user.role);
  
  // Submit form
  await page.click('#createusersub');
  
  // Wait for redirect or success message
  try {
    await page.waitForSelector('.notice-success', { timeout: 10000 });
  } catch {
    // If we don't see success message, check if we got redirected to users page
    await page.waitForURL(/wp-admin\/users\.php/, { timeout: 10000 });
  }
  
  return user;
}

/**
 * Delete a test user
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {string} username - Username to delete
 */
export async function deleteTestUser(page, username) {
  await page.goto('/wp-admin/users.php', {
    waitUntil: 'networkidle',
    timeout: 30000
  });
  
  // Wait for the page to load
  await page.waitForSelector('table.wp-list-table', { timeout: 15000 });
  
  // Find user row
  const userRow = page.locator(`tr:has-text("${username}")`);
  
  const count = await userRow.count();
  if (count > 0) {
    // Hover over the row to reveal action links
    await userRow.first().hover();
    
    // Click delete link
    const deleteLink = userRow.first().locator('a:has-text("Delete")');
    await deleteLink.click();
    
    // Confirm deletion
    await page.click('#submit');
    
    // Wait for success message or redirect
    try {
      await page.waitForSelector('.notice-success', { timeout: 10000 });
    } catch {
      // User was deleted but no success message - that's ok
      await page.waitForLoadState('networkidle');
    }
  }
}

/**
 * Get all WordPress menu items from the page
 * @param {import('@playwright/test').Page} page - Playwright page object
 */
export async function getMenuItems(page) {
  // Wait for menu to be visible
  await page.waitForSelector('#adminmenu', { timeout: 5000 });
  
  const menuItems = await page.locator('#adminmenu .menu-top').allTextContents();
  
  return menuItems.map(item => item.trim()).filter(item => item.length > 0);
}

/**
 * Check if a menu item is visible
 * @param {import('@playwright/test').Page} page - Playwright page object
 * @param {string} menuName - Name of the menu item
 */
export async function isMenuVisible(page, menuName) {
  const menuItem = page.locator(`#adminmenu .menu-top:has-text("${menuName}")`);
  return await menuItem.isVisible();
}
