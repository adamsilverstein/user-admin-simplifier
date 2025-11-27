import React, { useState, useMemo } from 'react';

/**
 * MenuItem component
 * Renders a single menu item with optional submenus
 */
const MenuItem = ({ item, userOptions, onToggle, rowIndex, strings }) => {
  const [isExpanded, setIsExpanded] = useState(false);
  const menuId = item.id;
  const isChecked = userOptions[menuId] === 1;
  const hasSubmenus = item.submenus && item.submenus.length > 0;

  const handleToggle = (e) => {
    onToggle(menuId, e.target.checked);
  };

  const toggleSubmenu = () => {
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
          {item.name}
        </label>
        {hasSubmenus && (
          <button
            type="button"
            className={`submenu-toggle ${isExpanded ? 'uas-selected' : 'uas-unselected'}`}
            onClick={toggleSubmenu}
            aria-expanded={isExpanded}
          >
            {isExpanded 
              ? (strings.hideSubmenus || 'Hide submenus') 
              : (strings.showSubmenus || 'Show submenus')}
          </button>
        )}
      </p>
      {hasSubmenus && isExpanded && (
        <div className="submenuinner">
          {item.submenus.map((submenu, subIndex) => {
            const submenuId = submenu.id;
            const isSubmenuChecked = userOptions[submenuId] === 1;
            const subRowClass = subIndex % 2 === 0 ? 'submain' : 'subalternate';
            
            return (
              <p key={submenuId} className={subRowClass}>
                <label>
                  <input 
                    type="checkbox" 
                    checked={isSubmenuChecked}
                    onChange={(e) => onToggle(submenuId, e.target.checked)}
                  />
                  {submenu.name}
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
 * MenuList component
 * Renders the list of WordPress admin menu items
 */
const MenuList = ({ menuItems, userOptions, onToggle, strings }) => {
  // Calculate if all menus are disabled
  const allMenuIds = useMemo(() => {
    const ids = [];
    menuItems.forEach(item => {
      ids.push(item.id);
      if (item.submenus) {
        item.submenus.forEach(submenu => ids.push(submenu.id));
      }
    });
    return ids;
  }, [menuItems]);

  const allDisabled = useMemo(() => {
    return allMenuIds.length > 0 && allMenuIds.every(id => userOptions[id] === 1);
  }, [allMenuIds, userOptions]);

  const handleToggleAll = () => {
    const newValue = allDisabled ? false : true;
    allMenuIds.forEach(id => onToggle(id, newValue));
  };

  return (
    <div className="menu-list">
      <div className="toggle-all-container">
        <button 
          type="button" 
          className="button button-secondary toggle-all-btn"
          onClick={handleToggleAll}
        >
          {allDisabled 
            ? (strings.enableAllMenus || 'Enable all menus') 
            : (strings.disableAllMenus || 'Disable all menus')}
        </button>
      </div>
      {menuItems.map((item, index) => (
        <MenuItem 
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

export default MenuList;
