<template>
  <ManagerLayout :title="$t('common.tasks')">
    <div class="space-y-5">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('common.tasks') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ tasks.total }} {{ $t('common.tasks_2') }}</p>
        </div>
        <Link
          :href="route('tenant.manager.tasks.create')"
          class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
        >
          <i class="fa-solid fa-plus"></i> {{ $t('common.new_task') }}
        </Link>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
        <input
          v-model="filters.search"
          @input="apply"
          :placeholder="$t('tasks.search_tasks')"
          class="input-sm flex-1 min-w-[200px]"
        />
        <select v-model="filters.priority" @change="apply" class="input-sm">
          <option value="">{{ $t('tasks.all_priorities') }}</option>
          <option value="urgent">{{ $t('tasks.urgent') }}</option>
          <option value="high">{{ $t('tasks.high') }}</option>
          <option value="medium">{{ $t('tasks.medium') }}</option>
          <option value="low">{{ $t('tasks.low') }}</option>
        </select>
        <select v-model="filters.status_id" @change="apply" class="input-sm">
          <option value="">{{ $t('common.all_statuses') }}</option>
          <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
          <input type="checkbox" v-model="filters.my_tasks" @change="apply" class="rounded border-gray-300" />
          {{ $t('portal.my_tasks') }}
        </label>
        <label class="flex items-center gap-2 text-sm text-red-600 cursor-pointer">
          <input type="checkbox" v-model="filters.overdue" @change="apply" class="rounded border-gray-300" />
          {{ $t('common.overdue') }}
        </label>
      </div>

      <!-- Tasks table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">{{ $t('common.task') }}</th>
              <th class="th">{{ $t('common.project') }}</th>
              <th class="th">{{ $t('common.status') }}</th>
              <th class="th">{{ $t('common.priority') }}</th>
              <th class="th">{{ $t('common.assigned_2') }}</th>
              <th class="th">{{ $t('common.due') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="task in tasks.data"
              :key="task.id"
              class="hover:bg-gray-50 cursor-pointer"
              @click="router.visit(route('tenant.manager.tasks.show', task.id))"
            >
              <td class="td">
                <div class="flex items-center gap-2">
                  <i
                    :class="
                      task.is_completed
                        ? 'fa-solid fa-circle-check text-green-500'
                        : 'fa-regular fa-circle text-gray-300'
                    "
                  ></i>
                  <span
                    :class="task.is_completed ? 'line-through text-gray-400' : 'text-gray-900'"
                    class="text-sm font-medium"
                    >{{ task.title }}</span
                  >
                </div>
              </td>
              <td class="td">
                <span class="text-sm text-gray-500">{{ task.project?.name }}</span>
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
              <td class="td">
                <span :class="priorityClass(task.priority)" class="text-xs px-2 py-0.5 rounded-full font-medium">
                  {{ task.priority }}
                </span>
              </td>
              <td class="td">
                <div class="flex items-center">
                  <div
                    v-for="a in (task.assignees ?? []).slice(0, 3)"
                    :key="a.id"
                    class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold -ml-1 first:ml-0 ring-2 ring-white"
                    :title="a.name"
                  >
                    {{ a.name?.charAt(0)?.toUpperCase() }}
                  </div>
                </div>
              </td>
              <td class="td">
                <span v-if="task.due_date" :class="isOverdue(task) ? 'text-red-500' : 'text-gray-500'" class="text-sm">
                  {{ task.due_date }}
                </span>
                <span v-else class="text-gray-300">—</span>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="!tasks.data.length" class="py-16 text-center">
          <i class="fa-solid fa-list-check text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('tasks.no_tasks_match_these_filters') }}</p>
        </div>
      </div>

      <Pagination :links="tasks.links" />
    </div>
  </ManagerLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  tasks: Object,
  statuses: Array,
  labels: Array,
  filters: Object,
})

const filters = reactive({ ...props.filters })

const apply = () => {
  router.get(route('tenant.manager.tasks.index'), filters, { preserveState: true, replace: true })
}

const isOverdue = (task) => {
  if (!task.due_date || task.is_completed) return false
  return new Date(task.due_date) < new Date()
}

const priorityClass = (p) =>
  ({
    urgent: 'bg-red-100 text-red-700',
    high: 'bg-orange-100 text-orange-700',
    medium: 'bg-yellow-100 text-yellow-700',
    low: 'bg-gray-100 text-gray-600',
  })[p] ?? 'bg-gray-100 text-gray-600'
</script>
