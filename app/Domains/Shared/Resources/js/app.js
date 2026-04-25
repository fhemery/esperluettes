import './bootstrap';
// This import was 2026 April Fools Day prank, to show easter egg on the website.
// Deactivated for now, to save bundle space.
//import './april-fools';
import Alpine from 'alpinejs';
import registerTooltip from './tooltip.js';
import * as DateUtils from './date-utils.js';
import {BadgeOverflow} from './badge-overflow.js';
import '../../../Moderation/Private/Resources/js/moderation.js';
import intersect from '@alpinejs/intersect'
import './countdown-timer.js';
 
window.Alpine = Alpine;

window.DateUtils = DateUtils;
window.BadgeOverflow = BadgeOverflow;

Alpine.plugin(intersect)

// Register UI components
// Global store for popover exclusivity (single-open behavior)
Alpine.store('popover', { openId: null });
registerTooltip(Alpine);

Alpine.start();

// Global spoiler reveal: event delegation on document body.
// Clicking a .ql-spoiler outside a Quill editor fades its background to near-transparent.
document.addEventListener('click', function (e) {
  const spoiler = e.target.closest('.ql-spoiler');
  if (!spoiler) return;
  // Do not trigger inside the Quill editor itself
  if (spoiler.closest('.ql-editor')) return;
  spoiler.classList.add('ql-spoiler--revealed');
});
