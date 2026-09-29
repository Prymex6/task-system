<template>
  <LandlordLayout :title="$t('platform.statistics')">
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('platform.platform_statistics') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $t('platform.every_tenant_and_what_they_are') }}</p>
      </div>

      <!-- KPI cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="flex items-center justify-between mb-3">
            <span class="text-sm text-gray-500">{{ $t('platform.tenants') }}</span>
            <i class="fa-solid fa-building text-indigo-400"></i>
          </div>
          <p class="text-3xl font-bold text-gray-900">{{ stats.total_tenants }}</p>
          <p class="text-xs text-green-600 mt-1">+{{ stats.new_tenants_month }} {{ $t('platform.this_month') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="flex items-center justify-between mb-3">
            <span class="text-sm text-gray-500">{{ $t('platform.active_users') }}</span>
            <i class="fa-solid fa-users text-blue-400"></i>
          </div>
          <p class="text-3xl font-bold text-gray-900">{{ stats.active_users }}</p>
          <p class="text-xs text-gray-400 mt-1">{{ $t('platform.signed_in_over_the_last_30') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="flex items-center justify-between mb-3">
            <span class="text-sm text-gray-500">{{ $t('platform.mrr') }}</span>
            <i class="fa-solid fa-dollar-sign text-green-400"></i>
          </div>
          <p class="text-3xl font-bold text-gray-900">{{ formatMoney(stats.mrr) }}</p>
          <p class="text-xs text-gray-400 mt-1">Monthly Recurring Revenue</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="flex items-center justify-between mb-3">
            <span class="text-sm text-gray-500">{{ $t('platform.tasks_in_total') }}</span>
            <i class="fa-solid fa-list-check text-orange-400"></i>
          </div>
          <p class="text-3xl font-bold text-gray-900">{{ stats.total_tasks?.toLocaleString() }}</p>
          <p class="text-xs text-gray-400 mt-1">{{ $t('platform.across_all_tenants') }}</p>
        </div>
      </div>

      <!-- Plans distribution -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('platform.plans_in_use') }}</h2>
          <div class="space-y-3">
            <div v-for="plan in planDistribution" :key="plan.name" class="flex items-center gap-3">
              <div class="w-20 text-xs text-gray-600 font-medium">{{ plan.name }}</div>
              <div class="flex-1 bg-gray-100 rounded-full h-2">
                <div class="h-2 rounded-full bg-indigo-500" :style="{ width: planWidth(plan) + '%' }"></div>
              </div>
              <div class="w-12 text-right text-sm font-semibold text-gray-900">{{ plan.count }}</div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('platform.tenant_growth_last_6_months') }}</h2>
          <div class="flex items-end gap-1.5 h-32">
            <div v-for="m in growthData" :key="m.month" class="flex-1 flex flex-col items-center gap-1">
              <div class="w-full bg-indigo-500 rounded-t" :style="{ height: barHeight(m.count) + 'px' }"></div>
              <span class="text-xs text-gray-400">{{ m.label }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Tenants table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-gray-700">{{ $t('platform.recently_active_organisations') }}</h2>
          <Link :href="route('landlord.tenants.index')" class="text-sm text-indigo-600 hover:text-indigo-800">
            {{ $t('common.all') }} <i class="fa-solid fa-arrow-right ml-0.5 text-xs"></i>
          </Link>
        </div>
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="th">{{ $t('platform.organisation') }}</th>
              <th class="th text-center">Plan</th>
              <th class="th text-right">{{ $t('platform.users') }}</th>
              <th class="th text-right">{{ $t('common.tasks') }}</th>
              <th class="th">{{ $t('platform.last_active') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="tenant in recentTenants" :key="tenant.id" class="hover:bg-gray-50">
              <td class="td">
                <div class="flex items-center gap-2">
                  <div
                    class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold"
                  >
                    {{ tenant.name?.charAt(0)?.toUpperCase() }}
                  </div>
                  <div>
                    <p class="font-medium text-gray-900">{{ tenant.name }}</p>
                    <p class="text-xs text-gray-400">{{ tenant.domain }}</p>
                  </div>
                </div>
              </td>
              <td class="td text-center">
                <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="planClass(tenant.plan)">{{
                  tenant.plan
                }}</span>
              </td>
              <td class="td text-right text-gray-600">{{ tenant.users_count ?? '—' }}</td>
              <td class="td text-right text-gray-600">{{ tenant.tasks_count ?? '—' }}</td>
              <td class="td text-gray-400">{{ formatDate(tenant.last_activity_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </LandlordLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { intlLocale } from '@/format'

const props = defineProps({
  stats: { type: Object, default: () => ({}) },
  planDistribution: { type: Array, default: () => [] },
  growthData: { type: Array, default: () => [] },
  recentTenants: { type: Array, default: () => [] },
})

const planWidth = (plan) => {
  const max = Math.max(...props.planDistribution.map((p) => p.count), 1)
  return Math.round((plan.count / max) * 100)
}

const maxGrowth = computed(() => Math.max(...props.growthData.map((m) => m.count), 1))
const barHeight = (count) => Math.round((count / maxGrowth.value) * 100)

const planClass = (p) =>
  ({
    free: 'bg-gray-100 text-gray-600',
    starter: 'bg-blue-100 text-blue-700',
    pro: 'bg-indigo-100 text-indigo-700',
    enterprise: 'bg-purple-100 text-purple-700',
  })[p?.toLowerCase()] ?? 'bg-gray-100 text-gray-600'

const formatDate = (d) => (d ? new Date(d).toLocaleDateString(intlLocale()) : '—')
const formatMoney = (v) =>
  v != null
    ? new Intl.NumberFormat(intlLocale(), { style: 'currency', currency: 'PLN', maximumFractionDigits: 0 }).format(v)
    : '—'
</script>
