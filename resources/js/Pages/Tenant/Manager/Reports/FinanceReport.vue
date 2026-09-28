<template>
  <ManagerLayout :title="$t('reports.finance_report')">
    <div class="space-y-5">
      <div class="page-header">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.reports.index')" class="btn-ghost btn-sm"
            ><i class="fa-solid fa-arrow-left"></i
          ></Link>
          <h1 class="page-title">{{ $t('reports.finance_report') }}</h1>
        </div>
        <select v-model="year" class="select input-sm w-24" @change="changeYear">
          <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>

      <!-- Summary cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="stat-card">
          <div class="stat-icon bg-emerald-100 text-emerald-600"><i class="fa-solid fa-circle-dollar-to-slot"></i></div>
          <div>
            <div class="stat-value">{{ formatMoney(summary.revenue) }}</div>
            <div class="stat-label">{{ $t('common.revenue') }} {{ year }}</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-blue-100 text-blue-600"><i class="fa-solid fa-file-invoice-dollar"></i></div>
          <div>
            <div class="stat-value">{{ formatMoney(summary.invoiced) }}</div>
            <div class="stat-label">{{ $t('reports.invoices_issued') }}</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-amber-100 text-amber-600"><i class="fa-solid fa-clock-rotate-left"></i></div>
          <div>
            <div class="stat-value">{{ formatMoney(summary.outstanding) }}</div>
            <div class="stat-label">{{ $t('common.outstanding') }}</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-red-100 text-red-600"><i class="fa-solid fa-receipt"></i></div>
          <div>
            <div class="stat-value">{{ formatMoney(summary.expenses) }}</div>
            <div class="stat-label">{{ $t('common.expenses') }}</div>
          </div>
        </div>
      </div>

      <!-- Monthly chart -->
      <div class="card p-6">
        <h2 class="section-title">{{ $t('reports.revenue_and_spending_by_month') }} {{ year }}</h2>
        <div class="mt-4 flex items-end gap-2 h-40">
          <div
            v-for="month in monthlyData"
            :key="month.month"
            class="flex-1 flex flex-col items-center gap-0.5 group"
            :title="`${month.label}\nPrzychód: ${formatMoney(month.revenue)}\nWydatki: ${formatMoney(month.expenses)}`"
          >
            <div class="w-full flex flex-col justify-end gap-0.5 h-32">
              <div
                class="w-full bg-emerald-400 rounded-t-sm"
                :style="{ height: maxRevenue > 0 ? (month.revenue / maxRevenue) * 100 + 'px' : '2px' }"
              ></div>
              <div
                class="w-full bg-red-300 rounded-t-sm"
                :style="{ height: maxRevenue > 0 ? (month.expenses / maxRevenue) * 100 + 'px' : '2px' }"
              ></div>
            </div>
            <span class="text-xs text-gray-400">{{ month.label }}</span>
          </div>
        </div>
        <div class="flex gap-4 mt-3 text-xs text-gray-400">
          <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-sm bg-emerald-400"></div>
            {{ $t('common.revenue') }}
          </div>
          <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-sm bg-red-300"></div>
            {{ $t('common.expenses') }}
          </div>
        </div>
      </div>

      <!-- Monthly table -->
      <div class="card">
        <div class="card-header">
          <h2 class="section-title mb-0">{{ $t('reports.month_by_month') }}</h2>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th class="th">{{ $t('reports.month') }}</th>
              <th class="th">{{ $t('common.invoices') }}</th>
              <th class="th">{{ $t('common.revenue') }}</th>
              <th class="th">{{ $t('common.expenses') }}</th>
              <th class="th">{{ $t('reports.result') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="m in monthlyData" :key="m.month" class="tr-hover">
              <td class="td font-medium">{{ m.label }}</td>
              <td class="td text-gray-600">{{ m.invoices_count }}</td>
              <td class="td text-emerald-600 font-medium">{{ formatMoney(m.revenue) }}</td>
              <td class="td text-red-500 font-medium">{{ formatMoney(m.expenses) }}</td>
              <td class="td font-bold" :class="m.revenue - m.expenses >= 0 ? 'text-emerald-700' : 'text-red-600'">
                {{ formatMoney(m.revenue - m.expenses) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  monthlyData: { type: Array, default: () => [] },
  summary: { type: Object, default: () => ({ revenue: 0, invoiced: 0, outstanding: 0, expenses: 0 }) },
  year: { type: Number, default: new Date().getFullYear() },
  years: { type: Array, default: () => [] },
})

const year = ref(props.year)
const changeYear = () => router.get(route('tenant.manager.reports.finance'), { year: year.value })

const maxRevenue = computed(() => Math.max(...props.monthlyData.map((m) => Math.max(m.revenue, m.expenses)), 1))

const formatMoney = (v) => new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(v ?? 0)
</script>
