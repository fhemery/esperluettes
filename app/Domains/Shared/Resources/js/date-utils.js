// Shared date utilities for consistent formatting across the app
// Usage:
//   DateUtils.formatDate(new Date())
//   DateUtils.formatTime('2025-01-02T03:04:05Z')
//   DateUtils.formatDateTime(Date.now())
//   // Low-level helpers kept for flexibility:
//   // DateUtils.format(value, opts, lang)
//   // DateUtils.formatIso(isoString, opts, lang)
//   // Local datetime value <-> date/time parts of two native inputs:
//   DateUtils.splitLocalInput('2026-06-21T15:07')  // { date: '2026-06-21', time: '15:07' }
//   DateUtils.joinLocalInput('2026-06-21', '')     // '2026-06-21T00:00'

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

/**
 * Convert a UTC datetime string to the local-time value for <input type="datetime-local">.
 * Uses local getters (getFullYear, getHours…) so no offset arithmetic is needed.
 * @param {string} utcString - UTC datetime string e.g. "2026-06-21T13:07" or "2026-06-21 13:07:00"
 * @returns {string} Local time in "YYYY-MM-DDTHH:mm" format, or "" if unparseable
 */
export function utcToLocalInput(utcString) {
  const d = new Date(utcString.replace(' ', 'T').slice(0, 16) + ':00Z');
  if (isNaN(d)) return '';
  const pad = n => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

/**
 * Convert a <input type="datetime-local"> value (interpreted as the browser's local time)
 * to the UTC datetime string the server expects.
 * @param {string} localString - e.g. "2026-06-21T13:07"
 * @returns {string} UTC time in "YYYY-MM-DDTHH:mm" format, or "" if unparseable
 */
export function localToUtcInput(localString) {
  const d = new Date(localString);
  if (isNaN(d)) return '';
  const pad = n => String(n).padStart(2, '0');
  return `${d.getUTCFullYear()}-${pad(d.getUTCMonth() + 1)}-${pad(d.getUTCDate())}T${pad(d.getUTCHours())}:${pad(d.getUTCMinutes())}`;
}


/**
 * Split a local datetime value into the date and time parts of two native inputs.
 * Pure string work (no Date parsing), so DST-adjacent values come back untouched.
 * @param {string|null|undefined} value - e.g. "2026-06-21T15:07" or "2026-06-21 15:07:00"
 * @returns {{date: string, time: string}} both empty when unparseable
 */
export function splitLocalInput(value) {
  const m = /^(\d{4}-\d{2}-\d{2})[T ](\d{2}:\d{2})/.exec(value ?? '');
  return m ? { date: m[1], time: m[2] } : { date: '', time: '' };
}

/**
 * Join date and time inputs back into a "YYYY-MM-DDTHH:mm" local value.
 * @param {string} date - "YYYY-MM-DD"
 * @param {string} time - "HH:mm" (or "HH:mm:ss"); empty defaults to 00:00
 * @returns {string} "" when the date is empty
 */
export function joinLocalInput(date, time) {
  if (!date) return '';
  return `${date}T${time ? time.slice(0, 5) : '00:00'}`;
}
