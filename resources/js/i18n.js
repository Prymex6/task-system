import { createI18n } from 'vue-i18n'

/**
 * Languages the interface exists in.
 *
 * Both dictionaries are generated from the same key set, so a key present in
 * one is present in the other. That is what makes it safe to load only the
 * language a page is being rendered in: there is no second file to fall back
 * to, and no reason to send one.
 */
export const SUPPORTED_LOCALES = ['pl', 'en']

export const FALLBACK_LOCALE = 'pl'

const dictionaries = import.meta.glob('./locales/*.json')

export function normaliseLocale(locale) {
  return SUPPORTED_LOCALES.includes(locale) ? locale : FALLBACK_LOCALE
}

export async function createI18nFor(locale) {
  const active = normaliseLocale(locale)
  const load = dictionaries[`./locales/${active}.json`]
  const messages = load ? (await load()).default : {}

  return createI18n({
    // The Options API half of vue-i18n is not used anywhere here, and
    // leaving it on costs every component a mixin it does not need.
    legacy: false,
    globalInjection: true,
    locale: active,
    fallbackLocale: active,
    messages: { [active]: messages },
    missingWarn: import.meta.env.DEV,
    fallbackWarn: false,
  })
}
