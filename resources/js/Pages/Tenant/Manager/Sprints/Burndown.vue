<template>
  <ManagerLayout :title="'Burndown — ' + sprint.name">
    <div class="space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ sprint.name }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">
            {{ formatDate(sprint.start_date) }} — {{ formatDate(sprint.end_date) }}
            <span class="ml-2 text-xs px-2 py-0.5 rounded-full" :class="statusClass(sprint.status)">{{
              sprint.status
            }}</span>
          </p>
        </div>
        <Link :href="route('tenant.manager.sprints.show', sprint.id)" class="btn-ghost text-sm">
          <i class="fa-solid fa-arrow-left mr-1"></i> Sprint Board
        </Link>
      </div>

      <!-- Sprint stats -->
      <div class="grid grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
          <p class="text-2xl font-bold text-gray-900">{{ stats.total_tasks }}</p>
          <p class="text-xs text-gray-500 mt-1">{{ $t('sprints.tasks_in_total') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
          <p class="text-2xl font-bold text-green-600">{{ stats.completed_tasks }}</p>
          <p class="text-xs text-gray-500 mt-1">{{ $t('common.completed_2') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
          <p class="text-2xl font-bold text-indigo-600">{{ stats.total_points }}</p>
          <p class="text-xs text-gray-500 mt-1">{{ $t('sprints.story_points_in_total') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
          <p class="text-2xl font-bold" :class="stats.remaining_points > 0 ? 'text-orange-600' : 'text-green-600'">
            {{ stats.remaining_points }}
          </p>
          <p class="text-xs text-gray-500 mt-1">{{ $t('sprints.points_remaining') }}</p>
        </div>
      </div>

      <!-- Burndown chart -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-5">{{ $t('sprints.burndown_chart') }}</h2>

        <div class="relative h-64">
          <svg viewBox="0 0 600 200" class="w-full h-full" preserveAspectRatio="none">
            <!-- Grid lines -->
            <line
              v-for="i in 5"
              :key="i"
              x1="0"
              :x2="600"
              :y1="i * 40"
              :y2="i * 40"
              stroke="#f3f4f6"
              stroke-width="1"
            />

            <!-- Ideal line -->
            <line x1="0" y1="0" :x2="600" y2="200" stroke="#d1d5db" stroke-width="1.5" stroke-dasharray="6,4" />

            <!-- Actual burndown -->
            <polyline
              v-if="burndownPoints.length > 1"
              :points="burndownPoints.map((p) => p.x + ',' + p.y).join(' ')"
              fill="none"
              stroke="#6366f1"
              stroke-width="2.5"
              stroke-linecap="round"
              stroke-linejoin="round"
            />

            <!-- Points -->
            <circle
              v-for="p in burndownPoints"
              :key="p.date"
              :cx="p.x"
              :cy="p.y"
              r="4"
              fill="white"
              stroke="#6366f1"
              stroke-width="2"
            />
          </svg>

          <!-- Y axis labels -->
          <div class="absolute left-0 top-0 bottom-0 flex flex-col justify-between pr-2">
            <span class="text-xs text-gray-400">{{ stats.total_points }}</span>
            <span class="text-xs text-gray-400">0</span>
          </div>
        </div>

        <div class="flex items-center gap-4 mt-3 justify-center text-xs text-gray-500">
          <div class="flex items-center gap-1.5">
            <div class="w-5 h-0.5 bg-gray-300" style="border-style: dashed; border-top: 1px dashed"></div>
            <span>{{ $t('sprints.ideal') }}</span>
          </div>
          <div class="flex items-center gap-1.5">
            <div class="w-5 h-0.5 bg-indigo-500"></div>
            <span>{{ $t('sprints.actual') }}</span>
          </div>
        </div>
      </div>

      <!-- Task list -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200">
          <h2 class="text-sm font-semibold text-gray-700">{{ $t('sprints.sprint_tasks') }}</h2>
        </div>
        <table class="min-w-full">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="th">{{ $t('common.task') }}</th>
              <th class="th">{{ $t('common.status') }}</th>
              <th class="th text-right">Story Points</th>
              <th class="th">{{ $t('common.assigned_2') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="task in sprint.tasks" :key="task.id" class="hover:bg-gray-50">
              <td class="td">
                <Link
                  :href="route('tenant.manager.tasks.show', task.id)"
                  class="text-sm font-medium text-gray-900 hover:text-indigo-600"
                  :class="task.completed_at ? 'line-through text-gray-400' : ''"
                >
                  {{ task.title }}
                </Link>
              </td>
              <td class="td">
                <span
                  v-if="task.status"
                  class="text-xs px-2 py-0.5 rounded-full font-medium"
                  :style="{ background: task.status.color + '22', color: task.status.color }"
                >
                  {{ task.status.name }}
                </span>
              </td>
              <td class="td text-right text-sm font-semibold text-indigo-600">
                {{ task.story_points ?? '—' }}
              </td>
              <td class="td">
                <div class="flex -space-x-1">
                  <div
                    v-for="a in (task.assignees ?? []).slice(0, 3)"
                    :key="a.id"
                    class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold ring-2 ring-white"
                    :title="a.name"
                  >
                    {{ a.name?.charAt(0) }}
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  sprint: Object,
  stats: { type: Object, default: () => ({}) },
  burndown: { type: Array, default: () => [] },
})

const burndownPoints = computed(() => {
  if (!props.burndown?.length) return []
  const max = Math.max(...props.burndown.map((p) => p.remaining), props.stats.total_points ?? 1)
  return props.burndown.map((p, i) => ({
    ...p,
    x: (i / (props.burndown.length - 1)) * 600,
    y: (1 - p.remaining / max) * 200,
  }))
})

const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')

const statusClass = (s) =>
  ({
    created: 'bg-gray-100 text-gray-600',
    in_progress: 'bg-blue-100 text-blue-700',
    completed: 'bg-green-100 text-green-700',
  })[s] ?? 'bg-gray-100 text-gray-600'
</script>
