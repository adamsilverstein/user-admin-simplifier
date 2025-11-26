import React, { useState } from 'react';

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
          <span 
            className={`submenu-toggle ${isExpanded ? 'uas-selected' : 'uas-unselected'}`}
            onClick={toggleSubmenu}
          >
            <a href="#" onClick={(e) => e.preventDefault()}>
              {isExpanded 
                ? (strings.hideSubmenus || 'Hide submenus') 
                : (strings.showSubmenus || 'Show submenus')}
            </a>
          </span>
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
  return (
    <div className="menu-list">
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
