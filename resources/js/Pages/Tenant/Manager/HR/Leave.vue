<template>
  <ManagerLayout :title="$t('hr.leave')">
    <div class="space-y-5">
      <div class="page-header">
        <div>
          <h1 class="page-title">{{ $t('hr.leave_and_absence') }}</h1>
        </div>
        <button @click="showRequest = true" class="btn-primary">
          <i class="fa-solid fa-plus"></i> {{ $t('hr.leave_request') }}
        </button>
      </div>

      <!-- My balance -->
      <div v-if="myBalance" class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div v-for="balance in myBalance" :key="balance.leave_type" class="stat-card">
          <div class="stat-icon bg-blue-100 text-blue-600"><i class="fa-solid fa-umbrella-beach"></i></div>
          <div>
            <div class="stat-value">{{ balance.remaining }}</div>
            <div class="stat-label">{{ balance.leave_type }}</div>
            <div class="text-xs text-gray-400">{{ balance.used }}/{{ balance.total }} {{ $t('hr.days_3') }}</div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filter-bar">
        <select v-model="filters.status" class="select input-sm w-36" @change="search">
          <option value="">{{ $t('common.all') }}</option>
          <option value="pending">{{ $t('common.pending') }}</option>
          <option value="approved">{{ $t('common.approved') }}</option>
          <option value="rejected">{{ $t('common.rejected_2') }}</option>
        </select>
        <select v-if="canApprove" v-model="filters.user_id" class="select input-sm w-40" @change="search">
          <option value="">{{ $t('common.everyone') }}</option>
          <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
        </select>
      </div>

      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th class="th">{{ $t('common.employee') }}</th>
              <th class="th">{{ $t('common.type') }}</th>
              <th class="th">{{ $t('hr.from') }}</th>
              <th class="th">{{ $t('hr.to') }}</th>
              <th class="th">{{ $t('hr.days_2') }}</th>
              <th class="th">{{ $t('common.status') }}</th>
              <th class="th">{{ $t('finance.reason') }}</th>
              <th class="th"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="!leaves.data?.length">
              <td colspan="8" class="td text-center text-gray-400 py-8">{{ $t('hr.no_requests') }}</td>
            </tr>
            <tr v-for="leave in leaves.data" :key="leave.id" class="tr-hover">
              <td class="td font-medium text-gray-800">{{ leave.user?.name }}</td>
              <td class="td text-gray-600">{{ leave.leave_type }}</td>
              <td class="td text-xs text-gray-500">{{ formatDate(leave.start_date) }}</td>
              <td class="td text-xs text-gray-500">{{ formatDate(leave.end_date) }}</td>
              <td class="td text-sm font-semibold">{{ leave.working_days }}</td>
              <td class="td">
                <span :class="leaveStatusClass(leave.status)" class="badge text-xs">{{
                  leaveStatusLabel(leave.status)
                }}</span>
              </td>
              <td class="td text-xs text-gray-500 max-w-32 truncate">{{ leave.reason }}</td>
              <td class="td">
                <div v-if="canApprove && leave.status === 'pending'" class="flex gap-1">
                  <button
                    @click="approve(leave.id)"
                    class="btn-ghost btn-sm text-emerald-600"
                    :title="$t('common.approve')"
                  >
                    <i class="fa-solid fa-check"></i>
                  </button>
                  <button @click="reject(leave.id)" class="btn-ghost btn-sm text-red-500" :title="$t('common.reject')">
                    <i class="fa-solid fa-times"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <Pagination :links="leaves.links" />
    </div>

    <!-- Request Leave Modal -->
    <div v-if="showRequest" class="modal-backdrop" @click.self="showRequest = false">
      <div class="modal-md">
        <div class="modal-header">
          <h3 class="modal-title">{{ $t('hr.leave_request') }}</h3>
          <button @click="showRequest = false" class="btn-ghost btn-sm"><i class="fa-solid fa-times"></i></button>
        </div>
        <form @submit.prevent="requestLeave">
          <div class="modal-body space-y-4">
            <div>
              <label class="label">{{ $t('hr.leave_type') }} <span class="text-red-500">*</span></label>
              <select v-model="leaveForm.leave_type" class="select" required>
                <option v-for="lt in leaveTypes" :key="lt" :value="lt">{{ lt }}</option>
              </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="label">{{ $t('hr.from') }}</label>
                <input v-model="leaveForm.start_date" type="date" class="input" required />
              </div>
              <div>
                <label class="label">{{ $t('hr.to') }}</label>
                <input v-model="leaveForm.end_date" type="date" class="input" required />
              </div>
            </div>
            <div>
              <label class="label">{{ $t('finance.reason') }}</label>
              <textarea v-model="leaveForm.reason" class="textarea" rows="3" required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" @click="showRequest = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="leaveForm.processing" class="btn-primary">
              {{ $t('hr.submit_request') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref, reactive } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  leaves: Object,
  myBalance: { type: Array, default: () => [] },
  users: { type: Array, default: () => [] },
  leaveTypes: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  canApprove: Boolean,
})

const showRequest = ref(false)
const filters = reactive({ status: props.filters.status ?? '', user_id: props.filters.user_id ?? '' })
const search = () => router.get(route('tenant.manager.hr.leave'), filters, { preserveState: true, replace: true })

const leaveForm = useForm({ leave_type: props.leaveTypes[0] ?? '', start_date: '', end_date: '', reason: '' })
const requestLeave = () => {
  leaveForm.post(route('tenant.manager.hr.request-leave'), {
    onSuccess: () => {
      showRequest.value = false
      leaveForm.reset()
    },
  })
}

const approve = (id) => useForm({}).post(route('tenant.manager.hr.approve-leave', id))
const reject = (id) => useForm({}).post(route('tenant.manager.hr.reject-leave', id))

const leaveStatusLabel = (s) =>
  ({ pending: t('common.pending_3'), approved: 'Zatwierdzony', rejected: 'Odrzucony' })[s] ?? s
const leaveStatusClass = (s) =>
  ({ pending: 'badge-yellow', approved: 'badge-green', rejected: 'badge-red' })[s] ?? 'badge-gray'
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')
</script>
