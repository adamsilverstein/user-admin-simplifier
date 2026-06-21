import React from 'react';

/**
 * TriStateControl
 * Renders Inherit / Show / Hide radios for override mode. `value` is one of
 * 'inherit' | 'show' | 'hide'. onChange receives the new value. `groupName`
 * must be unique per control so radio groups do not collide across the page.
 * Optional `labels` overrides the show/hide text for controls where Show/Hide
 * reads awkwardly (e.g. the admin-bar disable toggle uses Enabled/Disabled).
 */
const TriStateControl = ({ groupName, label, value, onChange, strings, labels = {} }) => (
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
          ? (labels.inherit || strings.inherit || 'Inherit')
          : opt === 'show'
          ? (labels.show || strings.show || 'Show')
          : (labels.hide || strings.hide || 'Hide')}
      </label>
    ))}
  </span>
);

export default TriStateControl;
