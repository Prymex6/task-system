<template>
  <ManagerLayout :title="staff.name">
    <div class="space-y-6">
      <div class="page-header">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.staff.index')" class="btn-ghost btn-sm"
            ><i class="fa-solid fa-arrow-left"></i
          ></Link>
          <div>
            <h1 class="page-title">{{ staff.name }}</h1>
            <p class="page-subtitle">{{ roleLabel(staff.workspace_role) }} · {{ staff.email }}</p>
          </div>
        </div>
        <div class="flex gap-2">
          <button v-if="staff.is_active" @click="deactivate" class="btn-secondary text-red-600">
            <i class="fa-solid fa-user-slash"></i> {{ $t('platform.deactivate') }}
          </button>
          <button v-else @click="activate" class="btn-secondary text-emerald-600">
            <i class="fa-solid fa-user-check"></i> {{ $t('common.activate') }}
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Profile card -->
        <div class="space-y-4">
          <div class="card p-5">
            <div class="flex flex-col items-center text-center gap-3 mb-4">
              <div
                class="w-20 h-20 rounded-full bg-indigo-100 flex items-center justify-center text-3xl font-bold text-indigo-600"
              >
                {{ staff.name.charAt(0).toUpperCase() }}
              </div>
              <div>
                <div class="font-bold text-gray-900 text-lg">{{ staff.name }}</div>
                <div class="text-sm text-gray-500">{{ staff.email }}</div>
                <span :class="roleColor(staff.workspace_role)" class="badge mt-1">{{
                  roleLabel(staff.workspace_role)
                }}</span>
              </div>
            </div>
            <div class="space-y-2 text-sm">
              <div v-if="staff.phone" class="flex gap-2">
                <i class="fa-solid fa-phone w-4 text-gray-400"></i>
                <span class="text-gray-600">{{ staff.phone }}</span>
              </div>
              <div class="flex gap-2 items-center">
                <i
                  class="fa-solid fa-circle w-4 text-center"
                  :class="staff.is_active ? 'text-emerald-500' : 'text-gray-300'"
                ></i>
                <span class="text-gray-600">{{ staff.is_active ? $t('common.active') : $t('common.inactive') }}</span>
              </div>
              <div v-if="staff.last_seen_at" class="flex gap-2">
                <i class="fa-solid fa-clock w-4 text-gray-400"></i>
                <span class="text-gray-500 text-xs">{{ $t('manager.last') }} {{ formatDate(staff.last_seen_at) }}</span>
              </div>
            </div>
          </div>

          <!-- Stats -->
          <div class="card p-5">
            <h3 class="section-title">{{ $t('platform.statistics') }}</h3>
            <div class="grid grid-cols-2 gap-3 text-center">
              <div class="bg-indigo-50 rounded-lg p-3">
                <div class="text-2xl font-bold text-indigo-700">{{ stats.projects }}</div>
                <div class="text-xs text-gray-500">{{ $t('portal.projects') }}</div>
              </div>
              <div class="bg-amber-50 rounded-lg p-3">
                <div class="text-2xl font-bold text-amber-700">{{ stats.tasks }}</div>
                <div class="text-xs text-gray-500">{{ $t('manager.tasks') }}</div>
              </div>
              <div class="bg-blue-50 rounded-lg p-3">
                <div class="text-2xl font-bold text-blue-700">{{ formatHours(stats.hours) }}</div>
                <div class="text-xs text-gray-500">{{ $t('manager.hours') }}</div>
              </div>
              <div class="bg-emerald-50 rounded-lg p-3">
                <div class="text-2xl font-bold text-emerald-700">{{ stats.done_tasks }}</div>
                <div class="text-xs text-gray-500">{{ $t('common.completed_2') }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Projects & Tasks -->
        <div class="lg:col-span-2 space-y-4">
          <!-- Assigned projects -->
          <div class="card">
            <div class="card-header">
              <h2 class="section-title mb-0">{{ $t('common.projects') }}</h2>
            </div>
            <div class="divide-y divide-gray-100">
              <div v-if="!staff.projects?.length" class="px-6 py-6 text-center text-sm text-gray-400">
                {{ $t('common.no_projects') }}
              </div>
              <div
                v-for="p in staff.projects"
                :key="p.id"
                class="px-6 py-3 flex items-center justify-between hover:bg-gray-50"
              >
                <Link
                  :href="route('tenant.manager.projects.show', p.id)"
                  class="text-sm font-medium text-gray-800 hover:text-indigo-600"
                >
                  {{ p.name }}
                </Link>
                <div class="flex items-center gap-3">
                  <span class="text-xs text-gray-400 capitalize">{{ p.pivot?.project_role ?? '—' }}</span>
                  <StatusBadge :status="p.status" />
                </div>
              </div>
            </div>
          </div>

          <!-- Assigned tasks -->
          <div class="card">
            <div class="card-header">
              <h2 class="section-title mb-0">{{ $t('common.recent_tasks') }}</h2>
            </div>
            <div class="divide-y divide-gray-100">
              <div v-if="!staff.tasks?.length" class="px-6 py-6 text-center text-sm text-gray-400">
                {{ $t('common.no_tasks') }}
              </div>
              <div
                v-for="task in staff.tasks"
                :key="task.id"
                class="px-6 py-3 flex items-center justify-between hover:bg-gray-50"
              >
                <Link
                  :href="route('tenant.manager.tasks.show', task.id)"
                  class="text-sm text-gray-800 hover:text-indigo-600"
                >
                  {{ task.title }}
                </Link>
                <div class="flex items-center gap-2">
                  <span class="text-xs text-gray-400">{{ task.project?.name }}</span>
                  <StatusBadge :status="task.status?.name ?? 'open'" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { Link, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import StatusBadge from '@/Components/Manager/StatusBadge.vue'
import { intlLocale } from '@/format'

const props = defineProps({
  staff: Object,
  stats: { type: Object, default: () => ({ projects: 0, tasks: 0, hours: 0, done_tasks: 0 }) },
})

const deactivate = () => {
  if (!confirm(t('manager.deactivate_this_employee'))) return
  useForm({}).post(route('tenant.manager.staff.deactivate', props.staff.id))
}

const activate = () => {
  useForm({}).post(route('tenant.manager.staff.activate', props.staff.id))
}

const roleLabel = (r) =>
  ({
    owner: t('common.owner'),
    admin: 'Admin',
    manager: 'Manager',
    member: t('manager.member'),
    guest: t('manager.guest'),
  })[r] ?? r
const roleColor = (r) =>
  ({ owner: 'badge-purple', admin: 'badge-red', manager: 'badge-orange', member: 'badge-blue', guest: 'badge-gray' })[
    r
  ] ?? 'badge-gray'
const formatDate = (d) => (d ? new Date(d).toLocaleString(intlLocale()) : '—')
const formatHours = (h) => {
  const hrs = Math.floor(h ?? 0)
  const m = Math.round(((h ?? 0) - hrs) * 60)
  return m > 0 ? `${hrs}h ${m}m` : `${hrs}h`
}
</script>
