<template>
  <ManagerLayout :title="$t('reports.project_report')">
    <div class="space-y-5">
      <div class="page-header">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.reports.index')" class="btn-ghost btn-sm"
            ><i class="fa-solid fa-arrow-left"></i
          ></Link>
          <h1 class="page-title">{{ $t('reports.project_report') }}</h1>
        </div>
      </div>

      <!-- Summary -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="stat-card">
          <div class="stat-icon bg-indigo-100 text-indigo-600"><i class="fa-solid fa-diagram-project"></i></div>
          <div>
            <div class="stat-value">{{ summary.total }}</div>
            <div class="stat-label">{{ $t('reports.projects_in_total') }}</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-blue-100 text-blue-600"><i class="fa-solid fa-spinner"></i></div>
          <div>
            <div class="stat-value">{{ summary.in_progress }}</div>
            <div class="stat-label">{{ $t('common.in_progress') }}</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-emerald-100 text-emerald-600"><i class="fa-solid fa-circle-check"></i></div>
          <div>
            <div class="stat-value">{{ summary.completed }}</div>
            <div class="stat-label">{{ $t('common.completed') }}</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-red-100 text-red-600"><i class="fa-solid fa-triangle-exclamation"></i></div>
          <div>
            <div class="stat-value">{{ summary.overdue }}</div>
            <div class="stat-label">{{ $t('reports.overdue') }}</div>
          </div>
        </div>
      </div>

      <!-- Project list with progress -->
      <div class="card">
        <div class="card-header">
          <h2 class="section-title mb-0">{{ $t('reports.project_progress') }}</h2>
        </div>
        <div class="divide-y divide-gray-100">
          <div v-if="!projects.length" class="px-6 py-8 text-center text-sm text-gray-400">
            {{ $t('common.no_projects') }}
          </div>
          <div v-for="p in projects" :key="p.id" class="px-6 py-4">
            <div class="flex items-start justify-between gap-4 mb-2">
              <div class="flex items-center gap-2">
                <div class="w-2 h-2 rounded-full" :style="{ backgroundColor: p.color || '#6366f1' }"></div>
                <Link
                  :href="route('tenant.manager.projects.show', p.id)"
                  class="font-medium text-gray-800 hover:text-indigo-600"
                >
                  {{ p.name }}
                </Link>
                <StatusBadge :status="p.status" />
                <span v-if="p.is_overdue" class="badge badge-red text-xs">{{ $t('reports.overdue') }}</span>
              </div>
              <div class="text-right text-sm flex-shrink-0">
                <div class="font-bold text-gray-900">{{ p.progress }}%</div>
                <div class="text-xs text-gray-400">
                  {{ p.done_tasks }}/{{ p.total_tasks }} {{ $t('common.tasks_2') }}
                </div>
              </div>
            </div>
            <div class="progress-bar h-2">
              <div
                class="progress-fill h-2"
                :class="p.is_overdue ? 'bg-red-500' : ''"
                :style="{ width: p.progress + '%' }"
              ></div>
            </div>
            <div class="flex gap-6 mt-2 text-xs text-gray-400">
              <span v-if="p.client"
                ><i class="fa-solid fa-building mr-1"></i>{{ p.client.company_name ?? p.client.name }}</span
              >
              <span v-if="p.due_date"><i class="fa-solid fa-calendar mr-1"></i>{{ formatDate(p.due_date) }}</span>
              <span
                ><i class="fa-solid fa-clock mr-1"></i>{{ formatHours(p.logged_hours) }}
                {{ $t('reports.logged') }}</span
              >
            </div>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import StatusBadge from '@/Components/Manager/StatusBadge.vue'
import { intlLocale } from '@/format'

const props = defineProps({
  projects: { type: Array, default: () => [] },
  summary: { type: Object, default: () => ({ total: 0, in_progress: 0, completed: 0, overdue: 0 }) },
})

const formatDate = (d) => (d ? new Date(d).toLocaleDateString(intlLocale()) : '—')
const formatHours = (h) => {
  const hrs = Math.floor(h ?? 0)
  const m = Math.round(((h ?? 0) - hrs) * 60)
  return m > 0 ? `${hrs}h ${m}m` : `${hrs}h`
}
</script>
