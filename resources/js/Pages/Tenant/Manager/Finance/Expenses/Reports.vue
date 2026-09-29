<template>
  <ManagerLayout :title="$t('finance.expense_reports')">
    <div class="space-y-5">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('finance.expense_reports') }}</h1>
        <button @click="exportCsv" class="btn-ghost text-sm">
          <i class="fa-solid fa-download mr-1"></i> {{ $t('common.export_csv') }}
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
        <input v-model="filters.from" type="date" @change="apply" class="input-sm" />
        <input v-model="filters.to" type="date" @change="apply" class="input-sm" />
        <select v-model="filters.category_id" @change="apply" class="input-sm">
          <option value="">{{ $t('finance.all_categories') }}</option>
          <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
        <select v-model="filters.user_id" @change="apply" class="input-sm">
          <option value="">{{ $t('common.everyone') }}</option>
          <option v-for="u in staff" :key="u.id" :value="u.id">{{ u.name }}</option>
        </select>
      </div>

      <!-- Summary -->
      <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-sm text-gray-500">{{ $t('finance.total') }}</p>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ fmt(summary.total) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-sm text-gray-500">{{ $t('common.approved') }}</p>
          <p class="text-2xl font-bold text-green-600 mt-1">{{ fmt(summary.approved) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-sm text-gray-500">{{ $t('common.pending') }}</p>
          <p class="text-2xl font-bold text-yellow-600 mt-1">{{ fmt(summary.pending) }}</p>
        </div>
      </div>

      <!-- By category -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200">
          <h2 class="text-sm font-semibold text-gray-700">{{ $t('finance.spending_by_category') }}</h2>
        </div>
        <table class="min-w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">{{ $t('common.category') }}</th>
              <th class="th text-right">{{ $t('common.amount') }}</th>
              <th class="th text-right">{{ $t('common.quantity') }}</th>
              <th class="th">{{ $t('finance.share') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="row in byCategory" :key="row.category" class="hover:bg-gray-50">
              <td class="td text-sm font-medium text-gray-900">{{ row.category }}</td>
              <td class="td text-right text-sm font-semibold">{{ fmt(row.total) }}</td>
              <td class="td text-right text-sm text-gray-500">{{ row.count }}</td>
              <td class="td w-40">
                <div class="flex items-center gap-2">
                  <div class="flex-1 bg-gray-100 rounded-full h-1.5">
                    <div class="bg-indigo-500 h-1.5 rounded-full" :style="{ width: pct(row.total) + '%' }"></div>
                  </div>
                  <span class="text-xs text-gray-500">{{ pct(row.total) }}%</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!byCategory.length" class="py-12 text-center text-gray-400 text-sm">
          {{ $t('finance.no_expenses_in_this_period') }}
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { intlLocale } from '@/format'

const props = defineProps({
  expenses: { type: Array, default: () => [] },
  summary: { type: Object, default: () => ({}) },
  byCategory: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  staff: { type: Array, default: () => [] },
  filters: Object,
})

const filters = reactive({
  from: props.filters?.from ?? '',
  to: props.filters?.to ?? '',
  category_id: props.filters?.category_id ?? '',
  user_id: props.filters?.user_id ?? '',
})

const apply = () => router.get(route('tenant.manager.expenses.reports'), filters, { preserveState: true })

const exportCsv = () =>
  window.open(route('tenant.manager.expenses.export') + '?' + new URLSearchParams(filters), '_blank')

const fmt = (n) => new Intl.NumberFormat(intlLocale(), { style: 'currency', currency: 'PLN' }).format(n ?? 0)
const maxTotal = computed(() => Math.max(...props.byCategory.map((r) => r.total ?? 0), 1))
const pct = (val) => Math.round(((val ?? 0) / maxTotal.value) * 100)
</script>
