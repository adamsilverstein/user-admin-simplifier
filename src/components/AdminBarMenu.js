import React, { useState, useMemo } from 'react';

/**
 * TriStateControl
 * Renders Inherit / Show / Hide radios for override mode. `value` is one of
 * 'inherit' | 'show' | 'hide'. onChange receives the new value.
 */
const TriStateControl = ({ id, value, onChange, strings }) => (
  <span className="uas-tristate" role="radiogroup">
    {['inherit', 'show', 'hide'].map((opt) => (
      <label key={opt} className="uas-tristate-option">
        <input
          type="radio"
          name={`tristate-${id}`}
          checked={value === opt}
          onChange={() => onChange(opt)}
        />
        {opt === 'inherit'
          ? (strings.inherit || 'Inherit')
          : opt === 'show'
          ? (strings.show || 'Show')
          : (strings.hide || 'Hide')}
      </label>
    ))}
  </span>
);

/**
 * AdminBarMenuItem component
 * Renders a single admin bar menu item with optional children
 */
const AdminBarMenuItem = ({ item, userOptions, onToggle, rowIndex, triState, onTriToggle, strings }) => {
  const [isExpanded, setIsExpanded] = useState(false);
  const menuId = item.id;
  const isChecked = userOptions[menuId] === 1;
  const triValue =
    userOptions[menuId] === 1 ? 'hide' : userOptions[menuId] === 0 ? 'show' : 'inherit';
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
        {triState ? (
          <span className="uas-tristate-row">
            <span className="uas-tristate-name">{item.title}</span>
            <TriStateControl
              id={menuId}
              value={triValue}
              onChange={(v) => onTriToggle(menuId, v)}
              strings={strings}
            />
          </span>
        ) : (
          <label>
            <input
              type="checkbox"
              checked={isChecked}
              onChange={handleToggle}
            />
            {item.title}
          </label>
        )}
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
            const childTriValue =
              userOptions[childId] === 1 ? 'hide' : userOptions[childId] === 0 ? 'show' : 'inherit';
            const subRowClass = subIndex % 2 === 0 ? 'submain' : 'subalternate';

            return (
              <p key={childId} className={subRowClass}>
                {triState ? (
                  <span className="uas-tristate-row">
                    <span className="uas-tristate-name">{child.title}</span>
                    <TriStateControl
                      id={childId}
                      value={childTriValue}
                      onChange={(v) => onTriToggle(childId, v)}
                      strings={strings}
                    />
                  </span>
                ) : (
                  <label>
                    <input
                      type="checkbox"
                      checked={isChildChecked}
                      onChange={(e) => onToggle(childId, e.target.checked)}
                    />
                    {child.title}
                  </label>
                )}
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
const AdminBarMenu = ({ adminBarItems, userOptions, onToggle, onTriToggle, triState = false, strings }) => {
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
          triState={triState}
          onTriToggle={onTriToggle}
          strings={strings}
        />
      ))}
    </div>
  );
};

export default AdminBarMenu;
