import React from 'react';

/**
 * ModeSelector component
 * Radio group to choose the menu control mode.
 */
const ModeSelector = ({ mode, onChange, strings }) => {
  const modes = [
    { value: 'per-user', label: strings.modePerUser || 'Per-user only' },
    { value: 'role', label: strings.modeRole || 'Role-based only' },
    {
      value: 'role-with-overrides',
      label: strings.modeRoleOverrides || 'Role-based with per-user overrides',
    },
  ];

  return (
    <fieldset className="uas-mode-selector">
      <legend>{strings.modeLabel || 'Menu control mode'}</legend>
      {modes.map((m) => (
        <label key={m.value} className="uas-mode-option">
          <input
            type="radio"
            name="uas-mode"
            value={m.value}
            checked={mode === m.value}
            onChange={() => onChange(m.value)}
          />
          {m.label}
        </label>
      ))}
    </fieldset>
  );
};

export default ModeSelector;
