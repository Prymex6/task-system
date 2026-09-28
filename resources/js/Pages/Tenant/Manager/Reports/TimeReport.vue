<template>
  <ManagerLayout :title="$t('reports.time_report')">
    <div class="space-y-5">
      <div class="page-header">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.reports.index')" class="btn-ghost btn-sm"
            ><i class="fa-solid fa-arrow-left"></i
          ></Link>
          <h1 class="page-title">{{ $t('reports.time_report_2') }}</h1>
        </div>
      </div>

      <!-- Filters -->
      <form @submit.prevent="search" class="filter-bar">
        <input v-model="filters.date_from" type="date" class="input input-sm" />
        <span class="text-gray-400">→</span>
        <input v-model="filters.date_to" type="date" class="input input-sm" />
        <select v-model="filters.user_id" class="select input-sm w-40">
          <option value="">{{ $t('common.everyone') }}</option>
          <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
        </select>
        <select v-model="filters.project_id" class="select input-sm w-44">
          <option value="">{{ $t('common.all_projects') }}</option>
          <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
        <button type="submit" class="btn-primary btn-sm">{{ $t('reports.filter') }}</button>
      </form>

      <!-- Summary -->
      <div class="grid grid-cols-3 gap-4">
        <div class="stat-card">
          <div class="stat-icon bg-indigo-100 text-indigo-600"><i class="fa-solid fa-clock"></i></div>
          <div>
            <div class="stat-value">{{ formatHours(summary.total_hours) }}</div>
            <div class="stat-label">{{ $t('reports.total_hours') }}</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-blue-100 text-blue-600"><i class="fa-solid fa-users"></i></div>
          <div>
            <div class="stat-value">{{ summary.users_count }}</div>
            <div class="stat-label">{{ $t('reports.staff') }}</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-purple-100 text-purple-600"><i class="fa-solid fa-diagram-project"></i></div>
          <div>
            <div class="stat-value">{{ summary.projects_count }}</div>
            <div class="stat-label">{{ $t('common.projects') }}</div>
          </div>
        </div>
      </div>

      <!-- By user -->
      <div class="card">
        <div class="card-header">
          <h2 class="section-title mb-0">{{ $t('reports.hours_by_person') }}</h2>
        </div>
        <div class="divide-y divide-gray-100">
          <div v-if="!byUser.length" class="px-6 py-8 text-center text-sm text-gray-400">
            {{ $t('common.nothing_here_yet') }}
          </div>
          <div v-for="row in byUser" :key="row.user_id" class="px-6 py-3">
            <div class="flex items-center justify-between mb-2">
              <span class="font-medium text-gray-800">{{ row.user_name }}</span>
              <span class="font-bold text-gray-900">{{ formatHours(row.total_hours) }}</span>
            </div>
            <div class="space-y-1">
              <div v-for="proj in row.projects" :key="proj.project_id" class="flex items-center gap-3 text-sm">
                <div class="progress-bar h-1.5 flex-1">
                  <div class="progress-fill h-1.5" :style="{ width: (proj.hours / row.total_hours) * 100 + '%' }"></div>
                </div>
                <span class="text-gray-600 w-40 truncate text-xs">{{ proj.project_name }}</span>
                <span class="text-gray-500 text-xs w-12 text-right">{{ formatHours(proj.hours) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- By project -->
      <div class="card">
        <div class="card-header">
          <h2 class="section-title mb-0">{{ $t('reports.hours_by_project') }}</h2>
        </div>
        <div class="divide-y divide-gray-100">
          <div v-if="!byProject.length" class="px-6 py-8 text-center text-sm text-gray-400">
            {{ $t('common.nothing_here_yet') }}
          </div>
          <div v-for="row in byProject" :key="row.project_id" class="px-6 py-3 flex items-center justify-between">
            <span class="text-gray-800 font-medium">{{ row.project_name }}</span>
            <div class="flex items-center gap-6 text-sm">
              <span class="text-gray-500">{{ row.entries_count }} {{ $t('reports.entries') }}</span>
              <span class="font-bold text-gray-900">{{ formatHours(row.total_hours) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  byUser: { type: Array, default: () => [] },
  byProject: { type: Array, default: () => [] },
  summary: { type: Object, default: () => ({ total_hours: 0, users_count: 0, projects_count: 0 }) },
  users: { type: Array, default: () => [] },
  projects: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
})

const filters = reactive({
  date_from: props.filters.date_from ?? '',
  date_to: props.filters.date_to ?? '',
  user_id: props.filters.user_id ?? '',
  project_id: props.filters.project_id ?? '',
})

const search = () => router.get(route('tenant.manager.reports.time'), filters, { preserveState: true, replace: true })
const formatHours = (h) => {
  const hrs = Math.floor(h ?? 0)
  const m = Math.round(((h ?? 0) - hrs) * 60)
  return m > 0 ? `${hrs}h ${m}m` : `${hrs}h`
}
</script>
