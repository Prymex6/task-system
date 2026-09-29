<template>
  <ClientLayout :title="$t('common.notifications')">
    <div class="space-y-5 max-w-2xl">
      <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">{{ $t('common.notifications') }}</h1>
        <button v-if="unread_count > 0" @click="markAll" class="btn-secondary btn-sm">
          <i class="fa-solid fa-check-double"></i> {{ $t('common.mark_all') }}
        </button>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
        <div v-if="!notifications.data?.length" class="p-12 text-center">
          <div class="text-3xl mb-2">🔔</div>
          <div class="text-gray-500">{{ $t('portal.no_notifications') }}</div>
        </div>

        <div
          v-for="notif in notifications.data"
          :key="notif.id"
          class="px-5 py-4 flex items-start gap-3 cursor-pointer hover:bg-gray-50 transition-colors"
          :class="{ 'bg-indigo-50': !notif.read_at }"
          @click="markRead(notif)"
        >
          <div
            class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-xs flex-shrink-0"
          >
            <i class="fa-solid fa-bell"></i>
          </div>
          <div class="flex-1">
            <p class="text-sm text-gray-800" :class="{ 'font-semibold': !notif.read_at }">
              {{ notif.data?.message ?? $t('common.notification') }}
            </p>
            <p class="text-xs text-gray-400 mt-0.5">{{ timeAgo(notif.created_at) }}</p>
          </div>
          <div v-if="!notif.read_at" class="w-2 h-2 rounded-full bg-indigo-500 mt-1.5 flex-shrink-0"></div>
        </div>
      </div>

      <Pagination :links="notifications.links" />
    </div>
  </ClientLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { Link, useForm, router } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  notifications: Object,
  unread_count: { type: Number, default: 0 },
})

const markRead = (notif) => {
  if (!notif.read_at) {
    useForm({}).post(route('tenant.portal.notifications.read', notif.id), { preserveScroll: true })
  }
}

const markAll = () => {
  useForm({}).post(route('tenant.portal.notifications.read-all'), { preserveScroll: true })
}

const timeAgo = (date) => {
  const diff = Date.now() - new Date(date).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return t('common.just_now')
  if (mins < 60) return `${mins} min temu`
  const hrs = Math.floor(mins / 60)
  if (hrs < 24) return `${hrs} godz. temu`
  return `${Math.floor(hrs / 24)} dni temu`
}
</script>
