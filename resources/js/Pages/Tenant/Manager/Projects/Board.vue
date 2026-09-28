<template>
  <ManagerLayout :title="project.name + ' – Board'">
    <div class="space-y-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.projects.show', project.id)" class="text-gray-400 hover:text-gray-600">
            <i class="fa-solid fa-arrow-left"></i>
          </Link>
          <h1 class="text-xl font-bold text-gray-900">{{ project.name }}</h1>
          <span class="text-gray-400 text-sm">/ Board</span>
        </div>
        <Link
          :href="route('tenant.manager.projects.tasks.create', project.id)"
          class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-3 py-1.5 rounded-lg transition-colors"
        >
          <i class="fa-solid fa-plus"></i> {{ $t('common.new_task') }}
        </Link>
      </div>

      <!-- Kanban -->
      <div class="flex gap-4 overflow-x-auto pb-4">
        <div v-for="column in columns" :key="column.id" class="flex-shrink-0 w-72 bg-gray-100 rounded-xl">
          <!-- Column header -->
          <div class="flex items-center justify-between p-3 border-b border-gray-200">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full" :style="{ background: column.color }"></span>
              <h3 class="text-sm font-semibold text-gray-700">{{ column.name }}</h3>
              <span class="text-xs bg-white text-gray-500 rounded-full px-2 py-0.5 border border-gray-200">
                {{ tasksByStatus(column.id).length }}
              </span>
            </div>
          </div>

          <!-- Tasks -->
          <div class="p-2 space-y-2 min-h-[200px]">
            <div
              v-for="task in tasksByStatus(column.id)"
              :key="task.id"
              class="bg-white rounded-lg border border-gray-200 p-3 shadow-sm cursor-pointer hover:border-indigo-300 hover:shadow-md transition-all"
              @click="openTask(task)"
            >
              <!-- Labels -->
              <div v-if="task.labels?.length" class="flex flex-wrap gap-1 mb-2">
                <span
                  v-for="label in task.labels"
                  :key="label.id"
                  class="text-xs px-2 py-0.5 rounded-full font-medium"
                  :style="{ background: label.color + '22', color: label.color }"
                >
                  {{ label.name }}
                </span>
              </div>

              <p class="text-sm font-medium text-gray-900 leading-snug">{{ task.title }}</p>

              <div class="flex items-center justify-between mt-3">
                <span :class="priorityClass(task.priority)" class="text-xs px-2 py-0.5 rounded-full">
                  {{ task.priority }}
                </span>
                <div class="flex items-center gap-1">
                  <div
                    v-for="a in (task.assignees ?? []).slice(0, 3)"
                    :key="a.id"
                    class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold -ml-1 ring-2 ring-white"
                  >
                    {{ a.name?.charAt(0)?.toUpperCase() }}
                  </div>
                </div>
              </div>

              <div v-if="task.due_date" class="mt-2 flex items-center gap-1 text-xs text-gray-400">
                <i class="fa-regular fa-calendar"></i>
                <span :class="isTaskOverdue(task) ? 'text-red-500' : ''">{{ task.due_date }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  project: Object,
})

const columns = computed(() => {
  const statuses = new Map()
  for (const task of props.project.tasks ?? []) {
    if (task.status && !statuses.has(task.status.id)) {
      statuses.set(task.status.id, task.status)
    }
  }
  if (statuses.size === 0) {
    return [
      { id: null, name: 'Do zrobienia', color: '#94a3b8' },
      { id: 'done', name: 'Gotowe', color: '#22c55e' },
    ]
  }
  return [...statuses.values()]
})

const tasksByStatus = (statusId) => (props.project.tasks ?? []).filter((t) => t.status_id === statusId)

const isTaskOverdue = (task) => {
  if (!task.due_date || task.is_completed) return false
  return new Date(task.due_date) < new Date()
}

const openTask = (task) => {
  router.visit(route('tenant.manager.tasks.show', task.id))
}

const priorityClass = (p) =>
  ({
    urgent: 'bg-red-100 text-red-700',
    high: 'bg-orange-100 text-orange-700',
    medium: 'bg-yellow-100 text-yellow-700',
    low: 'bg-gray-100 text-gray-600',
  })[p] ?? 'bg-gray-100 text-gray-600'
</script>
