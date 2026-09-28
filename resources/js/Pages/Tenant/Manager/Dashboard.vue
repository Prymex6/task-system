<template>
  <ManagerLayout title="Dashboard">
    <div class="space-y-6">
      <!-- Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Dashboard</h1>
          <p class="page-subtitle">
            {{ $t('portal.hello') }} {{ $page.props.auth.user?.name
            }}{{ $t('manager.here_is_how_your_workspace_looks') }}
          </p>
        </div>
        <Link :href="route('tenant.manager.tasks.create')" class="btn-primary">
          <i class="fa-solid fa-plus"></i> {{ $t('common.new_task') }}
        </Link>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card">
          <div class="stat-icon bg-indigo-100 text-indigo-600">
            <i class="fa-solid fa-diagram-project"></i>
          </div>
          <div>
            <div class="stat-value">{{ stats.projects.active }}</div>
            <div class="stat-label">{{ $t('manager.active_projects') }}</div>
            <div class="text-xs text-gray-400 mt-0.5">{{ stats.projects.total }} {{ $t('common.in_total') }}</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-amber-100 text-amber-600">
            <i class="fa-solid fa-list-check"></i>
          </div>
          <div>
            <div class="stat-value">{{ stats.tasks.open }}</div>
            <div class="stat-label">{{ $t('manager.open_tasks') }}</div>
            <div class="text-xs text-gray-400 mt-0.5">{{ stats.tasks.overdue }} {{ $t('manager.overdue') }}</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-emerald-100 text-emerald-600">
            <i class="fa-solid fa-file-invoice-dollar"></i>
          </div>
          <div>
            <div class="stat-value">{{ formatMoney(stats.finance.unpaid) }}</div>
            <div class="stat-label">{{ $t('manager.pending_invoices') }}</div>
            <div class="text-xs text-gray-400 mt-0.5">{{ stats.finance.overdue }} {{ $t('manager.overdue') }}</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-blue-100 text-blue-600">
            <i class="fa-solid fa-headset"></i>
          </div>
          <div>
            <div class="stat-value">{{ stats.tickets.open }}</div>
            <div class="stat-label">{{ $t('manager.open_tickets') }}</div>
            <div class="text-xs text-gray-400 mt-0.5">{{ stats.tickets.urgent }} {{ $t('manager.urgent') }}</div>
          </div>
        </div>
      </div>

      <!-- Main grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- My Projects -->
        <div class="lg:col-span-2 card">
          <div class="card-header flex items-center justify-between">
            <h2 class="section-title mb-0">{{ $t('common.my_projects') }}</h2>
            <Link :href="route('tenant.manager.projects.index')" class="text-xs text-indigo-600 hover:text-indigo-700">
              {{ $t('manager.see_all') }}
            </Link>
          </div>
          <div class="divide-y divide-gray-100">
            <div v-if="!myProjects.length" class="px-6 py-8 text-center text-sm text-gray-400">
              {{ $t('manager.no_active_projects') }}
            </div>
            <div v-for="project in myProjects" :key="project.id" class="px-6 py-4 hover:bg-gray-50 transition-colors">
              <div class="flex items-center gap-3">
                <div
                  class="w-2 h-2 rounded-full flex-shrink-0"
                  :style="{ backgroundColor: project.color || '#6366f1' }"
                ></div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between gap-2">
                    <Link
                      :href="route('tenant.manager.projects.show', project.id)"
                      class="text-sm font-medium text-gray-900 hover:text-indigo-600 truncate"
                    >
                      {{ project.name }}
                    </Link>
                    <StatusBadge :status="project.status" class="flex-shrink-0" />
                  </div>
                  <div class="mt-1.5 flex items-center gap-3">
                    <div class="progress-bar h-1.5 flex-1">
                      <div class="progress-fill h-1.5" :style="{ width: project.progress + '%' }"></div>
                    </div>
                    <span class="text-xs text-gray-400 flex-shrink-0">{{ project.progress }}%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Upcoming Tasks -->
        <div class="card">
          <div class="card-header flex items-center justify-between">
            <h2 class="section-title mb-0">{{ $t('manager.upcoming_tasks') }}</h2>
            <Link :href="route('tenant.manager.tasks.index')" class="text-xs text-indigo-600 hover:text-indigo-700">
              {{ $t('portal.all') }}
            </Link>
          </div>
          <div class="divide-y divide-gray-100">
            <div v-if="!upcomingTasks.length" class="px-6 py-8 text-center text-sm text-gray-400">
              {{ $t('manager.nothing_due_in_the_next_few') }}
            </div>
            <div v-for="task in upcomingTasks" :key="task.id" class="px-4 py-3 hover:bg-gray-50 transition-colors">
              <Link :href="route('tenant.manager.tasks.show', task.id)" class="flex items-start gap-3 group">
                <span class="w-1.5 h-1.5 rounded-full mt-2 flex-shrink-0" :class="priorityDot(task.priority)"></span>
                <div class="flex-1 min-w-0">
                  <p class="text-sm text-gray-800 group-hover:text-indigo-600 truncate">{{ task.title }}</p>
                  <p class="text-xs text-gray-400 mt-0.5">{{ task.project?.name }}</p>
                </div>
                <span
                  v-if="task.due_date"
                  class="text-xs flex-shrink-0 mt-0.5"
                  :class="isOverdue(task.due_date) ? 'text-red-500 font-medium' : 'text-gray-400'"
                >
                  {{ formatDate(task.due_date) }}
                </span>
              </Link>
            </div>
          </div>
        </div>
      </div>

      <!-- Activity Chart -->
      <div class="card p-6">
        <h2 class="section-title">{{ $t('manager.activity_last_7_days') }}</h2>
        <div class="flex items-end gap-2 h-24 mt-4">
          <div
            v-for="day in activityChart"
            :key="day.date"
            class="flex-1 flex flex-col items-center gap-1 group"
            :title="`${day.label}: ${day.tasks_done} zadań, ${day.hours}h`"
          >
            <div class="w-full flex flex-col items-center justify-end gap-0.5 h-20">
              <div
                class="w-full bg-indigo-500 rounded-t transition-all group-hover:bg-indigo-600"
                :style="{ height: maxActivity > 0 ? (day.tasks_done / maxActivity) * 64 + 'px' : '2px' }"
              ></div>
            </div>
            <span class="text-xs text-gray-400">{{ day.label }}</span>
          </div>
        </div>
        <div class="flex gap-4 mt-3 text-xs text-gray-400">
          <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-sm bg-indigo-500"></div>
            {{ $t('manager.tasks_completed') }}
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import StatusBadge from '@/Components/Manager/StatusBadge.vue'

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      projects: { active: 0, total: 0 },
      tasks: { open: 0, overdue: 0 },
      finance: { unpaid: 0, overdue: 0 },
      tickets: { open: 0, urgent: 0 },
    }),
  },
  myProjects: { type: Array, default: () => [] },
  upcomingTasks: { type: Array, default: () => [] },
  activityChart: { type: Array, default: () => [] },
})

const maxActivity = computed(() => Math.max(...props.activityChart.map((d) => d.tasks_done), 1))

const formatMoney = (val) => new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(val ?? 0)

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('pl-PL', { day: 'numeric', month: 'short' })
}

const isOverdue = (date) => date && new Date(date) < new Date()

const priorityDot = (priority) =>
  ({
    critical: 'bg-red-500',
    high: 'bg-orange-400',
    medium: 'bg-amber-400',
    low: 'bg-gray-300',
  })[priority] ?? 'bg-gray-300'
</script>
