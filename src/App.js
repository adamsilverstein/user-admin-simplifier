import React, { useState, useCallback, useEffect } from 'react';
import UserSelector from './components/UserSelector';
import MenuList from './components/MenuList';
import AdminBarMenu from './components/AdminBarMenu';
import SaveButton from './components/SaveButton';

/**
 * Main App component for User Admin Simplifier
 * Manages the state and coordinates between child components
 */
const App = () => {
  // Get initial data from WordPress
  const {
    users = [],
    menuItems = [],
    adminBarItems = [],
    options = {},
    nonce = '',
    ajaxUrl = '',
    strings = {}
  } = typeof uasData !== 'undefined' ? uasData : {};

  const [selectedUser, setSelectedUser] = useState('');
  const [userOptions, setUserOptions] = useState(options);
  const [isSaving, setIsSaving] = useState(false);
  const [message, setMessage] = useState({ text: '', type: '' });

  // Update user options when selectedUser changes
  useEffect(() => {
    if (selectedUser && !userOptions[selectedUser]) {
      setUserOptions(prev => ({
        ...prev,
        [selectedUser]: {}
      }));
    }
  }, [selectedUser, userOptions]);

  /**
   * Handle user selection change
   */
  const handleUserChange = useCallback((user) => {
    setSelectedUser(user);
    setMessage({ text: '', type: '' });
  }, []);

  /**
   * Handle menu checkbox toggle
   */
  const handleMenuToggle = useCallback((menuId, isChecked) => {
    if (!selectedUser) return;

    setUserOptions(prev => ({
      ...prev,
      [selectedUser]: {
        ...prev[selectedUser],
        [menuId]: isChecked ? 1 : 0
      }
    }));
  }, [selectedUser]);

  /**
   * Save options via AJAX
   */
  const handleSave = useCallback(async () => {
    if (!selectedUser) return;

    setIsSaving(true);
    setMessage({ text: '', type: '' });

    try {
      const formData = new FormData();
      formData.append('action', 'uas_save_options');
      formData.append('nonce', nonce);
      formData.append('user', selectedUser);
      formData.append('options', JSON.stringify(userOptions[selectedUser] || {}));

      const response = await fetch(ajaxUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      });

      const data = await response.json();

      if (data.success) {
        setMessage({ 
          text: strings.saveSuccess || 'Settings saved successfully!', 
          type: 'success' 
        });
      } else {
        setMessage({ 
          text: data.data?.message || strings.saveError || 'Failed to save settings.', 
          type: 'error' 
        });
      }
    } catch (error) {
      setMessage({ 
        text: strings.saveError || 'Failed to save settings.', 
        type: 'error' 
      });
    } finally {
      setIsSaving(false);
    }
  }, [selectedUser, userOptions, nonce, ajaxUrl, strings]);

  /**
   * Reset user options
   */
  const handleReset = useCallback(async () => {
    if (!selectedUser) return;

    setIsSaving(true);
    setMessage({ text: '', type: '' });

    try {
      const formData = new FormData();
      formData.append('action', 'uas_reset_user');
      formData.append('nonce', nonce);
      formData.append('user', selectedUser);

      const response = await fetch(ajaxUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      });

      const data = await response.json();

      if (data.success) {
        setUserOptions(prev => ({
          ...prev,
          [selectedUser]: {}
        }));
        setMessage({ 
          text: strings.resetSuccess || 'User settings reset successfully!', 
          type: 'success' 
        });
      } else {
        setMessage({ 
          text: data.data?.message || strings.resetError || 'Failed to reset settings.', 
          type: 'error' 
        });
      }
    } catch (error) {
      setMessage({ 
        text: strings.resetError || 'Failed to reset settings.', 
        type: 'error' 
      });
    } finally {
      setIsSaving(false);
    }
  }, [selectedUser, nonce, ajaxUrl, strings]);

  const currentUserOptions = selectedUser ? (userOptions[selectedUser] || {}) : {};

  return (
    <div className="wrap">
      <h2>{strings.title || 'User Admin Simplifier'}</h2>
      
      <div className="uas-container" id="chooseauser">
        <h3>{strings.chooseUser || 'Choose a user'}:</h3>
        <UserSelector 
          users={users} 
          selectedUser={selectedUser} 
          onChange={handleUserChange}
          strings={strings}
        />
      </div>

      {selectedUser && (
        <div className="uas-container" id="choosemenus">
          <h3>{strings.disableMenus || 'Disable menus/submenus'}:</h3>
          
          <MenuList 
            menuItems={menuItems}
            userOptions={currentUserOptions}
            onToggle={handleMenuToggle}
            strings={strings}
          />

          <hr />
          
          <h3>{strings.disableAdminBar || 'Disable the admin bar'}:</h3>
          <div className={`menu-item ${Object.keys(currentUserOptions).length % 2 === 0 ? 'menumain' : 'menualternate'}`}>
            <label>
              <input 
                type="checkbox" 
                checked={currentUserOptions['disable-admin-bar'] === 1}
                onChange={(e) => handleMenuToggle('disable-admin-bar', e.target.checked)}
              />
              {strings.disableAdminBarLabel || 'Completely disable the admin bar for this user.'}
            </label>
          </div>

          <h3>{strings.disableAdminBarMenus || 'Disable admin bar menus/submenus'}:</h3>
          
          <AdminBarMenu 
            adminBarItems={adminBarItems}
            userOptions={currentUserOptions}
            onToggle={handleMenuToggle}
            strings={strings}
          />

          {message.text && (
            <div className={`uas-message ${message.type}`}>
              {message.text}
            </div>
          )}

          <SaveButton 
            onSave={handleSave}
            onReset={handleReset}
            isSaving={isSaving}
            strings={strings}
          />
        </div>
      )}
    </div>
  );
};

export default App;
