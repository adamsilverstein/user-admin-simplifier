import React from 'react';

/**
 * RoleSelector component
 * Dropdown to select a role to edit.
 */
const RoleSelector = ({ roles, selectedRole, onChange, strings }) => {
  const handleChange = (e) => {
    onChange(e.target.value);
  };

  return (
    <div className="uas-role-selector-wrapper">
      <select
        id="uas_role_select"
        name="uas_role_select"
        value={selectedRole}
        onChange={handleChange}
        aria-label={strings.chooseRole || 'Choose a role'}
      >
        <option value="">{strings.choose || 'Choose...'}</option>
        {roles.map((role) => (
          <option key={role.slug} value={role.slug}>
            {role.name}
          </option>
        ))}
      </select>
    </div>
  );
};

export default RoleSelector;
