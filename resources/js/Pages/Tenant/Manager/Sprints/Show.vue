<template>
  <ManagerLayout :title="sprint.name">
    <div class="space-y-6">
      <div class="page-header">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.sprints.index')" class="btn-ghost btn-sm"
            ><i class="fa-solid fa-arrow-left"></i
          ></Link>
          <div>
            <h1 class="page-title">{{ sprint.name }}</h1>
            <p class="page-subtitle">
              {{ sprint.project?.name }} · {{ formatDate(sprint.start_date) }} → {{ formatDate(sprint.end_date) }}
            </p>
          </div>
        </div>
        <div class="flex gap-2">
          <button v-if="sprint.status === 'planning'" @click="startSprint" class="btn-primary">
            <i class="fa-solid fa-play"></i> {{ $t('sprints.start') }}
          </button>
          <button v-if="sprint.status === 'active'" @click="completeSprint" class="btn-secondary">
            <i class="fa-solid fa-flag-checkered"></i> {{ $t('sprints.finish') }}
          </button>
        </div>
      </div>

      <!-- Progress -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="stat-card">
          <div class="stat-icon bg-indigo-100 text-indigo-600"><i class="fa-solid fa-list-check"></i></div>
          <div>
            <div class="stat-value">{{ sprint.tasks?.length ?? 0 }}</div>
            <div class="stat-label">{{ $t('sprints.tasks_in_sprint') }}</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-emerald-100 text-emerald-600"><i class="fa-solid fa-circle-check"></i></div>
          <div>
            <div class="stat-value">{{ doneTasks }}</div>
            <div class="stat-label">{{ $t('common.completed_2') }}</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-amber-100 text-amber-600"><i class="fa-solid fa-spinner"></i></div>
          <div>
            <div class="stat-value">{{ inProgressTasks }}</div>
            <div class="stat-label">{{ $t('common.in_progress') }}</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-blue-100 text-blue-600"><i class="fa-solid fa-chart-simple"></i></div>
          <div>
            <div class="stat-value">{{ totalStoryPoints }}</div>
            <div class="stat-label">Story Points</div>
          </div>
        </div>
      </div>

      <!-- Progress bar -->
      <div class="card p-5">
        <div class="flex justify-between mb-2 text-sm">
          <span class="font-medium text-gray-700">{{ $t('sprints.sprint_progress') }}</span>
          <span class="text-gray-500">{{ progress }}%</span>
        </div>
        <div class="progress-bar h-3">
          <div class="progress-fill h-3" :style="{ width: progress + '%' }"></div>
        </div>
        <div v-if="sprint.goal" class="mt-3 text-sm text-gray-500 italic">
          <i class="fa-solid fa-bullseye text-indigo-400 mr-1.5"></i>{{ sprint.goal }}
        </div>
      </div>

      <!-- Tasks -->
      <div class="card">
        <div class="card-header flex items-center justify-between">
          <h2 class="section-title mb-0">{{ $t('common.tasks') }}</h2>
          <button @click="showAddTask = !showAddTask" class="btn-ghost btn-sm">
            <i class="fa-solid fa-plus"></i> {{ $t('sprints.add_task') }}
          </button>
        </div>

        <!-- Add task form -->
        <div v-if="showAddTask" class="px-6 py-3 bg-gray-50 border-b border-gray-100">
          <form @submit.prevent="addTask" class="flex gap-3">
            <select v-model="addTaskForm.task_id" class="select flex-1" required>
              <option value="">{{ $t('sprints.choose_a_task') }}</option>
              <option v-for="t in availableTasks" :key="t.id" :value="t.id">{{ t.title }}</option>
            </select>
            <button type="submit" :disabled="addTaskForm.processing" class="btn-primary btn-sm">
              {{ $t('common.add') }}
            </button>
            <button type="button" @click="showAddTask = false" class="btn-secondary btn-sm">
              {{ $t('common.cancel') }}
            </button>
          </form>
        </div>

        <div class="divide-y divide-gray-100">
          <div v-if="!sprint.tasks?.length" class="px-6 py-8 text-center text-sm text-gray-400">
            {{ $t('sprints.no_tasks_in_this_sprint') }}
          </div>
          <div v-for="task in sprint.tasks" :key="task.id" class="px-6 py-3 flex items-center gap-4 hover:bg-gray-50">
            <span class="w-2 h-2 rounded-full flex-shrink-0" :class="priorityDot(task.priority)"></span>
            <Link
              :href="route('tenant.manager.tasks.show', task.id)"
              class="flex-1 text-sm text-gray-800 hover:text-indigo-600 font-medium"
            >
              {{ task.title }}
            </Link>
            <StatusBadge :status="task.status?.name ?? 'open'" class="flex-shrink-0" />
            <span class="text-xs text-gray-400 w-16 text-right">{{ task.story_points ?? 0 }} SP</span>
            <button @click="removeTask(task.id)" class="btn-ghost btn-sm text-red-400 flex-shrink-0">
              <i class="fa-solid fa-times"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import StatusBadge from '@/Components/Manager/StatusBadge.vue'

const props = defineProps({
  sprint: Object,
  availableTasks: { type: Array, default: () => [] },
})

const showAddTask = ref(false)

const doneTasks = computed(() => props.sprint.tasks?.filter((t) => t.status?.is_done).length ?? 0)
const inProgressTasks = computed(
  () => props.sprint.tasks?.filter((t) => !t.status?.is_done && t.status?.name !== 'open').length ?? 0,
)
const totalStoryPoints = computed(() => props.sprint.tasks?.reduce((sum, t) => sum + (t.story_points ?? 0), 0) ?? 0)
const progress = computed(() => {
  const total = props.sprint.tasks?.length ?? 0
  return total > 0 ? Math.round((doneTasks.value / total) * 100) : 0
})

const addTaskForm = useForm({ task_id: '' })
const addTask = () => {
  addTaskForm.post(route('tenant.manager.sprints.tasks.add', props.sprint.id), {
    onSuccess: () => {
      addTaskForm.reset()
      showAddTask.value = false
    },
  })
}

const removeTask = (taskId) => {
  useForm({}).delete(route('tenant.manager.sprints.tasks.remove', [props.sprint.id, taskId]))
}

const startSprint = () => useForm({}).post(route('tenant.manager.sprints.start', props.sprint.id))
const completeSprint = () => useForm({}).post(route('tenant.manager.sprints.complete', props.sprint.id))

const priorityDot = (p) =>
  ({ critical: 'bg-red-500', high: 'bg-orange-400', medium: 'bg-amber-400', low: 'bg-gray-300' })[p] ?? 'bg-gray-300'
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')
</script>
