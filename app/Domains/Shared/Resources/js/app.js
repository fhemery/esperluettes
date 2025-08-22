import './bootstrap';
import Alpine from 'alpinejs';
import registerTooltip from './tooltip.js';

window.Alpine = Alpine;

// Register UI components
registerTooltip(Alpine);

Alpine.start();
