<template>
  <Head :title="title ? `${title} – Portal klienta` : 'Portal klienta'" />
  <div class="min-h-screen bg-gray-50 flex">
    <FlashMessage />

    <!-- Sidebar -->
    <aside class="w-60 bg-white border-r border-gray-200 flex flex-col fixed inset-y-0 left-0 z-30">
      <!-- Logo -->
      <div class="h-16 flex items-center px-5 border-b border-gray-200">
        <Link :href="route('tenant.portal.dashboard')" class="flex items-center gap-2">
          <i class="fa-solid fa-diagram-project text-indigo-600 text-lg"></i>
          <span class="font-bold text-gray-900 text-sm">{{ $t('nav.client_portal') }}</span>
        </Link>
      </div>

      <!-- Nav -->
      <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5">
        <PortalNavItem
          :href="route('tenant.portal.dashboard')"
          icon="fa-solid fa-house"
          label="Dashboard"
          :active="isActive('dashboard')"
        />
        <PortalNavItem
          :href="route('tenant.portal.projects')"
          icon="fa-solid fa-folder-open"
          :label="$t('common.projects')"
          :active="isActive('projects')"
        />
        <PortalNavItem
          :href="route('tenant.portal.tasks')"
          icon="fa-solid fa-list-check"
          :label="$t('common.tasks')"
          :active="isActive('tasks')"
        />

        <div class="border-t border-gray-100 my-2"></div>

        <PortalNavItem
          :href="route('tenant.portal.invoices')"
          icon="fa-solid fa-file-invoice-dollar"
          :label="$t('common.invoices')"
          :active="isActive('invoices')"
        />
        <PortalNavItem
          :href="route('tenant.portal.estimates')"
          icon="fa-solid fa-file-lines"
          :label="$t('common.estimates')"
          :active="isActive('estimates')"
        />
        <PortalNavItem
          :href="route('tenant.portal.contracts')"
          icon="fa-solid fa-file-contract"
          :label="$t('common.contracts')"
          :active="isActive('contracts')"
        />
        <PortalNavItem
          :href="route('tenant.portal.proposals')"
          icon="fa-solid fa-paper-plane"
          :label="$t('nav.proposals')"
          :active="isActive('proposals')"
        />

        <div class="border-t border-gray-100 my-2"></div>

        <PortalNavItem
          :href="route('tenant.portal.tickets')"
          icon="fa-solid fa-headset"
          :label="$t('common.support')"
          :active="isActive('tickets')"
        />
        <PortalNavItem
          :href="route('tenant.portal.kb')"
          icon="fa-solid fa-book-open"
          :label="$t('common.knowledge_base')"
          :active="isActive('kb')"
        />
      </nav>

      <!-- User -->
      <div class="border-t border-gray-200 p-3">
        <div class="flex items-center gap-2">
          <div
            class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm flex-shrink-0"
          >
            {{ auth?.name?.charAt(0)?.toUpperCase() }}
          </div>
          <div class="overflow-hidden">
            <p class="text-sm font-medium text-gray-900 truncate">{{ auth?.name }}</p>
            <p class="text-xs text-gray-500 truncate">{{ auth?.email }}</p>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main -->
    <div class="ml-60 flex-1 flex flex-col min-h-screen">
      <!-- Top bar -->
      <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20">
        <h2 class="text-gray-800 font-semibold text-base">{{ title }}</h2>
        <div class="flex items-center gap-4">
          <Link :href="route('tenant.portal.notifications')" class="text-gray-400 hover:text-gray-600 relative">
            <i class="fa-solid fa-bell"></i>
            <span
              v-if="unreadCount > 0"
              class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-4 h-4 flex items-center justify-center rounded-full"
            >
              {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
          </Link>
          <div class="relative" v-click-outside="() => (profileOpen = false)">
            <button
              @click="profileOpen = !profileOpen"
              class="flex items-center gap-2 text-sm text-gray-700 hover:text-gray-900"
            >
              <div
                class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm"
              >
                {{ auth?.name?.charAt(0)?.toUpperCase() }}
              </div>
              <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
            </button>
            <div
              v-if="profileOpen"
              class="absolute right-0 top-10 w-44 bg-white border border-gray-200 rounded-lg shadow-lg py-1 z-50"
            >
              <Link
                :href="route('tenant.portal.account')"
                class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
              >
                <i class="fa-solid fa-user w-4"></i> {{ $t('nav.my_account') }}
              </Link>
              <div class="border-t border-gray-100 my-1"></div>
              <Link
                :href="route('tenant.client.logout')"
                method="post"
                as="button"
                class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 w-full text-left"
              >
                <i class="fa-solid fa-right-from-bracket w-4"></i> {{ $t('common.sign_out') }}
              </Link>
            </div>
          </div>
        </div>
      </header>

      <!-- Content -->
      <main class="flex-1 p-6">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import FlashMessage from '@/Components/FlashMessage.vue'
import PortalNavItem from '@/Components/Client/PortalNavItem.vue'

defineProps({ title: { type: String, default: '' } })

const page = usePage()
const auth = page.props.auth?.contact
const profileOpen = ref(false)
const unreadCount = ref(page.props.unreadNotifications ?? 0)

const isActive = (section) => {
  const url = page.url
  if (section === 'dashboard') return url.endsWith('/portal') || url.endsWith('/portal/')
  return url.includes(`/portal/${section}`)
}

const vClickOutside = {
  mounted(el, binding) {
    el.__clickOutside = (e) => {
      if (!el.contains(e.target)) binding.value(e)
    }
    document.addEventListener('click', el.__clickOutside)
  },
  unmounted(el) {
    document.removeEventListener('click', el.__clickOutside)
  },
}
</script>
