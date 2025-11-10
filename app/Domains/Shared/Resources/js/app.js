import './bootstrap';
import Alpine from 'alpinejs';
import registerTooltip from './tooltip.js';
import * as DateUtils from './date-utils.js';
import {BadgeOverflow} from './badge-overflow.js';
import '../../../Moderation/Private/Resources/js/moderation.js';
import intersect from '@alpinejs/intersect'
 
window.Alpine = Alpine;

window.DateUtils = DateUtils;
window.BadgeOverflow = BadgeOverflow;

Alpine.plugin(intersect)

// Register UI components
// Global store for popover exclusivity (single-open behavior)
Alpine.store('popover', { openId: null });
registerTooltip(Alpine);

Alpine.start();
