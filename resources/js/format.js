/**
 * Locale-aware formatting for dates and money.
 *
 * Every screen used to call Intl with 'pl-PL' written into it, so a workspace
 * switched to English still got Polish dates and a Polish thousands separator.
 * The tag comes from the interface language instead, and the Polish output is
 * unchanged because 'pl' still maps to 'pl-PL'.
 *
 * This is a plain module rather than a composable: the i18n instance is built
 * per request in app.js, and half these calls happen outside a component's
 * setup where a composable cannot be used.
 */

const INTL_TAGS = {
  pl: 'pl-PL',
  en: 'en-GB',
}

const DEFAULT_CURRENCY = 'PLN'

let active = 'pl'

export function setFormattingLocale(locale) {
  active = locale in INTL_TAGS ? locale : 'pl'
}

export function intlLocale() {
  return INTL_TAGS[active]
}

export function formatDate(value, options) {
  return value ? new Date(value).toLocaleDateString(intlLocale(), options) : null
}

export function formatDateTime(value, options) {
  return value ? new Date(value).toLocaleString(intlLocale(), options) : null
}

export function formatMoney(value, currency = DEFAULT_CURRENCY, options) {
  if (value === null || value === undefined || value === '') {
    return null
  }

  return new Intl.NumberFormat(intlLocale(), { style: 'currency', currency, ...options }).format(value)
}
