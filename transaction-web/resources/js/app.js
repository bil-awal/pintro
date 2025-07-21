import './bootstrap';

// Alpine.js
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import persist from '@alpinejs/persist';

// Register Alpine plugins
Alpine.plugin(focus);
Alpine.plugin(persist);

// Make Alpine available globally
window.Alpine = Alpine;

// Start Alpine
Alpine.start();
