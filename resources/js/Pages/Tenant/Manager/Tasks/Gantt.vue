<template>
  <ManagerLayout title="Gantt">
    <div class="space-y-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('tasks.gantt_chart') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ $t('tasks.tasks_and_milestones_on_a_timeline') }}</p>
        </div>
        <div class="flex items-center gap-3">
          <select v-model="selectedProject" @change="load" class="input-sm">
            <option value="">{{ $t('tasks.choose_a_project') }}</option>
            <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
          <div class="flex items-center gap-1">
            <button @click="zoom(-1)" class="btn-ghost text-sm px-2">−</button>
            <span class="text-xs text-gray-500 w-16 text-center">{{ zoomLabel }}</span>
            <button @click="zoom(1)" class="btn-ghost text-sm px-2">+</button>
          </div>
        </div>
      </div>

      <!-- Gantt chart -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div v-if="!tasks.length" class="py-20 text-center">
          <i class="fa-solid fa-chart-gantt text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('tasks.choose_a_project_to_see_its') }}</p>
        </div>

        <div v-else class="overflow-x-auto">
          <div class="flex">
            <!-- Labels column -->
            <div class="flex-shrink-0 w-64 border-r border-gray-200">
              <div class="h-10 border-b border-gray-200 flex items-center px-4">
                <span class="text-xs font-semibold text-gray-500">{{ $t('tasks.task') }}</span>
              </div>
              <div
                v-for="task in tasks"
                :key="task.id"
                class="h-10 border-b border-gray-100 flex items-center px-4 gap-2 hover:bg-gray-50"
              >
                <span :class="priorityDot(task.priority)" class="w-2 h-2 rounded-full flex-shrink-0"></span>
                <Link
                  :href="route('tenant.manager.tasks.show', task.id)"
                  class="text-sm text-gray-700 truncate hover:text-indigo-600"
                >
                  {{ task.title }}
                </Link>
              </div>
            </div>

            <!-- Timeline -->
            <div class="flex-1 min-w-0 overflow-x-auto">
              <!-- Date headers -->
              <div class="h-10 flex border-b border-gray-200" :style="{ minWidth: timelineWidth + 'px' }">
                <div
                  v-for="col in dateColumns"
                  :key="col.date"
                  class="flex-shrink-0 text-center text-xs text-gray-500 border-r border-gray-100"
                  :style="{ width: colWidth + 'px' }"
                >
                  <div class="py-1.5 font-medium">{{ col.label }}</div>
                </div>
              </div>

              <!-- Task bars -->
              <div :style="{ minWidth: timelineWidth + 'px' }">
                <div
                  v-for="task in tasks"
                  :key="task.id"
                  class="h-10 border-b border-gray-100 relative flex items-center"
                >
                  <!-- Today line -->
                  <div
                    class="absolute top-0 bottom-0 w-px bg-red-400 opacity-60 z-10"
                    :style="{ left: todayOffset + 'px' }"
                  ></div>

                  <div
                    v-if="task.start_date && task.due_date"
                    class="absolute h-5 rounded-md flex items-center px-2 text-xs font-medium text-white truncate cursor-pointer hover:opacity-90 transition-opacity"
                    :style="barStyle(task)"
                    :title="task.title"
                  >
                    <i v-if="task.is_completed" class="fa-solid fa-check mr-1"></i>
                    {{ task.title }}
                  </div>
                  <div v-else class="absolute h-1 rounded-full bg-gray-200 left-0 right-0 mx-4"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Legend -->
      <div class="flex items-center gap-4 text-xs text-gray-500">
        <div class="flex items-center gap-1.5">
          <div class="w-3 h-0.5 bg-red-400"></div>
          <span>{{ $t('tasks.today') }}</span>
        </div>
        <div class="flex items-center gap-1.5">
          <div class="w-3 h-3 rounded bg-indigo-500"></div>
          <span>{{ $t('common.in_progress_2') }}</span>
        </div>
        <div class="flex items-center gap-1.5">
          <div class="w-3 h-3 rounded bg-green-500"></div>
          <span>{{ $t('tasks.finished') }}</span>
        </div>
        <div class="flex items-center gap-1.5">
          <div class="w-3 h-3 rounded bg-red-500"></div>
          <span>{{ $t('common.overdue') }}</span>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { intlLocale } from '@/format'

const props = defineProps({
  tasks: { type: Array, default: () => [] },
  projects: { type: Array, default: () => [] },
  filters: Object,
})

const selectedProject = ref(props.filters?.project_id ?? '')
const zoomLevel = ref(2) // 1=week, 2=month, 3=quarter
const zoomLabels = [t('time.week'), t('reports.month'), t('common.quarter_2')]
const zoomLabel = computed(() => zoomLabels[zoomLevel.value - 1])

const colWidth = computed(() => [40, 20, 10][zoomLevel.value - 1])

const tasks = computed(() => props.tasks ?? [])

const dateRange = computed(() => {
  const all = tasks.value.flatMap((t) => [t.start_date, t.due_date]).filter(Boolean)
  if (!all.length) {
    const now = new Date()
    return {
      start: new Date(now.getFullYear(), now.getMonth(), 1),
      end: new Date(now.getFullYear(), now.getMonth() + 3, 0),
    }
  }
  const start = new Date(Math.min(...all.map((d) => new Date(d))))
  const end = new Date(Math.max(...all.map((d) => new Date(d))))
  start.setDate(start.getDate() - 7)
  end.setDate(end.getDate() + 7)
  return { start, end }
})

const dateColumns = computed(() => {
  const cols = []
  const cur = new Date(dateRange.value.start)
  const end = dateRange.value.end

  while (cur <= end) {
    const days = [7, 1, 7][zoomLevel.value - 1]
    cols.push({
      date: new Date(cur),
      label: cur.toLocaleDateString(
        intlLocale(),
        zoomLevel.value === 2 ? { month: 'short', day: 'numeric' } : { month: 'short', day: 'numeric' },
      ),
    })
    cur.setDate(cur.getDate() + days)
  }
  return cols
})

const timelineWidth = computed(() => dateColumns.value.length * colWidth.value)

const todayOffset = computed(() => {
  const startTime = dateRange.value.start.getTime()
  const diff = Date.now() - startTime
  const days = diff / (1000 * 60 * 60 * 24)
  return days * colWidth.value
})

const barStyle = (task) => {
  const start = dateRange.value.start.getTime()
  const taskStart = new Date(task.start_date).getTime()
  const taskEnd = new Date(task.due_date).getTime()
  const dayMs = 1000 * 60 * 60 * 24

  const left = Math.max(0, ((taskStart - start) / dayMs) * colWidth.value)
  const width = Math.max(colWidth.value, ((taskEnd - taskStart) / dayMs) * colWidth.value)

  const isCompleted = !!task.completed_at
  const isOverdue = !isCompleted && new Date(task.due_date) < new Date()
  const bg = isCompleted ? '#22c55e' : isOverdue ? '#ef4444' : '#6366f1'

  return { left: left + 'px', width: width + 'px', background: bg }
}

const priorityDot = (p) =>
  ({
    urgent: 'bg-red-500',
    high: 'bg-orange-400',
    medium: 'bg-yellow-400',
    low: 'bg-gray-400',
  })[p] ?? 'bg-gray-400'

const zoom = (dir) => {
  zoomLevel.value = Math.max(1, Math.min(3, zoomLevel.value + dir))
}

const load = () => {
  router.get(route('tenant.manager.tasks.gantt'), { project_id: selectedProject.value }, { preserveState: true })
}
</script>
