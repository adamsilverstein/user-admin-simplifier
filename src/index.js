import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';
import './styles.css';

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('uas-react-root');
  if (container) {
    const root = createRoot(container);
    root.render(<App />);
  }
});
