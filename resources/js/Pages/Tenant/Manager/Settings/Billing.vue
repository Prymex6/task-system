<template>
  <ManagerLayout :title="$t('settings.plan_and_billing')">
    <div class="max-w-2xl space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('settings.plan_and_billing') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $t('settings.your_subscription_to_this_platform') }}</p>
      </div>

      <!-- Current plan -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h2 class="font-semibold text-gray-900">{{ $t('settings.current_plan') }}</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ tenant?.plan?.name ?? $t('common.free') }}</p>
          </div>
          <span class="text-2xl font-bold text-indigo-600">
            {{ tenant?.plan?.price ? fmt(tenant.plan.price) + $t('finance.per_month') : $t('platform.free_of_charge') }}
          </span>
        </div>

        <div class="space-y-2 mb-5">
          <div v-for="feature in currentFeatures" :key="feature" class="flex items-center gap-2 text-sm text-gray-700">
            <i class="fa-solid fa-check text-green-500"></i>
            {{ feature }}
          </div>
        </div>

        <div class="flex items-center justify-between text-sm text-gray-500 border-t border-gray-100 pt-4">
          <span
            >{{ $t('settings.active_until') }}
            {{ formatDate(tenant?.trial_ends_at ?? tenant?.subscription_ends_at) }}</span
          >
          <span :class="tenant?.is_active ? 'text-green-600' : 'text-red-600'" class="font-medium">
            {{ tenant?.is_active ? $t('common.active') : $t('common.inactive') }}
          </span>
        </div>
      </div>

      <!-- Available plans -->
      <div>
        <h2 class="font-semibold text-gray-900 mb-3">{{ $t('platform.change_plan') }}</h2>
        <div class="grid grid-cols-2 gap-4">
          <div
            v-for="plan in plans"
            :key="plan.id"
            class="bg-white rounded-xl border p-5 cursor-pointer hover:border-indigo-400 transition-colors"
            :class="tenant?.plan_id === plan.id ? 'border-indigo-500 ring-2 ring-indigo-200' : 'border-gray-200'"
          >
            <div class="flex items-start justify-between mb-3">
              <div>
                <h3 class="font-semibold text-gray-900">{{ plan.name }}</h3>
                <p class="text-xs text-gray-500 mt-0.5">{{ plan.description }}</p>
              </div>
              <span class="font-bold text-gray-900">
                {{ plan.price > 0 ? fmt(plan.price) : $t('platform.free_of_charge') }}
              </span>
            </div>
            <div class="space-y-1.5 mb-4">
              <div v-for="f in plan.features ?? []" :key="f" class="text-xs text-gray-600 flex items-center gap-1.5">
                <i class="fa-solid fa-check text-green-500 text-xs"></i> {{ f }}
              </div>
            </div>
            <button v-if="tenant?.plan_id !== plan.id" @click="changePlan(plan)" class="btn-primary text-sm w-full">
              {{ $t('settings.choose') }} {{ plan.name }}
            </button>
            <button v-else class="w-full text-sm text-center py-2 rounded-lg bg-indigo-50 text-indigo-600 font-medium">
              {{ $t('settings.current_plan') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Billing history -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200">
          <h2 class="text-sm font-semibold text-gray-700">{{ $t('finance.payment_history') }}</h2>
        </div>
        <div v-if="!invoices.length" class="py-10 text-center text-sm text-gray-400">
          {{ $t('settings.no_payment_history') }}
        </div>
        <table v-else class="min-w-full">
          <tbody class="divide-y divide-gray-100">
            <tr v-for="inv in invoices" :key="inv.id">
              <td class="td text-sm text-gray-700">{{ formatDate(inv.date) }}</td>
              <td class="td text-sm text-gray-700">{{ inv.description }}</td>
              <td class="td text-right text-sm font-semibold">{{ fmt(inv.amount) }}</td>
              <td class="td text-right">
                <a
                  v-if="inv.pdf_url"
                  :href="inv.pdf_url"
                  target="_blank"
                  class="text-xs text-indigo-600 hover:text-indigo-800"
                >
                  <i class="fa-solid fa-download mr-0.5"></i> PDF
                </a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { intlLocale } from '@/format'

const props = defineProps({
  tenant: Object,
  plans: { type: Array, default: () => [] },
  invoices: { type: Array, default: () => [] },
})

const currentFeatures = computed(() => props.tenant?.plan?.features ?? [])

const changePlan = (plan) => {
  if (confirm(`Zmienić plan na ${plan.name}?`)) {
    router.post(route('tenant.manager.settings.billing.change-plan'), { plan_id: plan.id })
  }
}

const fmt = (n) => new Intl.NumberFormat(intlLocale(), { style: 'currency', currency: 'PLN' }).format(n ?? 0)
const formatDate = (d) => (d ? new Date(d).toLocaleDateString(intlLocale()) : '—')
</script>
