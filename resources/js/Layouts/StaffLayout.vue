<template>
  <Head :title="$t('nav.staff_panel')" />
  <div class="min-h-screen bg-gray-100">
    <!-- Top Navigation (same style as ManagerLayout) -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-full px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center space-x-6">
            <h1 class="text-xl font-bold text-blue-600">{{ panelTitle }}</h1>

            <!-- Staff panel switcher -->
            <nav class="hidden md:flex items-center space-x-1">
              <Link
                :href="route('tenant.staff.fulfillment')"
                class="flex items-center px-3 py-1.5 rounded text-sm font-medium transition-colors"
                :class="isActive('fulfillment') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-100'"
              >
                <i class="fa-solid fa-boxes-packing mr-1.5 text-blue-500"></i> {{ $t('common.delivery') }}
              </Link>
            </nav>
          </div>

          <div class="flex items-center gap-3">
            <span class="text-sm text-gray-600">{{ auth?.name }}</span>
            <Link
              v-if="auth?.role === 'manager'"
              :href="route('tenant.manager.dashboard')"
              class="text-sm text-gray-600 hover:text-gray-900 transition-colors"
            >
              {{ $t('nav.manager_panel') }}
            </Link>
            <Link
              :href="route('tenant.staff.reports.index')"
              class="text-sm transition-colors"
              :class="isActive('reports') ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900'"
            >
              {{ $t('nav.report') }}
            </Link>
            <a :href="route('tenant.shop')" class="text-sm text-gray-600 hover:text-gray-900">
              {{ $t('common.workspace') }}
            </a>
            <button @click="logout" class="text-sm text-red-600 hover:text-red-700 font-medium transition-colors">
              {{ $t('common.sign_out') }}
            </button>
          </div>
          <!-- end flex -->
        </div>
      </div>
    </nav>

    <!-- Content -->
    <main class="p-6">
      <slot />
    </main>

    <!-- Global new order toast notification -->
    <transition name="slide-down"> </transition>
  </div>
</template>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}
</style>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, Link, usePage, router } from '@inertiajs/vue3'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import axios from 'axios'

defineProps({
  panelTitle: {
    type: String,
    default: 'Panel',
  },
})

const page = usePage()
const auth = page.props.auth?.user

const isActive = (panel) => {
  return page.url.includes(`/staff/${panel}`)
}

// Echo: new order notifications for all staff roles
onMounted(() => {
  window.Pusher = Pusher
  if (!window.Echo) {
    window.Echo = new Echo({
      broadcaster: 'reverb',
      key: import.meta.env.VITE_REVERB_APP_KEY,
      wsHost: import.meta.env.VITE_REVERB_HOST,
      wsPort: import.meta.env.VITE_REVERB_PORT,
      wssPort: import.meta.env.VITE_REVERB_PORT,
      forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
      enabledTransports: ['ws', 'wss'],
      auth: {
        headers: {
          'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
        },
      },
    })
  }
})

onUnmounted(() => {})

// Register push subscription (#32)
onMounted(async () => {
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) return

  try {
    const registration = await navigator.serviceWorker.ready
    let subscription = await registration.pushManager.getSubscription()

    if (!subscription) {
      const vapidPublicKey = page.props.vapidPublicKey
      if (!vapidPublicKey) return

      const convertedKey = urlBase64ToUint8Array(vapidPublicKey)
      subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: convertedKey,
      })
    }

    const sub = subscription.toJSON()
    await axios.post(route('tenant.staff.push.subscribe'), {
      endpoint: sub.endpoint,
      p256dh_key: sub.keys?.p256dh ?? null,
      auth_key: sub.keys?.auth ?? null,
      expires_at: sub.expirationTime ?? null,
    })
  } catch (e) {
    // Push subscription failed silently (user denied or unsupported)
  }
})

async function logout() {
  try {
    if ('serviceWorker' in navigator && 'PushManager' in window) {
      const registration = await navigator.serviceWorker.ready
      const subscription = await registration.pushManager.getSubscription()
      if (subscription) {
        await axios.post(route('tenant.staff.push.unsubscribe'), { endpoint: subscription.endpoint })
        await subscription.unsubscribe()
      }
    }
  } catch (e) {
    // Silent — push unsubscribe failure shouldn't block logout
  }
  router.post(route('tenant.logout'))
}

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
  const rawData = atob(base64)
  return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)))
}
</script>
