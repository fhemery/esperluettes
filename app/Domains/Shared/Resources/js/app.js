import './bootstrap';
import Alpine from 'alpinejs';
import registerTooltip from './tooltip.js';
import { initQuillEditor } from './editor.js';

window.Alpine = Alpine;
window.initQuillEditor = initQuillEditor;

// Register UI components
registerTooltip(Alpine);

Alpine.start();
