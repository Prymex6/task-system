<template>
  <div class="relative" v-click-outside="() => (open = false)">
    <button @click="open = !open" class="relative text-gray-400 hover:text-gray-600">
      <i class="fa-solid fa-bell text-lg"></i>
      <span
        v-if="unread > 0"
        class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-4 h-4 flex items-center justify-center rounded-full font-bold"
      >
        {{ unread > 9 ? '9+' : unread }}
      </span>
    </button>

    <div v-if="open" class="absolute right-0 top-9 w-80 bg-white border border-gray-200 rounded-xl shadow-xl z-50">
      <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
        <p class="text-sm font-semibold text-gray-900">{{ $t('common.notifications') }}</p>
        <button v-if="unread > 0" @click="markAllRead" class="text-xs text-indigo-600 hover:underline">
          {{ $t('common.mark_all') }}
        </button>
      </div>

      <div class="max-h-80 overflow-y-auto divide-y divide-gray-50">
        <div v-if="notifications.length === 0" class="px-4 py-6 text-center text-sm text-gray-400">
          {{ $t('common.no_new_notifications') }}
        </div>
        <div
          v-for="n in notifications"
          :key="n.id"
          :class="[
            !n.is_read ? 'bg-indigo-50' : '',
            'flex items-start gap-3 px-4 py-3 hover:bg-gray-50 cursor-pointer',
          ]"
          @click="markRead(n)"
        >
          <div
            class="w-2 h-2 rounded-full mt-2 flex-shrink-0"
            :class="n.is_read ? 'bg-gray-300' : 'bg-indigo-500'"
          ></div>
          <div class="flex-1 min-w-0">
            <p class="text-sm text-gray-900 font-medium truncate">{{ n.title }}</p>
            <p class="text-xs text-gray-500 mt-0.5 truncate">{{ n.body }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ n.time }}</p>
          </div>
        </div>
      </div>

      <div class="px-4 py-2 border-t border-gray-100">
        <Link :href="route('tenant.manager.notifications.index')" class="text-xs text-indigo-600 hover:underline">
          {{ $t('common.all_notifications') }}
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import axios from 'axios'

const open = ref(false)
const unread = ref(0)
const notifications = ref([])

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

const load = async () => {
  try {
    const { data } = await axios.get(route('tenant.manager.notifications.recent'))
    notifications.value = data.notifications
    unread.value = data.unread
  } catch (error) {
    // The bell is not worth interrupting anyone over, but a request that
    // fails silently leaves it empty with no way to tell why.
    console.error('Could not load notifications', error)
  }
}

const markRead = async (n) => {
  if (!n.is_read) {
    n.is_read = true
    unread.value = Math.max(0, unread.value - 1)
    await axios.post(route('tenant.manager.notifications.read', n.id)).catch(() => {})
  }
  open.value = false
  if (n.url) router.visit(n.url)
}

const markAllRead = async () => {
  await axios.post(route('tenant.manager.notifications.read-all')).catch(() => {})
  notifications.value.forEach((n) => (n.is_read = true))
  unread.value = 0
}

onMounted(load)
</script>
