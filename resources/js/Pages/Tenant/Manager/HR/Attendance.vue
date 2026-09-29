<template>
  <ManagerLayout :title="$t('hr.attendance')">
    <div class="space-y-5">
      <div class="page-header">
        <div>
          <h1 class="page-title">{{ $t('hr.attendance_record') }}</h1>
          <p class="page-subtitle">{{ today }}</p>
        </div>
        <div class="flex gap-2">
          <button v-if="!activeAttendance" @click="clockIn" class="btn-primary">
            <i class="fa-solid fa-door-open"></i> {{ $t('hr.in') }}
          </button>
          <button v-else @click="clockOut" class="btn-danger">
            <i class="fa-solid fa-door-closed"></i> {{ $t('hr.out') }}
          </button>
        </div>
      </div>

      <!-- Active clock in -->
      <div v-if="activeAttendance" class="card p-5 border-emerald-200 bg-emerald-50">
        <div class="flex items-center gap-4">
          <div class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></div>
          <div>
            <div class="font-semibold text-emerald-900">
              {{ $t('hr.you_clocked_in_at') }} {{ formatTime(activeAttendance.clock_in) }}
            </div>
            <div class="text-sm text-emerald-700">{{ elapsedDisplay }}</div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filter-bar">
        <input v-model="filters.date_from" type="date" class="input input-sm" @change="search" />
        <span class="text-gray-400">→</span>
        <input v-model="filters.date_to" type="date" class="input input-sm" @change="search" />
        <select v-if="canViewAll" v-model="filters.user_id" class="select input-sm w-40" @change="search">
          <option value="">{{ $t('common.everyone') }}</option>
          <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
        </select>
      </div>

      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th class="th">{{ $t('common.date') }}</th>
              <th class="th">{{ $t('common.employee') }}</th>
              <th class="th">{{ $t('hr.in') }}</th>
              <th class="th">{{ $t('hr.out') }}</th>
              <th class="th">{{ $t('common.hours') }}</th>
              <th class="th">{{ $t('common.note') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="!attendance.data?.length">
              <td colspan="6" class="td text-center text-gray-400 py-8">{{ $t('hr.no_records') }}</td>
            </tr>
            <tr v-for="rec in attendance.data" :key="rec.id" class="tr-hover">
              <td class="td text-sm text-gray-600">{{ formatDate(rec.date) }}</td>
              <td class="td text-sm font-medium text-gray-800">{{ rec.user?.name }}</td>
              <td class="td text-sm text-emerald-600 font-mono">{{ rec.clock_in ? formatTime(rec.clock_in) : '—' }}</td>
              <td class="td text-sm text-red-500 font-mono">{{ rec.clock_out ? formatTime(rec.clock_out) : '—' }}</td>
              <td class="td text-sm font-semibold text-gray-900">
                {{ rec.total_hours ? formatHours(rec.total_hours) : '—' }}
              </td>
              <td class="td text-xs text-gray-500">{{ rec.notes }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <Pagination :links="attendance.links" />
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { intlLocale } from '@/format'

const props = defineProps({
  attendance: Object,
  activeAttendance: { type: Object, default: null },
  users: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  canViewAll: Boolean,
})

const today = new Date().toLocaleDateString(intlLocale(), {
  weekday: 'long',
  year: 'numeric',
  month: 'long',
  day: 'numeric',
})
const filters = reactive({
  date_from: props.filters.date_from ?? '',
  date_to: props.filters.date_to ?? '',
  user_id: props.filters.user_id ?? '',
})
const search = () => router.get(route('tenant.manager.hr.attendance'), filters, { preserveState: true, replace: true })

const elapsed = ref(0)
let timerInterval = null

onMounted(() => {
  if (props.activeAttendance) {
    const start = new Date(props.activeAttendance.clock_in).getTime()
    elapsed.value = Math.floor((Date.now() - start) / 1000)
    timerInterval = setInterval(() => elapsed.value++, 1000)
  }
})
onUnmounted(() => clearInterval(timerInterval))

const elapsedDisplay = computed(() => {
  const h = Math.floor(elapsed.value / 3600)
  const m = Math.floor((elapsed.value % 3600) / 60)
  return `${h}h ${m}m przepracowanych`
})

const clockIn = () => useForm({}).post(route('tenant.manager.hr.clock-in'))
const clockOut = () => useForm({}).post(route('tenant.manager.hr.clock-out'))

const formatDate = (d) => (d ? new Date(d).toLocaleDateString(intlLocale()) : '—')
const formatTime = (dt) =>
  dt ? new Date(dt).toLocaleTimeString(intlLocale(), { hour: '2-digit', minute: '2-digit' }) : '—'
const formatHours = (h) => {
  const hrs = Math.floor(h)
  const m = Math.round((h - hrs) * 60)
  return m > 0 ? `${hrs}h ${m}m` : `${hrs}h`
}
</script>
