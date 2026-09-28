<template>
  <Head :title="title ? `${title} – TaskSystem` : 'TaskSystem'" />
  <div class="min-h-screen bg-gray-50 flex">
    <FlashMessage />

    <!-- Sidebar -->
    <aside
      :class="[
        sidebarOpen ? 'w-64' : 'w-16',
        'bg-indigo-900 text-white flex flex-col transition-all duration-300 fixed inset-y-0 left-0 z-30',
      ]"
    >
      <!-- Logo -->
      <div class="flex items-center justify-between h-16 px-4 border-b border-indigo-800">
        <Link :href="route('tenant.manager.dashboard')" class="flex items-center gap-2 overflow-hidden">
          <span class="text-indigo-300 text-xl"><i class="fa-solid fa-diagram-project"></i></span>
          <span v-show="sidebarOpen" class="font-bold text-white text-sm whitespace-nowrap">TaskSystem</span>
        </Link>
        <button @click="sidebarOpen = !sidebarOpen" class="text-indigo-400 hover:text-white ml-1">
          <i :class="sidebarOpen ? 'fa-solid fa-chevron-left' : 'fa-solid fa-chevron-right'"></i>
        </button>
      </div>

      <!-- Nav -->
      <nav class="flex-1 overflow-y-auto py-4 space-y-0.5 px-2">
        <NavItem
          :href="route('tenant.manager.dashboard')"
          icon="fa-solid fa-house"
          :label="sidebarOpen ? 'Dashboard' : ''"
          :active="isActive('dashboard')"
        />

        <div v-show="sidebarOpen" class="px-3 pt-4 pb-1 text-xs font-semibold text-indigo-400 uppercase tracking-wider">
          {{ $t('common.projects') }}
        </div>

        <NavItem
          :href="route('tenant.manager.projects.index')"
          icon="fa-solid fa-folder-open"
          :label="sidebarOpen ? 'Projekty' : ''"
          :active="isActive('projects')"
        />
        <NavItem
          :href="route('tenant.manager.tasks.index')"
          icon="fa-solid fa-list-check"
          :label="sidebarOpen ? 'Zadania' : ''"
          :active="isActive('tasks')"
        />
        <NavItem
          :href="route('tenant.manager.sprints.index')"
          icon="fa-solid fa-rocket"
          :label="sidebarOpen ? 'Sprinty' : ''"
          :active="isActive('sprints')"
        />
        <NavItem
          :href="route('tenant.manager.time.index')"
          icon="fa-solid fa-clock"
          :label="sidebarOpen ? 'Czas pracy' : ''"
          :active="isActive('time')"
        />

        <div v-show="sidebarOpen" class="px-3 pt-4 pb-1 text-xs font-semibold text-indigo-400 uppercase tracking-wider">
          CRM
        </div>

        <NavItem
          :href="route('tenant.manager.clients.index')"
          icon="fa-solid fa-building"
          :label="sidebarOpen ? 'Klienci' : ''"
          :active="isActive('clients')"
        />
        <NavItem
          :href="route('tenant.manager.leads.index')"
          icon="fa-solid fa-user-plus"
          :label="sidebarOpen ? 'Leady' : ''"
          :active="isActive('leads')"
        />
        <NavItem
          :href="route('tenant.manager.deals.index')"
          icon="fa-solid fa-handshake"
          :label="sidebarOpen ? 'Deale' : ''"
          :active="isActive('deals')"
        />

        <div v-show="sidebarOpen" class="px-3 pt-4 pb-1 text-xs font-semibold text-indigo-400 uppercase tracking-wider">
          {{ $t('common.finance') }}
        </div>

        <NavItem
          :href="route('tenant.manager.invoices.index')"
          icon="fa-solid fa-file-invoice-dollar"
          :label="sidebarOpen ? 'Faktury' : ''"
          :active="isActive('invoices')"
        />
        <NavItem
          :href="route('tenant.manager.estimates.index')"
          icon="fa-solid fa-file-lines"
          :label="sidebarOpen ? 'Wyceny' : ''"
          :active="isActive('estimates')"
        />
        <NavItem
          :href="route('tenant.manager.expenses.index')"
          icon="fa-solid fa-receipt"
          :label="sidebarOpen ? 'Wydatki' : ''"
          :active="isActive('expenses')"
        />

        <div v-show="sidebarOpen" class="px-3 pt-4 pb-1 text-xs font-semibold text-indigo-400 uppercase tracking-wider">
          {{ $t('nav.documents') }}
        </div>

        <NavItem
          :href="route('tenant.manager.contracts.index')"
          icon="fa-solid fa-file-contract"
          :label="sidebarOpen ? 'Umowy' : ''"
          :active="isActive('contracts')"
        />
        <NavItem
          :href="route('tenant.manager.proposals.index')"
          icon="fa-solid fa-paper-plane"
          :label="sidebarOpen ? 'Propozycje' : ''"
          :active="isActive('proposals')"
        />

        <div v-show="sidebarOpen" class="px-3 pt-4 pb-1 text-xs font-semibold text-indigo-400 uppercase tracking-wider">
          Support
        </div>

        <NavItem
          :href="route('tenant.manager.support.index')"
          icon="fa-solid fa-headset"
          :label="sidebarOpen ? 'Tickety' : ''"
          :active="isActive('tickets')"
          :badge="openTickets"
        />
        <NavItem
          :href="route('tenant.manager.kb.index')"
          icon="fa-solid fa-book-open"
          :label="sidebarOpen ? 'Baza wiedzy' : ''"
          :active="isActive('kb')"
        />

        <div v-show="sidebarOpen" class="px-3 pt-4 pb-1 text-xs font-semibold text-indigo-400 uppercase tracking-wider">
          HR
        </div>

        <NavItem
          :href="route('tenant.manager.staff.index')"
          icon="fa-solid fa-users"
          :label="sidebarOpen ? 'Pracownicy' : ''"
          :active="isActive('staff')"
        />
        <NavItem
          :href="route('tenant.manager.hr.attendance')"
          icon="fa-solid fa-calendar-check"
          :label="sidebarOpen ? $t('common.attendance') : ''"
          :active="isActive('attendance')"
        />
        <NavItem
          :href="route('tenant.manager.hr.leave')"
          icon="fa-solid fa-umbrella-beach"
          :label="sidebarOpen ? 'Urlopy' : ''"
          :active="isActive('leave')"
        />

        <div v-show="sidebarOpen" class="px-3 pt-4 pb-1 text-xs font-semibold text-indigo-400 uppercase tracking-wider">
          {{ $t('common.other') }}
        </div>

        <NavItem
          :href="route('tenant.manager.messages.index')"
          icon="fa-solid fa-comments"
          :label="sidebarOpen ? $t('manager.messages') : ''"
          :active="isActive('messages')"
        />
        <NavItem
          :href="route('tenant.manager.reports.index')"
          icon="fa-solid fa-chart-bar"
          :label="sidebarOpen ? 'Raporty' : ''"
          :active="isActive('reports')"
        />
        <NavItem
          :href="route('tenant.manager.automations.index')"
          icon="fa-solid fa-wand-magic-sparkles"
          :label="sidebarOpen ? 'Automatyzacje' : ''"
          :active="isActive('automations')"
        />
        <NavItem
          :href="route('tenant.manager.settings.index')"
          icon="fa-solid fa-gear"
          :label="sidebarOpen ? 'Ustawienia' : ''"
          :active="isActive('settings')"
        />
      </nav>

      <!-- User footer -->
      <div class="border-t border-indigo-800 p-3">
        <div class="flex items-center gap-2 overflow-hidden">
          <div
            class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center flex-shrink-0 text-sm font-bold"
          >
            {{ auth?.name?.charAt(0)?.toUpperCase() }}
          </div>
          <div v-show="sidebarOpen" class="overflow-hidden">
            <p class="text-sm font-medium text-white truncate">{{ auth?.name }}</p>
            <p class="text-xs text-indigo-400 truncate capitalize">{{ auth?.workspace_role }}</p>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main content -->
    <div :class="[sidebarOpen ? 'ml-64' : 'ml-16', 'flex-1 flex flex-col min-h-screen transition-all duration-300']">
      <!-- Top bar -->
      <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20">
        <div class="flex items-center gap-3">
          <h2 class="text-gray-800 font-semibold text-base">{{ title }}</h2>
        </div>
        <div class="flex items-center gap-4">
          <Link :href="route('tenant.manager.search')" class="text-gray-400 hover:text-gray-600 text-sm">
            <i class="fa-solid fa-magnifying-glass"></i>
          </Link>
          <NotificationBell />
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
              class="absolute right-0 top-10 w-48 bg-white border border-gray-200 rounded-lg shadow-lg py-1 z-50"
            >
              <p class="px-4 py-2 text-xs text-gray-500 border-b border-gray-100">{{ auth?.email }}</p>
              <Link
                :href="route('tenant.manager.profile')"
                class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
              >
                <i class="fa-solid fa-user w-4"></i> {{ $t('nav.my_profile') }}
              </Link>
              <div class="border-t border-gray-100 my-1"></div>
              <Link
                :href="route('tenant.logout')"
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

      <!-- Page content -->
      <main class="flex-1 p-6">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, Link, usePage, router } from '@inertiajs/vue3'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import FlashMessage from '@/Components/FlashMessage.vue'
import NotificationBell from '@/Components/Manager/NotificationBell.vue'
import NavItem from '@/Components/Manager/NavItem.vue'

defineProps({ title: { type: String, default: '' } })

const page = usePage()
const auth = page.props.auth?.user
const sidebarOpen = ref(true)
const profileOpen = ref(false)
const openTickets = ref(page.props.openTickets ?? 0)

let echo = null

const isActive = (section) => {
  const url = page.url
  if (section === 'dashboard') return url === '/manager' || url === '/manager/' || url.endsWith('/manager')
  return url.includes(`/manager/${section}`)
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

onMounted(() => {
  if (!import.meta.env.VITE_REVERB_APP_KEY) return
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
    })
  }
  echo = window.Echo
  if (auth?.id) {
    echo.private(`user.${auth.id}`).listen('.notification.created', () => {
      router.reload({ only: ['notifications'] })
    })
  }
})

onUnmounted(() => {
  if (auth?.id && echo) echo.leave(`user.${auth.id}`)
})
</script>
