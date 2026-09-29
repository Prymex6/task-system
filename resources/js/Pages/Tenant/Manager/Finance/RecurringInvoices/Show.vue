<template>
  <ManagerLayout :title="recurring.title">
    <div class="max-w-3xl space-y-5">
      <div class="flex items-start justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ recurring.title }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">
            {{ recurring.client?.name ?? $t('common.no_client') }} · {{ cycle }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <button class="btn-primary" @click="generate">{{ $t('finance.issue_now') }}</button>
          <Link :href="route('tenant.manager.recurring-invoices.edit', recurring.id)" class="btn-ghost">
            {{ $t('common.edit') }}
          </Link>
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-3">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500">{{ $t('finance.next_invoice') }}</p>
          <p class="text-lg font-semibold text-gray-900 mt-1">{{ date(recurring.next_date) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500">{{ $t('finance.ends') }}</p>
          <p class="text-lg font-semibold text-gray-900 mt-1">
            {{ recurring.ends_at ? date(recurring.ends_at) : $t('common.no_end_date_2') }}
          </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500">{{ $t('common.status') }}</p>
          <p class="text-lg font-semibold mt-1" :class="recurring.is_active ? 'text-green-600' : 'text-gray-500'">
            {{ recurring.is_active ? $t('platform.active') : $t('common.paused') }}
          </p>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200">
          <h2 class="text-sm font-semibold text-gray-700">{{ $t('finance.template_items') }}</h2>
        </div>
        <table class="min-w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">{{ $t('common.description') }}</th>
              <th class="th text-right">{{ $t('common.quantity') }}</th>
              <th class="th text-right">{{ $t('common.price') }}</th>
              <th class="th text-right">VAT</th>
              <th class="th text-right">{{ $t('common.value') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="(item, i) in items" :key="i">
              <td class="td text-gray-800">{{ item.description }}</td>
              <td class="td text-right tabular-nums">{{ item.quantity }}</td>
              <td class="td text-right tabular-nums">{{ fmt(item.unit_price) }}</td>
              <td class="td text-right tabular-nums">{{ item.tax_rate ?? 0 }}%</td>
              <td class="td text-right tabular-nums font-medium">{{ fmt(lineTotal(item)) }}</td>
            </tr>
          </tbody>
        </table>
        <div class="p-4 border-t border-gray-200 text-right">
          <span class="text-sm text-gray-500">{{ $t('common.gross_total') }}</span>
          <span class="text-lg font-bold text-gray-900 ml-2">{{ fmt(total) }}</span>
        </div>
      </div>

      <p v-if="recurring.template_data?.notes" class="text-sm text-gray-600">
        {{ recurring.template_data.notes }}
      </p>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { intlLocale } from '@/format'

const props = defineProps({ recurring: { type: Object, required: true } })

const labels = { weekly: t('common.week'), monthly: t('common.month'), quarterly: t('common.quarter'), yearly: 'rok' }

const cycle = computed(() =>
  props.recurring.interval > 1
    ? `co ${props.recurring.interval} × ${labels[props.recurring.frequency]}`
    : `co ${labels[props.recurring.frequency]}`,
)

const items = computed(() => props.recurring.template_data?.items ?? [])

const currency = computed(() => props.recurring.template_data?.currency ?? 'PLN')

const lineTotal = (item) =>
  (Number(item.quantity) || 0) * (Number(item.unit_price) || 0) * (1 + (Number(item.tax_rate) || 0) / 100)

const total = computed(() => items.value.reduce((sum, i) => sum + lineTotal(i), 0))

const fmt = (v) => new Intl.NumberFormat(intlLocale(), { style: 'currency', currency: currency.value }).format(v || 0)

const date = (d) => (d ? new Date(d).toLocaleDateString(intlLocale()) : '—')

const generate = () => {
  if (confirm(t('finance.issue_an_invoice_from_this_schedule'))) {
    router.post(route('tenant.manager.recurring-invoices.generate', props.recurring.id))
  }
}
</script>
