<template>
  <ManagerLayout :title="$t('time.timesheet_approvals')">
    <div class="space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('time.timesheet_approvals') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ pending.length }} {{ $t('time.waiting_for_approval') }}</p>
        </div>
        <div class="flex items-center gap-3">
          <select v-model="filters.status" @change="apply" class="input-sm">
            <option value="">{{ $t('common.all') }}</option>
            <option value="submitted">{{ $t('common.pending') }}</option>
            <option value="approved">{{ $t('common.approved') }}</option>
            <option value="rejected">{{ $t('common.rejected_2') }}</option>
          </select>
        </div>
      </div>

      <!-- Pending list -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">{{ $t('common.employee') }}</th>
              <th class="th">{{ $t('time.week') }}</th>
              <th class="th text-right">{{ $t('common.hours') }}</th>
              <th class="th">{{ $t('common.status') }}</th>
              <th class="th">{{ $t('time.submitted') }}</th>
              <th class="th text-right">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="approval in approvals.data" :key="approval.id" class="hover:bg-gray-50">
              <td class="td">
                <div class="flex items-center gap-2.5">
                  <div
                    class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold"
                  >
                    {{ approval.user?.name?.charAt(0) }}
                  </div>
                  <span class="text-sm font-medium text-gray-900">{{ approval.user?.name }}</span>
                </div>
              </td>
              <td class="td text-sm text-gray-700">{{ formatWeek(approval.week_start) }}</td>
              <td class="td text-right text-sm font-semibold">{{ approval.total_hours ?? '—' }}h</td>
              <td class="td">
                <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="statusClass(approval.status)">
                  {{ statusLabel(approval.status) }}
                </span>
              </td>
              <td class="td text-sm text-gray-500">{{ formatDate(approval.submitted_at) }}</td>
              <td class="td text-right">
                <div class="flex items-center justify-end gap-2" v-if="approval.status === 'submitted'">
                  <button @click="doApprove(approval)" class="btn-success-sm">
                    <i class="fa-solid fa-check mr-1"></i> {{ $t('common.approve') }}
                  </button>
                  <button @click="openRejectModal(approval)" class="btn-danger-sm">
                    <i class="fa-solid fa-xmark mr-1"></i> {{ $t('common.reject') }}
                  </button>
                </div>
                <span v-else class="text-xs text-gray-400">
                  {{ approval.approver?.name ?? '—' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="!approvals.data?.length" class="py-16 text-center">
          <i class="fa-solid fa-check-double text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('time.nothing_waiting_for_approval') }}</p>
        </div>
      </div>

      <!-- Reject modal -->
      <div
        v-if="rejectTarget"
        class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center"
        @click.self="rejectTarget = null"
      >
        <div class="bg-white rounded-xl shadow-xl w-96 p-5">
          <h3 class="font-semibold text-gray-900 mb-3">{{ $t('time.reject_timesheet') }}</h3>
          <textarea
            v-model="rejectReason"
            rows="3"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent"
            :placeholder="$t('time.reason_for_rejecting_optional')"
          ></textarea>
          <div class="flex justify-end gap-2 mt-4">
            <button @click="rejectTarget = null" class="btn-ghost text-sm">{{ $t('common.cancel') }}</button>
            <button @click="doReject" class="btn-danger text-sm">{{ $t('common.reject') }}</button>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import axios from 'axios'

const props = defineProps({
  approvals: Object,
  filters: Object,
})

const filters = reactive({ status: props.filters?.status ?? '' })
const rejectTarget = ref(null)
const rejectReason = ref('')

const pending = computed(() => (props.approvals?.data ?? []).filter((a) => a.status === 'submitted'))

const apply = () => {
  router.get(route('tenant.manager.timesheets.approvals'), filters, { preserveState: true, replace: true })
}

const doApprove = async (approval) => {
  await axios.post(route('tenant.manager.timesheets.approve', approval.id))
  router.reload()
}

const openRejectModal = (approval) => {
  rejectTarget.value = approval
  rejectReason.value = ''
}

const doReject = async () => {
  await axios.post(route('tenant.manager.timesheets.reject', rejectTarget.value.id), { reason: rejectReason.value })
  rejectTarget.value = null
  router.reload()
}

const formatWeek = (d) =>
  d ? new Date(d).toLocaleDateString('pl-PL', { day: 'numeric', month: 'long', year: 'numeric' }) : '—'
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')

const statusClass = (s) =>
  ({
    submitted: 'bg-yellow-100 text-yellow-700',
    approved: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-700',
  })[s] ?? 'bg-gray-100 text-gray-600'

const statusLabel = (s) =>
  ({
    submitted: 'Oczekuje',
    approved: 'Zatwierdzone',
    rejected: 'Odrzucone',
  })[s] ?? s
</script>
