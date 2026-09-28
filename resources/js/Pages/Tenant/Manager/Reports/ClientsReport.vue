<template>
  <ManagerLayout :title="$t('reports.client_report')">
    <div class="space-y-5">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('reports.client_report') }}</h1>
        <button @click="exportCsv" class="btn-ghost text-sm">
          <i class="fa-solid fa-download mr-1"></i> {{ $t('common.export_csv') }}
        </button>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">{{ $t('common.client') }}</th>
              <th class="th text-right">{{ $t('common.projects') }}</th>
              <th class="th text-right">{{ $t('reports.issued') }}</th>
              <th class="th text-right">{{ $t('reports.paid') }}</th>
              <th class="th text-right">{{ $t('reports.outstanding') }}</th>
              <th class="th text-right">{{ $t('reports.tickets') }}</th>
              <th class="th text-right">{{ $t('common.deals') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="row in report" :key="row.client.id" class="hover:bg-gray-50">
              <td class="td">
                <Link
                  :href="route('tenant.manager.clients.show', row.client.id)"
                  class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                >
                  {{ row.client.name }}
                </Link>
                <p class="text-xs text-gray-400">{{ row.client.email }}</p>
              </td>
              <td class="td text-right text-sm">
                <span class="font-semibold text-gray-700">{{ row.total_projects }}</span>
                <span class="text-gray-400 ml-1">({{ row.open_projects }} {{ $t('reports.active') }}</span>
              </td>
              <td class="td text-right text-sm font-semibold text-gray-700">{{ fmt(row.total_invoiced) }}</td>
              <td class="td text-right text-sm font-semibold text-green-600">{{ fmt(row.total_paid) }}</td>
              <td
                class="td text-right text-sm"
                :class="row.outstanding > 0 ? 'text-red-500 font-semibold' : 'text-gray-400'"
              >
                {{ row.outstanding > 0 ? fmt(row.outstanding) : '—' }}
              </td>
              <td class="td text-right text-sm text-gray-600">{{ row.open_tickets }}</td>
              <td class="td text-right text-sm text-gray-600">{{ row.deals }}</td>
            </tr>
          </tbody>
        </table>
        <div v-if="!report.length" class="py-16 text-center">
          <i class="fa-solid fa-building text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('common.nothing_here_yet') }}</p>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  report: { type: Array, default: () => [] },
  filters: Object,
})

const fmt = (n) => new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(n ?? 0)
const exportCsv = () => window.open(route('tenant.manager.reports.clients.export'), '_blank')
</script>
