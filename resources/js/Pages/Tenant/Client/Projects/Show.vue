<template>
  <ClientLayout :title="project.name">
    <div class="space-y-6">
      <div class="flex items-center gap-3">
        <Link :href="route('tenant.portal.projects')" class="text-sm text-indigo-600 hover:text-indigo-700">
          {{ $t('portal.projects_2') }}
        </Link>
        <span class="text-gray-300">/</span>
        <h1 class="text-xl font-bold text-gray-900">{{ project.name }}</h1>
        <span class="badge badge-indigo text-xs">{{ statusLabel(project.status) }}</span>
      </div>

      <!-- Progress bar -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex justify-between mb-2 text-sm">
          <span class="font-medium text-gray-700">{{ $t('portal.project_progress') }}</span>
          <span class="text-gray-500">{{ project.progress ?? 0 }}%</span>
        </div>
        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
          <div class="h-2 bg-indigo-500 rounded-full" :style="{ width: (project.progress ?? 0) + '%' }"></div>
        </div>
        <div class="flex gap-6 mt-3 text-xs text-gray-400">
          <span v-if="project.start_date"
            ><i class="fa-solid fa-play mr-1"></i>{{ formatDate(project.start_date) }}</span
          >
          <span v-if="project.due_date"><i class="fa-solid fa-flag mr-1"></i>{{ formatDate(project.due_date) }}</span>
          <span
            ><i class="fa-solid fa-list-check mr-1"></i>{{ project.tasks?.length ?? 0 }}
            {{ $t('common.tasks_2') }}</span
          >
        </div>
      </div>

      <!-- Description -->
      <div v-if="project.description" class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="font-semibold text-gray-900 mb-2">{{ $t('portal.about_the_project') }}</h2>
        <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ project.description }}</p>
      </div>

      <!-- Tasks -->
      <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100">
          <h2 class="font-semibold text-gray-900">{{ $t('common.tasks') }}</h2>
        </div>
        <div class="divide-y divide-gray-100">
          <div v-if="!project.tasks?.length" class="px-5 py-8 text-center text-sm text-gray-400">
            {{ $t('common.no_tasks') }}
          </div>
          <div v-for="task in project.tasks" :key="task.id" class="px-5 py-3 flex items-center gap-3">
            <i
              class="fa-solid fa-circle-check text-sm"
              :class="task.status?.is_done ? 'text-emerald-500' : 'text-gray-300'"
            ></i>
            <div class="flex-1">
              <div class="text-sm font-medium text-gray-800">{{ task.title }}</div>
              <div v-if="task.due_date" class="text-xs text-gray-400">{{ formatDate(task.due_date) }}</div>
            </div>
            <span class="badge badge-gray text-xs">{{ task.status?.name }}</span>
          </div>
        </div>
      </div>

      <!-- Milestones -->
      <div v-if="project.milestones?.length" class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100">
          <h2 class="font-semibold text-gray-900">{{ $t('common.milestones') }}</h2>
        </div>
        <div class="divide-y divide-gray-100">
          <div v-for="ms in project.milestones" :key="ms.id" class="px-5 py-3 flex items-center gap-3">
            <i
              class="fa-solid fa-diamond text-sm"
              :class="ms.completed_at ? 'text-emerald-500' : 'text-indigo-400'"
            ></i>
            <div class="flex-1 text-sm font-medium text-gray-800">{{ ms.name }}</div>
            <div class="text-xs text-gray-400">{{ formatDate(ms.due_date) }}</div>
          </div>
        </div>
      </div>
    </div>
  </ClientLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { Link } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'

const props = defineProps({ project: Object })

const statusLabel = (s) =>
  ({ planning: 'Planowanie', in_progress: 'W trakcie', on_hold: 'Wstrzymany', completed: t('common.completed_3') })[
    s
  ] ?? s
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')
</script>
