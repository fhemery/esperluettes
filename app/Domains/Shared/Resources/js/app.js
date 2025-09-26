import './bootstrap';
import Alpine from 'alpinejs';
import registerTooltip from './tooltip.js';
import { initQuillEditor } from './editor.js';
import * as DateUtils from './date-utils.js';
import {BadgeOverflow} from './badge-overflow.js';

window.Alpine = Alpine;
window.initQuillEditor = initQuillEditor;
window.DateUtils = DateUtils;
window.BadgeOverflow = BadgeOverflow;

// Register UI components
registerTooltip(Alpine);

Alpine.start();
