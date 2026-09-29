<template>
  <ManagerLayout title="Kanban">
    <div class="space-y-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Kanban</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ project?.name ?? $t('common.all_projects') }}</p>
        </div>
        <div class="flex items-center gap-3">
          <select v-model="selectedProject" @change="load" class="input-sm">
            <option value="">{{ $t('common.all_projects') }}</option>
            <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
          <Link :href="route('tenant.manager.tasks.create')" class="btn-primary text-sm">
            <i class="fa-solid fa-plus mr-1"></i> {{ $t('common.new_task') }}
          </Link>
        </div>
      </div>

      <!-- Board -->
      <div class="flex gap-4 overflow-x-auto pb-4 min-h-[70vh]">
        <div
          v-for="col in columns"
          :key="col.status.id"
          class="flex-shrink-0 w-72 bg-gray-50 rounded-xl border border-gray-200 flex flex-col"
        >
          <!-- Column header -->
          <div class="p-3 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="w-3 h-3 rounded-full" :style="{ background: col.status.color }"></span>
              <span class="text-sm font-semibold text-gray-700">{{ col.status.name }}</span>
              <span class="text-xs bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded-full">{{ col.tasks.length }}</span>
            </div>
          </div>

          <!-- Tasks -->
          <div class="flex-1 p-2 space-y-2 overflow-y-auto" @dragover.prevent @drop="onDrop($event, col.status.id)">
            <div
              v-for="task in col.tasks"
              :key="task.id"
              draggable="true"
              @dragstart="onDragStart($event, task)"
              class="bg-white rounded-lg border border-gray-200 p-3 cursor-grab hover:border-indigo-300 hover:shadow-sm transition-all"
            >
              <!-- Priority bar -->
              <div class="h-0.5 rounded-full mb-2" :class="priorityBar(task.priority)"></div>

              <p class="text-sm font-medium text-gray-900 leading-snug">{{ task.title }}</p>

              <!-- Labels -->
              <div v-if="task.labels?.length" class="flex flex-wrap gap-1 mt-1.5">
                <span
                  v-for="label in task.labels"
                  :key="label.id"
                  class="text-xs px-1.5 py-0.5 rounded-full"
                  :style="{ background: label.color + '22', color: label.color }"
                >
                  {{ label.name }}
                </span>
              </div>

              <!-- Footer -->
              <div class="flex items-center justify-between mt-2.5">
                <div class="flex items-center -space-x-1">
                  <div
                    v-for="a in (task.assignees ?? []).slice(0, 3)"
                    :key="a.id"
                    class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold ring-1 ring-white"
                    :title="a.name"
                  >
                    {{ a.name?.charAt(0) }}
                  </div>
                </div>
                <span v-if="task.due_date" class="text-xs" :class="isOverdue(task) ? 'text-red-500' : 'text-gray-400'">
                  <i class="fa-regular fa-calendar mr-0.5"></i>{{ formatDate(task.due_date) }}
                </span>
              </div>

              <div class="mt-2 pt-2 border-t border-gray-100 flex items-center gap-3 text-xs text-gray-400">
                <span v-if="task.checklists_count" :title="$t('common.checklist')">
                  <i class="fa-solid fa-list-check mr-0.5"></i>{{ task.checklists_done }}/{{ task.checklists_total }}
                </span>
                <span v-if="task.comments_count" :title="$t('tasks.comments')">
                  <i class="fa-regular fa-comment mr-0.5"></i>{{ task.comments_count }}
                </span>
                <Link
                  :href="route('tenant.manager.tasks.show', task.id)"
                  class="ml-auto text-indigo-500 hover:text-indigo-700"
                  @click.stop
                >
                  <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </Link>
              </div>
            </div>

            <!-- Drop placeholder -->
            <div
              v-if="!col.tasks.length"
              class="h-16 border-2 border-dashed border-gray-200 rounded-lg flex items-center justify-center text-xs text-gray-400"
            >
              {{ $t('tasks.drag_a_task_here') }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import axios from 'axios'

const props = defineProps({
  columns: Array,
  projects: Array,
  project: Object,
  filters: Object,
})

const selectedProject = ref(props.filters?.project_id ?? '')
const columns = ref(props.columns ?? [])
let draggingTask = null

const load = () => {
  router.get(route('tenant.manager.tasks.kanban'), { project_id: selectedProject.value }, { preserveState: true })
}

const onDragStart = (e, task) => {
  draggingTask = task
  e.dataTransfer.effectAllowed = 'move'
}

const onDrop = async (e, statusId) => {
  e.preventDefault()
  if (!draggingTask) return

  const task = draggingTask
  draggingTask = null

  // Optimistic update
  columns.value = columns.value.map((col) => ({
    ...col,
    tasks: col.tasks.filter((t) => t.id !== task.id),
  }))
  const target = columns.value.find((c) => c.status.id === statusId)
  if (target) {
    target.tasks.push({ ...task, task_status_id: statusId })
  }

  try {
    await axios.post(route('tenant.manager.tasks.move', task.id), { status_id: statusId })
  } catch {
    load()
  }
}

const isOverdue = (task) => {
  if (!task.due_date || task.completed_at) return false
  return new Date(task.due_date) < new Date()
}

const formatDate = (d) => new Date(d).toLocaleDateString('pl-PL', { month: 'short', day: 'numeric' })

const priorityBar = (p) =>
  ({
    urgent: 'bg-red-500',
    high: 'bg-orange-400',
    medium: 'bg-yellow-400',
    low: 'bg-gray-300',
  })[p] ?? 'bg-gray-300'
</script>
