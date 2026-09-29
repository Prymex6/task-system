<template>
  <ManagerLayout :title="$t('time.team_hours')">
    <div class="space-y-5">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('time.team_hours') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ $t('time.week_2') }} {{ weekLabel }}</p>
        </div>
        <div class="flex items-center gap-2">
          <button @click="prevWeek" class="btn-ghost"><i class="fa-solid fa-chevron-left"></i></button>
          <button @click="nextWeek" class="btn-ghost"><i class="fa-solid fa-chevron-right"></i></button>
          <button @click="goThisWeek" class="btn-ghost text-sm">{{ $t('time.this_week') }}</button>
        </div>
      </div>

      <!-- Summary cards -->
      <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-sm text-gray-500">{{ $t('time.total_hours') }}</p>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ totalHours }}h</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-sm text-gray-500">{{ $t('time.billable_hours') }}</p>
          <p class="text-2xl font-bold text-indigo-600 mt-1">{{ billableHours }}h</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-sm text-gray-500">{{ $t('time.active_staff') }}</p>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ activeUsers }}</p>
        </div>
      </div>

      <!-- Team table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th text-left">{{ $t('common.employee') }}</th>
              <th class="th text-right">{{ $t('common.hours') }}</th>
              <th class="th text-right">Billable</th>
              <th class="th text-right">{{ $t('common.projects') }}</th>
              <th class="th">{{ $t('time.approval') }}</th>
              <th class="th"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="row in timesheets" :key="row.user.id" class="hover:bg-gray-50">
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
              <td class="td text-right text-sm text-indigo-600">{{ row.billable }}h</td>
              <td class="td text-right text-sm text-gray-500">{{ row.projects ?? '—' }}</td>
              <td class="td text-center">
                <span
                  v-if="row.approval"
                  class="text-xs px-2 py-0.5 rounded-full font-medium"
                  :class="approvalClass(row.approval.status)"
                >
                  {{ approvalLabel(row.approval.status) }}
                </span>
                <span v-else class="text-xs text-gray-400">{{ $t('settings.none') }}</span>
              </td>
              <td class="td text-right">
                <div class="flex items-center justify-end gap-2" v-if="row.approval?.status === 'submitted'">
                  <button @click="approve(row)" class="text-xs text-green-600 hover:text-green-800 font-medium">
                    {{ $t('common.approve') }}
                  </button>
                  <button @click="reject(row)" class="text-xs text-red-500 hover:text-red-700 font-medium">
                    {{ $t('common.reject') }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="!timesheets.length" class="py-16 text-center">
          <i class="fa-solid fa-clock text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('time.no_time_logged_this_week') }}</p>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import axios from 'axios'

const props = defineProps({
  timesheets: { type: Array, default: () => [] },
  weekStart: String,
})

const week = ref(props.weekStart ?? new Date().toISOString().slice(0, 10))

const weekLabel = computed(() => {
  const start = new Date(week.value)
  const end = new Date(start)
  end.setDate(end.getDate() + 6)
  return (
    start.toLocaleDateString('pl-PL', { day: 'numeric', month: 'short' }) +
    ' – ' +
    end.toLocaleDateString('pl-PL', { day: 'numeric', month: 'short', year: 'numeric' })
  )
})

const totalHours = computed(() => props.timesheets.reduce((s, r) => s + (r.total_hours ?? 0), 0).toFixed(1))
const billableHours = computed(() => props.timesheets.reduce((s, r) => s + (r.billable ?? 0), 0).toFixed(1))
const activeUsers = computed(() => props.timesheets.filter((r) => r.total_hours > 0).length)

const prevWeek = () => {
  shiftWeek(-7)
}
const nextWeek = () => {
  shiftWeek(7)
}
const goThisWeek = () => {
  week.value = getMondayOfToday()
  load()
}

const getMondayOfToday = () => {
  const d = new Date()
  const day = d.getDay()
  const diff = d.getDate() - day + (day === 0 ? -6 : 1)
  d.setDate(diff)
  return d.toISOString().slice(0, 10)
}

const shiftWeek = (days) => {
  const d = new Date(week.value)
  d.setDate(d.getDate() + days)
  week.value = d.toISOString().slice(0, 10)
  load()
}

const load = () => {
  router.get(route('tenant.manager.time.team'), { week: week.value }, { preserveState: true, replace: true })
}

const approve = async (row) => {
  await axios.post(route('tenant.manager.timesheets.approvals.approve', row.approval.id))
  load()
}

const reject = async (row) => {
  await axios.post(route('tenant.manager.timesheets.approvals.reject', row.approval.id))
  load()
}

const approvalClass = (status) =>
  ({
    submitted: 'bg-yellow-100 text-yellow-700',
    approved: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-700',
  })[status] ?? 'bg-gray-100 text-gray-600'

const approvalLabel = (status) =>
  ({
    submitted: 'Oczekuje',
    approved: 'Zatwierdzone',
    rejected: 'Odrzucone',
  })[status] ?? status
</script>
