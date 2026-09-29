<template>
  <ManagerLayout title="Timesheet">
    <div class="space-y-5">
      <div class="page-header">
        <div>
          <h1 class="page-title">Timesheet</h1>
          <p class="page-subtitle">{{ $t('time.time_tracking') }}</p>
        </div>
        <button @click="showManual = true" class="btn-primary">
          <i class="fa-solid fa-plus"></i> {{ $t('time.add_entry') }}
        </button>
      </div>

      <!-- Active timer -->
      <div v-if="activeTimer" class="card p-5 border-indigo-200 bg-indigo-50">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="w-3 h-3 rounded-full bg-indigo-500 animate-pulse"></div>
            <div>
              <div class="font-semibold text-indigo-900">{{ $t('time.timer_running') }}</div>
              <div class="text-sm text-indigo-700">{{ activeTimer.task?.title ?? $t('common.no_task') }}</div>
            </div>
            <div class="text-2xl font-mono font-bold text-indigo-800">{{ timerDisplay }}</div>
          </div>
          <button @click="stopTimer" class="btn-danger"><i class="fa-solid fa-stop"></i> {{ $t('tasks.stop') }}</button>
        </div>
      </div>

      <!-- Filters -->
      <div class="filter-bar">
        <input v-model="filters.date_from" type="date" class="input input-sm" @change="search" />
        <span class="text-gray-400 text-sm">→</span>
        <input v-model="filters.date_to" type="date" class="input input-sm" @change="search" />
        <select v-model="filters.project_id" class="select input-sm w-44" @change="search">
          <option value="">{{ $t('common.all_projects') }}</option>
          <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
        <div class="ml-auto text-sm font-semibold text-gray-700">
          {{ $t('common.total') }} {{ formatHours(totalHours) }}
        </div>
      </div>

      <!-- Entries table -->
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th class="th">{{ $t('common.date') }}</th>
              <th class="th">{{ $t('common.project') }}</th>
              <th class="th">{{ $t('common.task') }}</th>
              <th class="th">{{ $t('common.description') }}</th>
              <th class="th">{{ $t('common.hours') }}</th>
              <th class="th">{{ $t('common.employee') }}</th>
              <th class="th"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="!entries.data?.length">
              <td colspan="7" class="td text-center text-gray-400 py-8">{{ $t('settings.no_entries') }}</td>
            </tr>
            <tr v-for="entry in entries.data" :key="entry.id" class="tr-hover">
              <td class="td text-xs text-gray-500">{{ formatDate(entry.date) }}</td>
              <td class="td text-sm text-gray-700">{{ entry.project?.name ?? '—' }}</td>
              <td class="td text-sm">
                <Link
                  v-if="entry.task"
                  :href="route('tenant.manager.tasks.show', entry.task.id)"
                  class="text-indigo-600 hover:text-indigo-700"
                >
                  {{ entry.task.title }}
                </Link>
                <span v-else class="text-gray-400">—</span>
              </td>
              <td class="td text-sm text-gray-600">{{ entry.description }}</td>
              <td class="td font-semibold text-gray-900">{{ formatHours(entry.hours) }}</td>
              <td class="td text-xs text-gray-500">{{ entry.user?.name }}</td>
              <td class="td">
                <button @click="deleteEntry(entry.id)" class="btn-ghost btn-sm text-red-500">
                  <i class="fa-solid fa-trash-can"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <Pagination :links="entries.links" />
    </div>

    <!-- Manual Entry Modal -->
    <div v-if="showManual" class="modal-backdrop" @click.self="showManual = false">
      <div class="modal-md">
        <div class="modal-header">
          <h3 class="modal-title">{{ $t('time.add_time_entry') }}</h3>
          <button @click="showManual = false" class="btn-ghost btn-sm"><i class="fa-solid fa-times"></i></button>
        </div>
        <form @submit.prevent="addEntry">
          <div class="modal-body space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="label">{{ $t('common.date') }}</label>
                <input v-model="manualForm.date" type="date" class="input" required />
              </div>
              <div>
                <label class="label">{{ $t('common.hours') }}</label>
                <input
                  v-model="manualForm.hours"
                  type="number"
                  step="0.25"
                  min="0.25"
                  max="24"
                  class="input"
                  required
                />
              </div>
              <div>
                <label class="label">{{ $t('common.project') }}</label>
                <select v-model="manualForm.project_id" class="select">
                  <option value="">{{ $t('common.none') }}</option>
                  <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
              </div>
              <div>
                <label class="label">{{ $t('common.task') }}</label>
                <select v-model="manualForm.task_id" class="select">
                  <option value="">{{ $t('common.none') }}</option>
                  <option v-for="t in filteredTasks" :key="t.id" :value="t.id">{{ t.title }}</option>
                </select>
              </div>
              <div class="col-span-2">
                <label class="label">{{ $t('common.description') }}</label>
                <input
                  v-model="manualForm.description"
                  type="text"
                  class="input"
                  :placeholder="$t('time.what_were_you_working_on')"
                />
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" @click="showManual = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="manualForm.processing" class="btn-primary">{{ $t('common.save') }}</button>
          </div>
        </form>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { intlLocale } from '@/format'

const props = defineProps({
  entries: Object,
  activeTimer: { type: Object, default: null },
  projects: { type: Array, default: () => [] },
  tasks: { type: Array, default: () => [] },
  totalHours: { type: Number, default: 0 },
  filters: { type: Object, default: () => ({}) },
})

const showManual = ref(false)
const filters = reactive({
  date_from: props.filters.date_from ?? '',
  date_to: props.filters.date_to ?? '',
  project_id: props.filters.project_id ?? '',
})

const search = () => router.get(route('tenant.manager.time.index'), filters, { preserveState: true, replace: true })

// Timer display
const elapsed = ref(0)
let timerInterval = null

const timerDisplay = computed(() => {
  const h = Math.floor(elapsed.value / 3600)
  const m = Math.floor((elapsed.value % 3600) / 60)
  const s = elapsed.value % 60
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
})

onMounted(() => {
  if (props.activeTimer) {
    const startedAt = new Date(props.activeTimer.started_at).getTime()
    elapsed.value = Math.floor((Date.now() - startedAt) / 1000)
    timerInterval = setInterval(() => elapsed.value++, 1000)
  }
})

onUnmounted(() => clearInterval(timerInterval))

const stopTimer = () => useForm({}).post(route('tenant.manager.timer.stop'))

// Manual form
const manualForm = useForm({
  date: new Date().toISOString().slice(0, 10),
  hours: 1,
  project_id: '',
  task_id: '',
  description: '',
})

const filteredTasks = computed(() =>
  manualForm.project_id ? props.tasks.filter((t) => t.project_id == manualForm.project_id) : props.tasks,
)

const addEntry = () => {
  manualForm.post(route('tenant.manager.timer.store-entry'), {
    onSuccess: () => {
      showManual.value = false
      manualForm.reset()
    },
  })
}

const deleteEntry = (id) => {
  if (!confirm(t('time.delete_this_entry'))) return
  useForm({}).delete(route('tenant.manager.timer.destroy-entry', id))
}

const formatHours = (h) => {
  const hrs = Math.floor(h)
  const mins = Math.round((h - hrs) * 60)
  return mins > 0 ? `${hrs}h ${mins}m` : `${hrs}h`
}

const formatDate = (d) => (d ? new Date(d).toLocaleDateString(intlLocale()) : '—')
</script>
