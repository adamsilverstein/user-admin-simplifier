import React, { useState, useMemo, useCallback } from 'react';

/**
 * TriStateControl
 * Renders Inherit / Show / Hide radios for override mode. `value` is one of
 * 'inherit' | 'show' | 'hide'. onChange receives the new value.
 */
const TriStateControl = ({ id, label, value, onChange, strings }) => (
  <span className="uas-tristate" role="radiogroup" aria-label={label}>
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
 * MenuItem component
 * Renders a single menu item with optional submenus, a drag handle and
 * keyboard accessible move up/down buttons for reordering.
 */
const MenuItem = ({
  item,
  userOptions,
  onToggle,
  rowIndex,
  totalItems,
  onMove,
  onDragStart,
  onDragEnter,
  onDrop,
  onDragEnd,
  isDragging,
  isDropTarget,
  triState,
  onTriToggle,
  strings
}) => {
  const [isExpanded, setIsExpanded] = useState(false);
  const [isDraggable, setIsDraggable] = useState(false);
  const menuId = item.id;
  const isChecked = userOptions[menuId] === 1;
  const triValue =
    userOptions[menuId] === 1 ? 'hide' : userOptions[menuId] === 0 ? 'show' : 'inherit';
  const hasSubmenus = item.submenus && item.submenus.length > 0;

  const handleToggle = (e) => {
    onToggle(menuId, e.target.checked);
  };

  const toggleSubmenu = () => {
    setIsExpanded(!isExpanded);
  };

  const handleDragStart = (e) => {
    e.dataTransfer.effectAllowed = 'move';
    // Some browsers require data to be set for the drag to start.
    e.dataTransfer.setData('text/plain', menuId);
    onDragStart(rowIndex);
  };

  const handleDragOver = (e) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    onDragEnter(rowIndex);
  };

  const handleDrop = (e) => {
    e.preventDefault();
    onDrop(rowIndex);
    setIsDraggable(false);
  };

  const handleDragEnd = () => {
    onDragEnd();
    setIsDraggable(false);
  };

  const rowClass = [
    rowIndex % 2 === 0 ? 'menumain' : 'menualternate',
    isDragging ? 'uas-dragging' : '',
    isDropTarget ? 'uas-drop-target' : ''
  ].filter(Boolean).join(' ');

  return (
    <>
      <p
        className={rowClass}
        draggable={isDraggable}
        onDragStart={handleDragStart}
        onDragOver={handleDragOver}
        onDrop={handleDrop}
        onDragEnd={handleDragEnd}
      >
        <span
          className="uas-drag-handle"
          title={strings.dragToReorder || 'Drag to reorder'}
          aria-hidden="true"
          onMouseDown={() => setIsDraggable(true)}
          onMouseUp={() => setIsDraggable(false)}
        >
          ⠿
        </span>
        <span className="uas-move-buttons">
          <button
            type="button"
            className="uas-move-btn"
            onClick={() => onMove(rowIndex, -1)}
            disabled={rowIndex === 0}
            aria-label={`${strings.moveUp || 'Move up'}: ${item.name}`}
            title={strings.moveUp || 'Move up'}
          >
            ▲
          </button>
          <button
            type="button"
            className="uas-move-btn"
            onClick={() => onMove(rowIndex, 1)}
            disabled={rowIndex === totalItems - 1}
            aria-label={`${strings.moveDown || 'Move down'}: ${item.name}`}
            title={strings.moveDown || 'Move down'}
          >
            ▼
          </button>
        </span>
        {triState ? (
          <span className="uas-tristate-row">
            <span className="uas-tristate-name">{item.name}</span>
            <TriStateControl
              id={menuId}
              label={item.name}
              value={triValue}
              onChange={(v) => onTriToggle(menuId, v)}
              strings={strings}
            />
          </span>
        ) : (
          <label>
            <input type="checkbox" checked={isChecked} onChange={handleToggle} />
            {item.name}
          </label>
        )}
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
            const subTriValue =
              userOptions[submenuId] === 1 ? 'hide' : userOptions[submenuId] === 0 ? 'show' : 'inherit';
            const subRowClass = subIndex % 2 === 0 ? 'submain' : 'subalternate';

            return (
              <p key={submenuId} className={subRowClass}>
                {triState ? (
                  <span className="uas-tristate-row">
                    <span className="uas-tristate-name">{submenu.name}</span>
                    <TriStateControl
                      id={submenuId}
                      label={submenu.name}
                      value={subTriValue}
                      onChange={(v) => onTriToggle(submenuId, v)}
                      strings={strings}
                    />
                  </span>
                ) : (
                  <label>
                    <input
                      type="checkbox"
                      checked={isSubmenuChecked}
                      onChange={(e) => onToggle(submenuId, e.target.checked)}
                    />
                    {submenu.name}
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
 * MenuList component
 * Renders the list of WordPress admin menu items
 */
const MenuList = ({ menuItems, userOptions, onToggle, onTriToggle, onReorder, triState = false, strings }) => {
  const [dragIndex, setDragIndex] = useState(null);
  const [dropIndex, setDropIndex] = useState(null);

  // Apply the saved per-user order to the menu items. Items missing from the
  // saved order keep their default relative position (matches the PHP logic).
  const orderedMenuItems = useMemo(() => {
    const savedOrder = Array.isArray(userOptions['menu-order'])
      ? userOptions['menu-order']
      : [];

    if (savedOrder.length === 0) {
      return menuItems;
    }

    const items = [...menuItems];
    const slots = [];
    const itemsById = {};

    items.forEach((item, index) => {
      if (savedOrder.includes(item.id)) {
        slots.push(index);
        itemsById[item.id] = item;
      }
    });

    let slot = 0;
    savedOrder.forEach(id => {
      if (itemsById[id]) {
        items[slots[slot]] = itemsById[id];
        slot++;
      }
    });

    return items;
  }, [menuItems, userOptions]);

  /**
   * Move an item by a delta (used by the keyboard accessible buttons)
   */
  const handleMove = useCallback((fromIndex, delta) => {
    const toIndex = fromIndex + delta;
    if (toIndex < 0 || toIndex >= orderedMenuItems.length) return;

    const items = [...orderedMenuItems];
    const [moved] = items.splice(fromIndex, 1);
    items.splice(toIndex, 0, moved);
    onReorder(items.map(item => item.id));
  }, [orderedMenuItems, onReorder]);

  const handleDragStart = useCallback((index) => {
    setDragIndex(index);
    setDropIndex(index);
  }, []);

  const handleDragEnter = useCallback((index) => {
    setDropIndex(prev => (prev === index ? prev : index));
  }, []);

  const handleDrop = useCallback((index) => {
    if (dragIndex !== null && index !== dragIndex) {
      const items = [...orderedMenuItems];
      const [moved] = items.splice(dragIndex, 1);
      items.splice(index, 0, moved);
      onReorder(items.map(item => item.id));
    }
    setDragIndex(null);
    setDropIndex(null);
  }, [dragIndex, orderedMenuItems, onReorder]);

  const handleDragEnd = useCallback(() => {
    setDragIndex(null);
    setDropIndex(null);
  }, []);

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
        <span className="uas-reorder-hint">
          {strings.reorderHint || 'Drag a menu item, or use its arrow buttons, to change the menu order for this user.'}
        </span>
      </div>
      {orderedMenuItems.map((item, index) => (
        <MenuItem
          key={item.id}
          item={item}
          userOptions={userOptions}
          onToggle={onToggle}
          rowIndex={index}
          totalItems={orderedMenuItems.length}
          onMove={handleMove}
          onDragStart={handleDragStart}
          onDragEnter={handleDragEnter}
          onDrop={handleDrop}
          onDragEnd={handleDragEnd}
          isDragging={dragIndex === index}
          isDropTarget={dropIndex === index && dragIndex !== null && dragIndex !== index}
          triState={triState}
          onTriToggle={onTriToggle}
          strings={strings}
        />
      ))}
    </div>
  );
};

export default MenuList;
