import './bootstrap';
import Alpine from 'alpinejs';
import registerTooltip from './tooltip.js';
import { initQuillEditor } from './editor.js';
import * as DateUtils from './date-utils.js';

window.Alpine = Alpine;
window.initQuillEditor = initQuillEditor;
window.DateUtils = DateUtils;

// Register UI components
registerTooltip(Alpine);

Alpine.start();
