import React from 'react';

/**
 * TriStateControl
 * Renders Inherit / Show / Hide radios for override mode. `value` is one of
 * 'inherit' | 'show' | 'hide'. onChange receives the new value. `groupName`
 * must be unique per control so radio groups do not collide across the page.
 */
const TriStateControl = ({ groupName, label, value, onChange, strings }) => (
  <span className="uas-tristate" role="radiogroup" aria-label={label}>
    {['inherit', 'show', 'hide'].map((opt) => (
      <label key={opt} className="uas-tristate-option">
        <input
          type="radio"
          name={groupName}
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

export default TriStateControl;
