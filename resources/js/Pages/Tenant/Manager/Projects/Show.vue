<template>
  <ManagerLayout :title="project.name">
    <div class="space-y-5">
      <!-- Header -->
      <div class="flex items-start justify-between">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.projects.index')" class="text-gray-400 hover:text-gray-600">
            <i class="fa-solid fa-arrow-left"></i>
          </Link>
          <div>
            <div class="flex items-center gap-2">
              <span v-if="project.color" class="w-3 h-3 rounded-full" :style="{ background: project.color }"></span>
              <h1 class="text-2xl font-bold text-gray-900">{{ project.name }}</h1>
              <StatusBadge :status="project.status" />
            </div>
            <p class="text-sm text-gray-500 mt-0.5">{{ project.client?.company_name ?? project.client?.name }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Link :href="route('tenant.manager.projects.board', project.id)" class="btn-secondary text-sm">
            <i class="fa-solid fa-table-columns mr-1"></i> Board
          </Link>
          <Link :href="route('tenant.manager.projects.edit', project.id)" class="btn-secondary text-sm">
            <i class="fa-solid fa-pen mr-1"></i> {{ $t('common.edit') }}
          </Link>
        </div>
      </div>

      <!-- Stats bar -->
      <div class="grid grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
          <p class="text-2xl font-bold text-indigo-600">{{ progress }}%</p>
          <p class="text-xs text-gray-500 mt-0.5">{{ $t('common.completed') }}</p>
          <div class="mt-2 h-1.5 bg-gray-100 rounded-full">
            <div class="h-1.5 bg-indigo-500 rounded-full" :style="{ width: progress + '%' }"></div>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
          <p class="text-2xl font-bold text-gray-900">{{ project.tasks?.length ?? 0 }}</p>
          <p class="text-xs text-gray-500 mt-0.5">{{ $t('projects.tasks_in_total') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
          <p class="text-2xl font-bold text-gray-900">{{ totalHours }}h</p>
          <p class="text-xs text-gray-500 mt-0.5">{{ $t('projects.registered') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
          <p class="text-2xl font-bold text-gray-900">{{ project.members?.length ?? 0 }}</p>
          <p class="text-xs text-gray-500 mt-0.5">{{ $t('projects.members') }}</p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200">
        <nav class="flex gap-6">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              activeTab === tab.id
                ? 'border-indigo-600 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700',
              'pb-3 text-sm font-medium border-b-2 transition-colors',
            ]"
          >
            <i :class="tab.icon" class="mr-1.5"></i>{{ tab.label }}
          </button>
        </nav>
      </div>

      <!-- Tab: Overview -->
      <div v-if="activeTab === 'overview'" class="grid grid-cols-3 gap-5">
        <div class="col-span-2 space-y-4">
          <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ $t('portal.about_the_project') }}</h3>
            <p v-if="project.description" class="text-sm text-gray-600 whitespace-pre-line">
              {{ project.description }}
            </p>
            <p v-else class="text-sm text-gray-400 italic">{{ $t('projects.no_description') }}</p>
          </div>

          <!-- Recent tasks -->
          <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-semibold text-gray-700">{{ $t('common.recent_tasks') }}</h3>
              <Link
                :href="route('tenant.manager.projects.tasks.index', project.id)"
                class="text-xs text-indigo-600 hover:underline"
                >{{ $t('common.all') }}</Link
              >
            </div>
            <div class="space-y-2">
              <div
                v-for="task in recentTasks"
                :key="task.id"
                class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50"
              >
                <span
                  class="w-2 h-2 rounded-full flex-shrink-0"
                  :style="{ background: task.status?.color ?? '#94a3b8' }"
                ></span>
                <span class="text-sm text-gray-800 flex-1">{{ task.title }}</span>
                <span :class="priorityClass(task.priority)" class="text-xs px-2 py-0.5 rounded-full">
                  {{ task.priority }}
                </span>
              </div>
              <p v-if="!recentTasks.length" class="text-sm text-gray-400 text-center py-4">
                {{ $t('common.no_tasks') }}
              </p>
            </div>
          </div>
        </div>

        <!-- Sidebar info -->
        <div class="space-y-4">
          <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-sm font-semibold text-gray-700">{{ $t('common.details') }}</h3>
            <div class="text-sm space-y-2">
              <div class="flex justify-between">
                <span class="text-gray-500">{{ $t('projects.start') }}</span>
                <span class="font-medium">{{ project.start_date ?? '—' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">{{ $t('finance.due') }}</span>
                <span class="font-medium" :class="isOverdue ? 'text-red-600' : ''">{{ project.due_date ?? '—' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">{{ $t('projects.budget_2') }}</span>
                <span class="font-medium">{{ project.budget ? Number(project.budget).toFixed(2) + ' PLN' : '—' }}</span>
              </div>
            </div>
          </div>

          <!-- Members -->
          <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-semibold text-gray-700">{{ $t('projects.team') }}</h3>
              <Link
                :href="route('tenant.manager.projects.members', project.id)"
                class="text-xs text-indigo-600 hover:underline"
                >{{ $t('projects.manage') }}</Link
              >
            </div>
            <div class="space-y-2">
              <div v-for="member in project.members" :key="member.id" class="flex items-center gap-2">
                <div
                  class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold"
                >
                  {{ member.name?.charAt(0)?.toUpperCase() }}
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 truncate">{{ member.name }}</p>
                  <p class="text-xs text-gray-400">{{ member.pivot?.project_role }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab: Milestones -->
      <div v-if="activeTab === 'milestones'">
        <MilestoneList :project="project" :milestones="project.milestones" />
      </div>

      <!-- Tab: Files -->
      <div v-if="activeTab === 'files'">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
          <Link
            :href="route('tenant.manager.projects.files.index', project.id)"
            class="text-indigo-600 text-sm hover:underline flex items-center gap-2"
          >
            <i class="fa-solid fa-folder-open"></i> {{ $t('projects.go_to_project_files') }}
          </Link>
        </div>
      </div>

      <!-- Tab: Discussions -->
      <div v-if="activeTab === 'discussions'">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
          <Link
            :href="route('tenant.manager.projects.discussions.index', project.id)"
            class="text-indigo-600 text-sm hover:underline flex items-center gap-2"
          >
            <i class="fa-solid fa-comments"></i> {{ $t('projects.go_to_project_discussions') }}
          </Link>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import StatusBadge from '@/Components/Manager/StatusBadge.vue'
import MilestoneList from '@/Components/Manager/MilestoneList.vue'

const props = defineProps({
  project: Object,
  myRole: String,
  progress: Number,
  totalHours: Number,
})

const activeTab = ref('overview')

const tabs = [
  { id: 'overview', label: t('projects.overview'), icon: 'fa-solid fa-circle-info' },
  { id: 'milestones', label: t('projects.milestones'), icon: 'fa-solid fa-flag' },
  { id: 'files', label: t('common.files'), icon: 'fa-solid fa-file' },
  { id: 'discussions', label: t('projects.discussions'), icon: 'fa-solid fa-comments' },
]

const recentTasks = computed(() => (props.project.tasks ?? []).slice(0, 8))

const isOverdue = computed(() => {
  if (!props.project.due_date) return false
  return new Date(props.project.due_date) < new Date() && props.project.status !== 'completed'
})

const priorityClass = (p) =>
  ({
    urgent: 'bg-red-100 text-red-700',
    high: 'bg-orange-100 text-orange-700',
    medium: 'bg-yellow-100 text-yellow-700',
    low: 'bg-gray-100 text-gray-600',
  })[p] ?? 'bg-gray-100 text-gray-600'
</script>
