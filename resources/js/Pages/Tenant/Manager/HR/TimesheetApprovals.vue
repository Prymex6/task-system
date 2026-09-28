<template>
  <ManagerLayout :title="$t('hr.timesheets')">
    <div class="max-w-5xl space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('hr.timesheets') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ $t('hr.weekly_hours_waiting_for_approval') }}</p>
        </div>
        <select v-model="status" @change="filter" class="input-sm w-44">
          <option value="">{{ $t('common.all') }}</option>
          <option value="pending">{{ $t('common.pending') }}</option>
          <option value="approved">{{ $t('common.approved') }}</option>
          <option value="rejected">{{ $t('common.rejected_2') }}</option>
        </select>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">{{ $t('common.employee') }}</th>
              <th class="th">{{ $t('hr.week_starting') }}</th>
              <th class="th text-right">{{ $t('common.hours') }}</th>
              <th class="th">{{ $t('common.status') }}</th>
              <th v-if="canApprove" class="th text-right">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="sheet in timesheets.data" :key="sheet.id" class="hover:bg-gray-50">
              <td class="td text-gray-800">{{ sheet.user?.name ?? '—' }}</td>
              <td class="td text-gray-600">{{ formatDate(sheet.week_start) }}</td>
              <td class="td text-right tabular-nums">{{ sheet.total_hours }}</td>
              <td class="td">
                <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="badge(sheet.status)">
                  {{ label(sheet.status) }}
                </span>
                <p v-if="sheet.rejection_reason" class="text-xs text-gray-400 mt-0.5">
                  {{ sheet.rejection_reason }}
                </p>
              </td>
              <td v-if="canApprove" class="td text-right">
                <div v-if="sheet.status !== 'approved'" class="flex items-center justify-end gap-3">
                  <button @click="approve(sheet)" class="text-xs text-green-600 hover:text-green-800">
                    {{ $t('common.approve') }}
                  </button>
                  <button @click="reject(sheet)" class="text-xs text-red-400 hover:text-red-600">
                    {{ $t('common.reject') }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="!timesheets.data?.length" class="py-16 text-center">
          <i class="fa-solid fa-clock text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('hr.no_timesheets') }}</p>
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

const props = defineProps({
  timesheets: { type: Object, default: () => ({ data: [] }) },
  filters: { type: Object, default: () => ({}) },
  canApprove: Boolean,
})

const status = ref(props.filters.status ?? '')

const labels = { pending: t('common.pending_2'), approved: 'Zatwierdzona', rejected: 'Odrzucona' }
const badges = {
  pending: 'bg-yellow-100 text-yellow-700',
  approved: 'bg-green-100 text-green-700',
  rejected: 'bg-red-100 text-red-700',
}

const label = (s) => labels[s] ?? s
const badge = (s) => badges[s] ?? 'bg-gray-100 text-gray-600'
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')

const filter = () =>
  router.get(
    route('tenant.manager.timesheets.approvals'),
    { status: status.value },
    { preserveState: true, replace: true },
  )

const approve = (sheet) =>
  router.post(route('tenant.manager.timesheets.approvals.approve', sheet.id), {}, { preserveScroll: true })

const reject = (sheet) => {
  const reason = prompt(t('hr.reason_for_rejecting'))
  if (reason) {
    router.post(
      route('tenant.manager.timesheets.approvals.reject', sheet.id),
      { rejection_reason: reason },
      { preserveScroll: true },
    )
  }
}
</script>
