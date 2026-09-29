<template>
  <ManagerLayout :title="$t('common.finance')">
    <div class="space-y-5">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('finance.finance_overview') }}</h1>
        <div class="flex items-center gap-2">
          <select v-model="year" @change="load" class="input-sm">
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
          <select v-model="month" @change="load" class="input-sm">
            <option value="">{{ $t('finance.whole_year') }}</option>
            <option v-for="(m, i) in months" :key="i" :value="i + 1">{{ m }}</option>
          </select>
        </div>
      </div>

      <!-- KPI cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <div class="flex items-center justify-between mb-1">
            <p class="text-sm text-gray-500">{{ $t('finance.revenue') }}</p>
            <i class="fa-solid fa-arrow-trend-up text-green-500"></i>
          </div>
          <p class="text-2xl font-bold text-gray-900">{{ fmt(stats.revenue) }}</p>
          <p class="text-xs text-gray-400 mt-1">{{ $t('finance.paid_invoices') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <div class="flex items-center justify-between mb-1">
            <p class="text-sm text-gray-500">{{ $t('common.pending') }}</p>
            <i class="fa-solid fa-clock text-yellow-500"></i>
          </div>
          <p class="text-2xl font-bold text-yellow-600">{{ fmt(stats.pending) }}</p>
          <p class="text-xs text-gray-400 mt-1">{{ $t('finance.issued_unpaid') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <div class="flex items-center justify-between mb-1">
            <p class="text-sm text-gray-500">{{ $t('common.overdue') }}</p>
            <i class="fa-solid fa-circle-exclamation text-red-500"></i>
          </div>
          <p class="text-2xl font-bold text-red-600">{{ fmt(stats.overdue) }}</p>
          <p class="text-xs text-gray-400 mt-1">{{ $t('finance.past_the_due_date') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <div class="flex items-center justify-between mb-1">
            <p class="text-sm text-gray-500">{{ $t('finance.net_profit') }}</p>
            <i class="fa-solid fa-coins text-indigo-500"></i>
          </div>
          <p class="text-2xl font-bold" :class="stats.profit >= 0 ? 'text-green-600' : 'text-red-600'">
            {{ fmt(stats.profit) }}
          </p>
          <p class="text-xs text-gray-400 mt-1">{{ $t('finance.revenue_minus_spending') }}</p>
        </div>
      </div>

      <!-- Monthly revenue chart -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('finance.revenue_by_month') }}</h2>
        <div class="flex items-end gap-2 h-40">
          <div v-for="m in stats.by_month" :key="m.month" class="flex-1 flex flex-col items-center gap-1">
            <div
              class="w-full bg-indigo-100 rounded-t relative"
              :style="{ height: barH(m.paid) + 'px' }"
              :title="$t('common.paid_2') + fmt(m.paid)"
            >
              <div
                class="absolute bottom-0 w-full bg-indigo-500 rounded-t"
                :style="{ height: barH(m.paid) + 'px' }"
              ></div>
            </div>
            <span class="text-xs text-gray-400">{{ m.month?.slice(5) }}</span>
          </div>
        </div>
      </div>

      <!-- Top clients -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200">
          <h2 class="text-sm font-semibold text-gray-700">{{ $t('finance.top_clients_by_revenue') }}</h2>
        </div>
        <table class="min-w-full">
          <tbody class="divide-y divide-gray-100">
            <tr v-for="row in stats.by_client" :key="row.client?.id" class="hover:bg-gray-50">
              <td class="td text-sm font-medium text-gray-900">{{ row.client?.name }}</td>
              <td class="td text-right text-sm text-gray-700">
                {{ $t('finance.issued_2') }} <span class="font-semibold">{{ fmt(row.invoiced) }}</span>
              </td>
              <td class="td text-right text-sm text-green-600">
                {{ $t('finance.paid_2') }} <span class="font-semibold">{{ fmt(row.paid) }}</span>
              </td>
              <td class="td">
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                  <div
                    class="bg-indigo-500 h-1.5 rounded-full"
                    :style="{ width: pct(row.paid, row.invoiced) + '%' }"
                  ></div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!stats.by_client?.length" class="py-12 text-center text-gray-400 text-sm">
          {{ $t('common.nothing_here_yet') }}
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { intlLocale } from '@/format'

const props = defineProps({
  stats: { type: Object, default: () => ({}) },
  filters: Object,
})

const now = new Date()
const year = ref(props.filters?.year ?? now.getFullYear())
const month = ref(props.filters?.month ?? '')
const years = Array.from({ length: 5 }, (_, i) => now.getFullYear() - i)
const months = [
  t('common.january'),
  'Luty',
  'Marzec',
  t('common.april'),
  'Maj',
  'Czerwiec',
  'Lipiec',
  t('common.august'),
  t('common.september'),
  t('common.october'),
  'Listopad',
  t('common.december'),
]

const load = () =>
  router.get(
    route('tenant.manager.finance.overview'),
    { year: year.value, month: month.value },
    { preserveState: true },
  )

const fmt = (n) => new Intl.NumberFormat(intlLocale(), { style: 'currency', currency: 'PLN' }).format(n ?? 0)
const maxPaid = () => Math.max(...(props.stats.by_month ?? []).map((m) => m.paid ?? 0), 1)
const barH = (val) => Math.max(4, Math.round(((val ?? 0) / maxPaid()) * 128))
const pct = (a, b) => (b > 0 ? Math.min(100, Math.round((a / b) * 100)) : 0)
</script>
