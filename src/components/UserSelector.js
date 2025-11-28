import React from 'react';

/**
 * UserSelector component
 * Renders a dropdown to select a user with WordPress-style styling
 */
const UserSelector = ({ users, selectedUser, onChange, strings }) => {
  const handleChange = (e) => {
    const value = e.target.value;
    onChange(value === '' ? '' : value);
  };

  return (
    <div className="uas-user-selector-wrapper">
      <select 
        id="uas_user_select" 
        name="uas_user_select" 
        value={selectedUser}
        onChange={handleChange}
        aria-label={strings.chooseUser || 'Choose a user'}
      >
        <option value="">{strings.choose || 'Choose...'}</option>
        {users.map((user) => (
          <option key={user.nicename} value={user.nicename}>
            {user.nicename}
          </option>
        ))}
      </select>
    </div>
  );
};

export default UserSelector;
