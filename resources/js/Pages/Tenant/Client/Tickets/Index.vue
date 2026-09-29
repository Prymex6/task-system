<template>
  <ClientLayout :title="$t('portal.tickets')">
    <div class="space-y-5">
      <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">{{ $t('portal.tickets') }}</h1>
        <Link :href="route('tenant.portal.tickets.create')" class="btn-primary btn-sm">
          <i class="fa-solid fa-plus"></i> {{ $t('common.new_ticket') }}
        </Link>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="th">#</th>
              <th class="th">{{ $t('common.subject') }}</th>
              <th class="th">{{ $t('common.department') }}</th>
              <th class="th">{{ $t('common.priority') }}</th>
              <th class="th">{{ $t('common.status') }}</th>
              <th class="th">{{ $t('common.date') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="!tickets.data?.length">
              <td colspan="6" class="td text-center text-gray-400 py-8">{{ $t('platform.no_tickets') }}</td>
            </tr>
            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-gray-50">
              <td class="td text-xs text-gray-400">#{{ ticket.id }}</td>
              <td class="td">
                <Link
                  :href="route('tenant.portal.tickets.show', ticket.id)"
                  class="font-medium text-gray-800 hover:text-indigo-600"
                >
                  {{ ticket.subject }}
                </Link>
              </td>
              <td class="td text-xs text-gray-500">{{ ticket.department }}</td>
              <td class="td">
                <span class="badge text-xs" :class="priorityClass(ticket.priority)">{{
                  priorityLabel(ticket.priority)
                }}</span>
              </td>
              <td class="td">
                <span class="badge text-xs" :class="ticketStatusClass(ticket.status)">{{
                  ticketStatusLabel(ticket.status)
                }}</span>
              </td>
              <td class="td text-xs text-gray-400">{{ formatDate(ticket.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <Pagination :links="tickets.links" />
    </div>
  </ClientLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { Link } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { intlLocale } from '@/format'

const props = defineProps({ tickets: Object })

const priorityLabel = (p) => ({ low: 'Niski', medium: t('common.medium'), high: 'Wysoki', urgent: 'Pilny' })[p] ?? p
const priorityClass = (p) =>
  ({ low: 'badge-gray', medium: 'badge-blue', high: 'badge-yellow', urgent: 'badge-red' })[p] ?? 'badge-gray'
const ticketStatusLabel = (s) =>
  ({ open: 'Otwarty', in_progress: 'W trakcie', resolved: t('common.resolved_2'), closed: t('common.closed_2') })[s] ??
  s
const ticketStatusClass = (s) =>
  ({ open: 'badge-blue', in_progress: 'badge-indigo', resolved: 'badge-green', closed: 'badge-gray' })[s] ??
  'badge-gray'
const formatDate = (d) => (d ? new Date(d).toLocaleDateString(intlLocale()) : '—')
</script>
