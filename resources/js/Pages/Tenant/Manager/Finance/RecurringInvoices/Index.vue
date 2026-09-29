<template>
  <ManagerLayout :title="$t('finance.recurring_invoices')">
    <div class="max-w-5xl space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('finance.recurring_invoices') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ $t('finance.standing_orders_that_issue_their_own') }}</p>
        </div>
        <Link :href="route('tenant.manager.recurring-invoices.create')" class="btn-primary">
          {{ $t('finance.new_schedule') }}
        </Link>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">{{ $t('common.title') }}</th>
              <th class="th">{{ $t('common.client') }}</th>
              <th class="th">{{ $t('finance.cycle') }}</th>
              <th class="th">{{ $t('finance.next') }}</th>
              <th class="th">{{ $t('common.status') }}</th>
              <th class="th text-right">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="r in recurring.data" :key="r.id" class="hover:bg-gray-50">
              <td class="td">
                <Link
                  :href="route('tenant.manager.recurring-invoices.show', r.id)"
                  class="text-indigo-600 hover:text-indigo-800 font-medium"
                >
                  {{ r.title }}
                </Link>
              </td>
              <td class="td text-gray-600">{{ r.client?.name ?? '—' }}</td>
              <td class="td text-gray-600">{{ cycle(r) }}</td>
              <td class="td text-gray-600 tabular-nums">{{ date(r.next_date) }}</td>
              <td class="td">
                <span
                  class="text-xs px-2 py-0.5 rounded-full font-medium"
                  :class="r.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                >
                  {{ r.is_active ? $t('platform.active') : $t('common.paused') }}
                </span>
              </td>
              <td class="td text-right">
                <button class="text-xs text-indigo-600 hover:text-indigo-800" @click="generate(r)">
                  {{ $t('finance.issue_now') }}
                </button>
                <Link
                  :href="route('tenant.manager.recurring-invoices.edit', r.id)"
                  class="text-xs text-gray-500 hover:text-gray-700 ml-3"
                  >{{ $t('common.edit') }}</Link
                >
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="!recurring.data?.length" class="py-16 text-center">
          <i class="fa-solid fa-repeat text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('finance.no_recurring_invoices') }}</p>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { intlLocale } from '@/format'

defineProps({ recurring: { type: Object, default: () => ({ data: [] }) } })

const labels = { weekly: t('common.week'), monthly: t('common.month'), quarterly: t('common.quarter'), yearly: 'rok' }

const cycle = (r) => (r.interval > 1 ? `co ${r.interval} × ${labels[r.frequency]}` : `co ${labels[r.frequency]}`)

const date = (d) => (d ? new Date(d).toLocaleDateString(intlLocale()) : '—')

const generate = (r) => {
  if (confirm(t('finance.issue_an_invoice_from_this_schedule'))) {
    router.post(route('tenant.manager.recurring-invoices.generate', r.id))
  }
}
</script>
