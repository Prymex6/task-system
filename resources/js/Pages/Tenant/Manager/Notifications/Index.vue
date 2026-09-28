<template>
  <ManagerLayout :title="$t('common.notifications')">
    <div class="space-y-5 max-w-2xl">
      <div class="page-header">
        <div>
          <h1 class="page-title">{{ $t('common.notifications') }}</h1>
          <p v-if="unread_count > 0" class="page-subtitle">{{ unread_count }} {{ $t('platform.unread') }}</p>
        </div>
        <button v-if="unread_count > 0" @click="markAll" class="btn-secondary">
          <i class="fa-solid fa-check-double"></i> {{ $t('manager.mark_all_as_read') }}
        </button>
      </div>

      <div class="card divide-y divide-gray-100">
        <div v-if="!notifications.data?.length" class="empty-state py-12">
          <div class="empty-icon">🔔</div>
          <div class="empty-title">{{ $t('portal.no_notifications') }}</div>
          <div class="empty-text">{{ $t('manager.you_have_no_notifications_yet') }}</div>
        </div>

        <div
          v-for="notif in notifications.data"
          :key="notif.id"
          class="px-6 py-4 flex items-start gap-4 hover:bg-gray-50 cursor-pointer transition-colors"
          :class="{ 'bg-indigo-50': !notif.read_at }"
          @click="markRead(notif)"
        >
          <div
            class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 text-sm"
            :class="notifIconBg(notif.type)"
          >
            <i :class="notifIcon(notif.type)"></i>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm text-gray-800" :class="{ 'font-semibold': !notif.read_at }">
              {{ notif.data?.message ?? 'Powiadomienie' }}
            </p>
            <p class="text-xs text-gray-400 mt-0.5">{{ timeAgo(notif.created_at) }}</p>
          </div>
          <div v-if="!notif.read_at" class="w-2 h-2 rounded-full bg-indigo-500 flex-shrink-0 mt-1.5"></div>
        </div>
      </div>

      <Pagination :links="notifications.links" />
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { Link, useForm, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  notifications: Object,
  unread_count: { type: Number, default: 0 },
})

const markRead = (notif) => {
  if (!notif.read_at) {
    useForm({}).post(route('tenant.manager.notifications.read', notif.id), { preserveScroll: true })
  }
  if (notif.data?.url) {
    router.visit(notif.data.url)
  }
}

const markAll = () => {
  useForm({}).post(route('tenant.manager.notifications.read-all'), { preserveScroll: true })
}

const notifIcon = (type) => {
  const icons = {
    task: 'fa-solid fa-list-check',
    project: 'fa-solid fa-diagram-project',
    invoice: 'fa-solid fa-file-invoice',
    ticket: 'fa-solid fa-headset',
    mention: 'fa-solid fa-at',
    system: 'fa-solid fa-bell',
  }
  return icons[type] ?? 'fa-solid fa-bell'
}

const notifIconBg = (type) => {
  const bgs = {
    task: 'bg-amber-100 text-amber-600',
    project: 'bg-indigo-100 text-indigo-600',
    invoice: 'bg-emerald-100 text-emerald-600',
    ticket: 'bg-blue-100 text-blue-600',
    mention: 'bg-purple-100 text-purple-600',
    system: 'bg-gray-100 text-gray-600',
  }
  return bgs[type] ?? 'bg-gray-100 text-gray-600'
}

const timeAgo = (date) => {
  const diff = Date.now() - new Date(date).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return t('common.just_now')
  if (mins < 60) return `${mins} min temu`
  const hrs = Math.floor(mins / 60)
  if (hrs < 24) return `${hrs} godz. temu`
  const days = Math.floor(hrs / 24)
  return `${days} dni temu`
}
</script>
