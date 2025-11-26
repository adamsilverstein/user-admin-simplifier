import React, { useState } from 'react';

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
          <span 
            className={`submenu-toggle ${isExpanded ? 'uas-selected' : 'uas-unselected'}`}
            onClick={toggleChildren}
          >
            <a href="#" onClick={(e) => e.preventDefault()}>
              {isExpanded 
                ? (strings.hideSubmenus || 'Hide submenus') 
                : (strings.showSubmenus || 'Show submenus')}
            </a>
          </span>
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
  return (
    <div className="admin-bar-menu-list">
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
