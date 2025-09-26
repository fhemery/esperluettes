// Shared date utilities for consistent formatting across the app
// Usage:
//   DateUtils.formatDate(new Date())
//   DateUtils.formatTime('2025-01-02T03:04:05Z')
//   DateUtils.formatDateTime(Date.now())
//   // Low-level helpers kept for flexibility:
//   // DateUtils.format(value, opts, lang)
//   // DateUtils.formatIso(isoString, opts, lang)

const getLang = () => (document?.documentElement?.lang || 'en');

/**
 * Format a Date instance or an ISO-like string using Intl.DateTimeFormat
 * @param {Date|string|number} value - Date instance, ISO string, or timestamp
 * @param {Intl.DateTimeFormatOptions} opts
 * @param {string} [lang]
 */
function format(value, opts = {}, lang = getLang()) {
  const d = value instanceof Date ? value : new Date(value);
  return new Intl.DateTimeFormat(lang, opts).format(d);
}

// Predefined option sets
const DATE_OPTS = { day: '2-digit', month: '2-digit', year: 'numeric' };
const TIME_OPTS = { hour: '2-digit', minute: '2-digit' };
const DATE_TIME_OPTS = { ...DATE_OPTS, ...TIME_OPTS };

/**
 * Format only the date portion using locale defaults (DD/MM/YYYY or similar)
 * @param {Date|string|number} value
 * @param {string} [lang]
 */
export function formatDate(value, lang = getLang()) {
  return format(value, DATE_OPTS, lang);
}

/**
 * Format only the time portion (HH:MM based on locale)
 * @param {Date|string|number} value
 * @param {string} [lang]
 */
export function formatTime(value, lang = getLang()) {
  return format(value, TIME_OPTS, lang);
}

/**
 * Format date and time together using locale defaults
 * @param {Date|string|number} value
 * @param {string} [lang]
 */
export function formatDateTime(value, lang = getLang()) {
  return format(value, DATE_TIME_OPTS, lang);
}
