import React, { useState, useMemo } from 'react';

/**
 * AdminBarMenuItem component
 * Renders a single admin bar menu item with optional children
 */
const AdminBarMenuItem = ({ item, userOptions, onToggle, rowIndex, strings }) => {
  const [isExpanded, setIsExpanded] = useState(false);
  const menuId = item.id;
  const isChecked = userOptions[menuId] === 1;
  const hasChildren = item.children && item.children.length > 0;

  const handleToggle = (e) => {
    onToggle(menuId, e.target.checked);
  };

  const toggleChildren = () => {
    setIsExpanded(!isExpanded);
  };

  const rowClass = rowIndex % 2 === 0 ? 'menumain' : 'menualternate';

  return (
    <>
      <p className={rowClass}>
        <label>
          <input 
            type="checkbox" 
            checked={isChecked}
            onChange={handleToggle}
          />
          {item.title}
        </label>
        {hasChildren && (
          <button
            type="button"
            className={`submenu-toggle ${isExpanded ? 'uas-selected' : 'uas-unselected'}`}
            onClick={toggleChildren}
            aria-expanded={isExpanded}
          >
            {isExpanded 
              ? (strings.hideSubmenus || 'Hide submenus') 
              : (strings.showSubmenus || 'Show submenus')}
          </button>
        )}
      </p>
      {hasChildren && isExpanded && (
        <div className="submenuinner">
          {item.children.map((child, subIndex) => {
            const childId = child.id;
            const isChildChecked = userOptions[childId] === 1;
            const subRowClass = subIndex % 2 === 0 ? 'submain' : 'subalternate';
            
            return (
              <p key={childId} className={subRowClass}>
                <label>
                  <input 
                    type="checkbox" 
                    checked={isChildChecked}
                    onChange={(e) => onToggle(childId, e.target.checked)}
                  />
                  {child.title}
                </label>
              </p>
            );
          })}
        </div>
      )}
    </>
  );
};

/**
 * AdminBarMenu component
 * Renders the list of WordPress admin bar menu items
 */
const AdminBarMenu = ({ adminBarItems, userOptions, onToggle, strings }) => {
  // Calculate if all admin bar items are disabled
  const allItemIds = useMemo(() => {
    const ids = [];
    adminBarItems.forEach(item => {
      ids.push(item.id);
      if (item.children) {
        item.children.forEach(child => ids.push(child.id));
      }
    });
    return ids;
  }, [adminBarItems]);

  const allDisabled = useMemo(() => {
    return allItemIds.length > 0 && allItemIds.every(id => userOptions[id] === 1);
  }, [allItemIds, userOptions]);

  const handleToggleAll = () => {
    const newValue = allDisabled ? false : true;
    allItemIds.forEach(id => onToggle(id, newValue));
  };

  return (
    <div className="admin-bar-menu-list">
      <div className="toggle-all-container">
        <button 
          type="button" 
          className="button button-secondary toggle-all-btn"
          onClick={handleToggleAll}
        >
          {allDisabled 
            ? (strings.enableAllAdminBar || 'Enable all admin bar items') 
            : (strings.disableAllAdminBar || 'Disable all admin bar items')}
        </button>
      </div>
      {adminBarItems.map((item, index) => (
        <AdminBarMenuItem 
          key={item.id}
          item={item}
          userOptions={userOptions}
          onToggle={onToggle}
          rowIndex={index}
          strings={strings}
        />
      ))}
    </div>
  );
};

export default AdminBarMenu;
