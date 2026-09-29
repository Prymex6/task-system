<template>
  <ClientLayout :title="$t('portal.my_tasks')">
    <div class="space-y-5">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('common.tasks') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $t('portal.tasks_assigned_in_your_projects') }}</p>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
        <select v-model="filters.project_id" @change="apply" class="input-sm">
          <option value="">{{ $t('common.all_projects') }}</option>
          <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
        <select v-model="filters.status" @change="apply" class="input-sm">
          <option value="">{{ $t('common.all_statuses') }}</option>
          <option value="open">{{ $t('common.open') }}</option>
          <option value="completed">{{ $t('common.completed') }}</option>
        </select>
      </div>

      <!-- Tasks -->
      <div class="space-y-2">
        <div
          v-for="task in tasks.data"
          :key="task.id"
          @click="router.visit(route('tenant.client.tasks.show', task.id))"
          class="bg-white rounded-xl border border-gray-200 p-4 hover:border-indigo-300 hover:shadow-sm cursor-pointer transition-all"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3 flex-1 min-w-0">
              <i
                :class="
                  task.completed_at ? 'fa-solid fa-circle-check text-green-500' : 'fa-regular fa-circle text-gray-300'
                "
                class="mt-0.5 flex-shrink-0"
              ></i>
              <div class="flex-1 min-w-0">
                <p
                  class="text-sm font-medium text-gray-900 truncate"
                  :class="task.completed_at ? 'line-through text-gray-400' : ''"
                >
                  {{ task.title }}
                </p>
                <p class="text-xs text-gray-500 mt-0.5">{{ task.project?.name }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
              <span
                v-if="task.status"
                class="text-xs px-2 py-0.5 rounded-full"
                :style="{ background: task.status.color + '22', color: task.status.color }"
              >
                {{ task.status.name }}
              </span>
              <span v-if="task.due_date" class="text-xs" :class="isOverdue(task) ? 'text-red-500' : 'text-gray-400'">
                <i class="fa-regular fa-calendar mr-0.5"></i>{{ formatDate(task.due_date) }}
              </span>
            </div>
          </div>
        </div>

        <div v-if="!tasks.data?.length" class="py-16 text-center bg-white rounded-xl border border-gray-200">
          <i class="fa-solid fa-list-check text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('portal.no_tasks_assigned_to_you') }}</p>
        </div>
      </div>

      <Pagination :links="tasks.links" />
    </div>
  </ClientLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { intlLocale } from '@/format'

const props = defineProps({
  tasks: Object,
  projects: { type: Array, default: () => [] },
  filters: Object,
})

const filters = reactive({ project_id: props.filters?.project_id ?? '', status: props.filters?.status ?? '' })

const apply = () => router.get(route('tenant.client.tasks.index'), filters, { preserveState: true, replace: true })

const isOverdue = (t) => t.due_date && !t.completed_at && new Date(t.due_date) < new Date()
const formatDate = (d) => (d ? new Date(d).toLocaleDateString(intlLocale(), { day: 'numeric', month: 'short' }) : '—')
</script>
