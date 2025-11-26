import React from 'react';

/**
 * SaveButton component
 * Renders save and reset buttons
 */
const SaveButton = ({ onSave, onReset, isSaving, strings }) => {
  return (
    <div className="uas-buttons">
      <button 
        type="button"
        className="button button-primary"
        onClick={onSave}
        disabled={isSaving}
      >
        {isSaving 
          ? (strings.saving || 'Saving...') 
          : (strings.saveChanges || 'Save Changes')}
      </button>
      <button 
        type="button"
        className="button button-secondary"
        onClick={onReset}
        disabled={isSaving}
      >
        {strings.resetUser || 'Reset User Settings'}
      </button>
    </div>
  );
};

export default SaveButton;
