<template>
  <ManagerLayout :title="$t('reports.staff_report')">
    <div class="space-y-5">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('reports.staff_report') }}</h1>
        <button @click="exportCsv" class="btn-ghost text-sm">
          <i class="fa-solid fa-download mr-1"></i> {{ $t('common.export_csv') }}
        </button>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
        <input v-model="filters.from" type="date" @change="apply" class="input-sm" />
        <input v-model="filters.to" type="date" @change="apply" class="input-sm" />
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">{{ $t('common.employee') }}</th>
              <th class="th text-right">{{ $t('common.hours') }}</th>
              <th class="th text-right">Billable</th>
              <th class="th text-right">{{ $t('common.tasks') }}</th>
              <th class="th text-right">{{ $t('common.completed') }}</th>
              <th class="th text-right">{{ $t('reports.completion_rate') }}</th>
              <th class="th text-right">{{ $t('common.projects') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="row in report" :key="row.user.id" class="hover:bg-gray-50">
              <td class="td">
                <div class="flex items-center gap-2.5">
                  <div
                    class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold"
                  >
                    {{ row.user.name?.charAt(0) }}
                  </div>
                  <span class="text-sm font-medium text-gray-900">{{ row.user.name }}</span>
                </div>
              </td>
              <td class="td text-right text-sm font-semibold text-gray-700">{{ row.total_hours }}h</td>
              <td class="td text-right text-sm text-indigo-600">{{ row.billable_hours }}h</td>
              <td class="td text-right text-sm text-gray-600">{{ row.total_tasks }}</td>
              <td class="td text-right text-sm text-green-600">{{ row.completed_tasks }}</td>
              <td class="td text-right">
                <div class="flex items-center justify-end gap-2">
                  <div class="w-20 bg-gray-100 rounded-full h-1.5">
                    <div class="bg-indigo-500 h-1.5 rounded-full" :style="{ width: row.completion_rate + '%' }"></div>
                  </div>
                  <span class="text-xs text-gray-500">{{ row.completion_rate }}%</span>
                </div>
              </td>
              <td class="td text-right text-sm text-gray-500">{{ row.projects }}</td>
            </tr>
          </tbody>
        </table>
        <div v-if="!report.length" class="py-16 text-center">
          <i class="fa-solid fa-users text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('reports.nothing_in_this_period') }}</p>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  report: { type: Array, default: () => [] },
  filters: Object,
})

const filters = reactive({ from: props.filters?.from ?? '', to: props.filters?.to ?? '' })

const apply = () => router.get(route('tenant.manager.reports.staff'), filters, { preserveState: true })
const exportCsv = () =>
  window.open(route('tenant.manager.reports.staff.export') + '?' + new URLSearchParams(filters), '_blank')
</script>
