<template>
  <ManagerLayout :title="$t('tasks.recurring_tasks')">
    <div class="space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('tasks.recurring_tasks') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ $t('tasks.tasks_that_create_their_next_instance') }}</p>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">{{ $t('common.task') }}</th>
              <th class="th">{{ $t('common.project') }}</th>
              <th class="th text-center">{{ $t('finance.frequency') }}</th>
              <th class="th">{{ $t('tasks.next_due') }}</th>
              <th class="th">{{ $t('tasks.repeat_until') }}</th>
              <th class="th text-right">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="task in tasks.data" :key="task.id" class="hover:bg-gray-50">
              <td class="td">
                <Link
                  :href="route('tenant.manager.tasks.show', task.id)"
                  class="font-medium text-gray-900 hover:text-indigo-600"
                >
                  {{ task.title }}
                </Link>
              </td>
              <td class="td text-gray-600">{{ task.project?.name }}</td>
              <td class="td text-center">
                <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-medium">
                  {{ frequencyLabel(task.frequency) }}
                </span>
              </td>
              <td class="td text-gray-700">{{ formatDate(task.next_run_at) }}</td>
              <td class="td text-gray-500">{{ formatDate(task.recur_end_date) ?? 'Bez limitu' }}</td>
              <td class="td text-right">
                <button @click="stopRecurring(task)" class="text-xs text-orange-500 hover:text-orange-700">
                  {{ $t('tasks.stop') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!tasks.data?.length" class="py-16 text-center">
          <i class="fa-solid fa-rotate text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('tasks.no_recurring_tasks') }}</p>
        </div>
      </div>

      <Pagination :links="tasks.links" />
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({ tasks: Object })

const stopRecurring = (task) => {
  if (confirm(t('tasks.stop_repeating_this_task'))) {
    router.delete(route('tenant.manager.tasks.recurring.destroy', task.id))
  }
}

const frequencyLabel = (f) =>
  ({
    daily: 'Codziennie',
    weekly: t('common.weekly'),
    biweekly: 'Co 2 tygodnie',
    monthly: t('common.monthly'),
    yearly: 'Co rok',
  })[f] ?? f
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : null)
</script>
