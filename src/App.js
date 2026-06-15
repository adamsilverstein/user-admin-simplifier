import React, { useState, useCallback, useEffect } from 'react';
import UserSelector from './components/UserSelector';
import MenuList from './components/MenuList';
import AdminBarMenu from './components/AdminBarMenu';
import SaveButton from './components/SaveButton';
import ModeSelector from './components/ModeSelector';
import RoleSelector from './components/RoleSelector';

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
    strings = {},
    roles = [],
    roleOptions = {},
    mode: initialMode = 'per-user'
  } = typeof uasData !== 'undefined' ? uasData : {};

  const [selectedUser, setSelectedUser] = useState('');
  const [userOptions, setUserOptions] = useState(options);
  const [isSaving, setIsSaving] = useState(false);
  const [message, setMessage] = useState({ text: '', type: '' });
  const [mode, setMode] = useState(initialMode);
  const [selectedRole, setSelectedRole] = useState('');
  const [roleOpts, setRoleOpts] = useState(roleOptions);

  // Update user options when selectedUser changes
  useEffect(() => {
    if (selectedUser) {
      setUserOptions(prev => {
        if (prev[selectedUser]) return prev;
        return {
          ...prev,
          [selectedUser]: {}
        };
      });
    }
  }, [selectedUser]);

  /**
   * Shared AJAX helper.
   */
  const postAjax = useCallback(async (fields) => {
    const formData = new FormData();
    formData.append('nonce', nonce);
    Object.entries(fields).forEach(([k, v]) => formData.append(k, v));
    const response = await fetch(ajaxUrl, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
    });
    if (!response.ok) throw new Error(`HTTP error: ${response.status}`);
    return response.json();
  }, [nonce, ajaxUrl]);

  /**
   * Handle mode change (persists immediately).
   */
  const handleModeChange = useCallback(async (newMode) => {
    const prevMode = mode;
    setMode(newMode);
    setMessage({ text: '', type: '' });
    try {
      const data = await postAjax({ action: 'uas_save_mode', mode: newMode });
      if (data.success) {
        setMessage({ text: strings.modeSaved || 'Mode saved.', type: 'success' });
      } else {
        setMode(prevMode);
        setMessage({ text: data.data?.message || strings.saveError || 'Failed to save settings.', type: 'error' });
      }
    } catch (e) {
      setMode(prevMode);
      setMessage({ text: strings.saveError || 'Failed to save settings.', type: 'error' });
    }
  }, [mode, postAjax, strings]);

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
   * Handle menu reordering
   * Stores the full top-level menu order for the selected user.
   */
  const handleMenuReorder = useCallback((menuOrder) => {
    if (!selectedUser) return;

    setUserOptions(prev => ({
      ...prev,
      [selectedUser]: {
        ...prev[selectedUser],
        'menu-order': menuOrder
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

      if (!response.ok) {
        throw new Error(`HTTP error: ${response.status}`);
      }

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

      if (!response.ok) {
        throw new Error(`HTTP error: ${response.status}`);
      }

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

  const currentRoleOptions = selectedRole ? (roleOpts[selectedRole] || {}) : {};

  /**
   * Handle role menu toggle.
   */
  const handleRoleToggle = useCallback((menuId, isChecked) => {
    if (!selectedRole) return;
    setRoleOpts(prev => ({
      ...prev,
      [selectedRole]: { ...prev[selectedRole], [menuId]: isChecked ? 1 : 0 },
    }));
  }, [selectedRole]);

  /**
   * Handle role menu reordering.
   */
  const handleRoleReorder = useCallback((menuOrder) => {
    if (!selectedRole) return;
    setRoleOpts(prev => ({
      ...prev,
      [selectedRole]: { ...prev[selectedRole], 'menu-order': menuOrder },
    }));
  }, [selectedRole]);

  /**
   * Save role options via AJAX.
   */
  const handleRoleSave = useCallback(async () => {
    if (!selectedRole) return;
    setIsSaving(true);
    setMessage({ text: '', type: '' });
    try {
      const data = await postAjax({
        action: 'uas_save_role',
        role: selectedRole,
        options: JSON.stringify(roleOpts[selectedRole] || {}),
      });
      setMessage(data.success
        ? { text: strings.saveSuccess || 'Settings saved successfully!', type: 'success' }
        : { text: data.data?.message || strings.saveError || 'Failed to save settings.', type: 'error' });
    } catch (e) {
      setMessage({ text: strings.saveError || 'Failed to save settings.', type: 'error' });
    } finally {
      setIsSaving(false);
    }
  }, [selectedRole, roleOpts, postAjax, strings]);

  /**
   * Reset role options via AJAX.
   */
  const handleRoleReset = useCallback(async () => {
    if (!selectedRole) return;
    setIsSaving(true);
    setMessage({ text: '', type: '' });
    try {
      const data = await postAjax({ action: 'uas_reset_role', role: selectedRole });
      if (data.success) {
        setRoleOpts(prev => ({ ...prev, [selectedRole]: {} }));
        setMessage({ text: strings.resetSuccess || 'Settings reset!', type: 'success' });
      }
    } catch (e) {
      setMessage({ text: strings.resetError || 'Failed to reset.', type: 'error' });
    } finally {
      setIsSaving(false);
    }
  }, [selectedRole, postAjax, strings]);

  /**
   * Handle per-user override tri-state toggle.
   */
  const handleUserTriToggle = useCallback((menuId, value) => {
    if (!selectedUser) return;
    setUserOptions(prev => {
      const next = { ...(prev[selectedUser] || {}) };
      if (value === 'inherit') {
        delete next[menuId];
      } else {
        next[menuId] = value === 'hide' ? 1 : 0;
      }
      return { ...prev, [selectedUser]: next };
    });
  }, [selectedUser]);

  const currentUserOptions = selectedUser ? (userOptions[selectedUser] || {}) : {};

  const showUserEditor = mode === 'per-user' || mode === 'role-with-overrides';
  const showRoleEditor = mode === 'role' || mode === 'role-with-overrides';
  const overrideMode = mode === 'role-with-overrides';

  return (
    <div className="wrap">
      <h2>{strings.title || 'User Admin Simplifier'}</h2>

      <ModeSelector mode={mode} onChange={handleModeChange} strings={strings} />

      {showRoleEditor && (
        <div className="uas-container" id="chooserole">
          <h3>{strings.editingRole || 'Editing role defaults'}:</h3>
          <RoleSelector
            roles={roles}
            selectedRole={selectedRole}
            onChange={(r) => { setSelectedRole(r); setMessage({ text: '', type: '' }); }}
            strings={strings}
          />
          {selectedRole && (
            <div className="uas-container" id="rolemenus">
              <h3>{strings.disableMenus || 'Disable menus/submenus'}:</h3>
              <MenuList
                menuItems={menuItems}
                userOptions={currentRoleOptions}
                onToggle={handleRoleToggle}
                onReorder={handleRoleReorder}
                strings={strings}
              />
              <hr />
              <h3>{strings.disableAdminBar || 'Disable the admin bar'}:</h3>
              <div className="menu-item uas-admin-bar-toggle">
                <label>
                  <input
                    type="checkbox"
                    checked={currentRoleOptions['disable-admin-bar'] === 1}
                    onChange={(e) => handleRoleToggle('disable-admin-bar', e.target.checked)}
                  />
                  {strings.disableAdminBarLabel || 'Completely disable the admin bar for this user.'}
                </label>
              </div>
              <h3>{strings.disableAdminBarMenus || 'Disable admin bar menus/submenus'}:</h3>
              <AdminBarMenu
                adminBarItems={adminBarItems}
                userOptions={currentRoleOptions}
                onToggle={handleRoleToggle}
                strings={strings}
              />
              <SaveButton
                onSave={handleRoleSave}
                onReset={handleRoleReset}
                isSaving={isSaving}
                strings={{ ...strings, saveChanges: strings.saveRole || strings.saveChanges, resetUser: strings.resetRole || strings.resetUser }}
              />
            </div>
          )}
        </div>
      )}

      {showUserEditor && (
        <div className="uas-container" id="chooseauser">
          <h3>{strings.chooseUser || 'Choose a user'}:</h3>
          <UserSelector
            users={users}
            selectedUser={selectedUser}
            onChange={handleUserChange}
            strings={strings}
          />
        </div>
      )}

      {showUserEditor && selectedUser && (
        <div className="uas-container" id="choosemenus">
          <h3>{strings.disableMenus || 'Disable menus/submenus'}:</h3>

          <MenuList
            menuItems={menuItems}
            userOptions={currentUserOptions}
            onToggle={handleMenuToggle}
            onTriToggle={handleUserTriToggle}
            onReorder={handleMenuReorder}
            triState={overrideMode}
            strings={strings}
          />

          <hr />

          <h3>{strings.disableAdminBar || 'Disable the admin bar'}:</h3>
          <div className="menu-item uas-admin-bar-toggle">
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
            onTriToggle={handleUserTriToggle}
            triState={overrideMode}
            strings={strings}
          />

          <SaveButton
            onSave={handleSave}
            onReset={handleReset}
            isSaving={isSaving}
            strings={strings}
          />
        </div>
      )}

      {message.text && (
        <div className={`uas-message ${message.type}`}>
          {message.text}
        </div>
      )}
    </div>
  );
};

export default App;
