import './bootstrap'
import { registerSW } from 'virtual:pwa-register'
import axios from 'axios'

// Handle CSRF token expiry (419) globally — reload to get a fresh token
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 419) {
      window.location.reload()
    }
    return Promise.reject(error)
  },
)

import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { createPinia } from 'pinia'
import { ZiggyVue } from 'ziggy-js'
import { createI18nFor, normaliseLocale } from './i18n'
import { setFormattingLocale } from './format'

let appName = import.meta.env.VITE_APP_NAME || 'Laravel'

// The language is read off the first Inertia payload, which is already in
// the document, so the dictionary is chosen before anything renders rather
// than after a round trip.
const initialLocale = JSON.parse(document.getElementById('app')?.dataset.page ?? '{}')?.props?.current_locale

const i18n = await createI18nFor(initialLocale)

// Dates and money follow the interface language, not the browser's.
setFormattingLocale(normaliseLocale(initialLocale))

createInertiaApp({
  title: (title) => (title ? `${title} - ${appName}` : appName),
  // Reload on CSRF expiry (419) so Inertia gets a fresh token
  onError: (error) => {
    if (error?.response?.status === 419) {
      window.location.reload()
    }
  },
  resolve: (name) => {
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
    const page = pages[`./Pages/${name}.vue`]
    if (!page) {
      throw new Error(`Page not found: ${name}. Make sure the file exists at ./Pages/${name}.vue`)
    }
    return page
  },
  setup({ el, App, props, plugin }) {
    const workspaceName = props.initialPage?.props?.tenant?.name
    if (workspaceName) {
      appName = workspaceName
    }

    const app = createApp({ render: () => h(App, props) })

    app.use(plugin)
    app.use(createPinia())
    app.use(ZiggyVue)
    app.use(i18n)

    app.mount(el)

    return app
  },
  progress: {
    color: '#4B5563',
  },
})

// Register Service Worker for PWA
const updateSW = registerSW({
  immediate: true,
  onNeedRefresh() {
    if (confirm(i18n.global.t('common.a_new_version_is_available_reload'))) {
      updateSW(true)
    }
  },
  onOfflineReady() {
    console.log('App ready to work offline')
  },
})
