<template>
  <ManagerLayout :title="$t('settings.role_permissions')">
    <div class="space-y-6">
      <div class="page-header">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.settings.index')" class="btn-ghost btn-sm"
            ><i class="fa-solid fa-arrow-left"></i
          ></Link>
          <div>
            <h1 class="page-title">{{ $t('settings.role_permissions') }}</h1>
            <p class="page-subtitle">{{ $t('settings.what_each_role_is_allowed_to') }}</p>
          </div>
        </div>
        <button @click="save" :disabled="saving" class="btn-primary">
          <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
          {{ $t('common.save_changes') }}
        </button>
      </div>

      <div v-if="saved" class="alert-success">
        <i class="fa-solid fa-circle-check"></i> {{ $t('settings.permissions_saved') }}
      </div>

      <div class="card overflow-x-auto">
        <table class="min-w-full">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-200">
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-64">
                {{ $t('common.action') }}
              </th>
              <th
                v-for="role in roles"
                :key="role"
                class="px-4 py-3 text-center text-xs font-semibold uppercase w-24"
                :class="roleColor(role)"
              >
                {{ roleLabel(role) }}
              </th>
            </tr>
          </thead>
          <tbody>
            <template v-for="group in groupedActions" :key="group.name">
              <tr class="bg-gray-50">
                <td
                  :colspan="roles.length + 1"
                  class="px-6 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider"
                >
                  {{ group.name }}
                </td>
              </tr>
              <tr v-for="action in group.actions" :key="action" class="border-t border-gray-100 hover:bg-gray-50">
                <td class="px-6 py-2.5 text-sm text-gray-700">{{ actionLabel(action) }}</td>
                <td v-for="role in roles" :key="role" class="px-4 py-2.5 text-center">
                  <input
                    type="checkbox"
                    :checked="permissions[role]?.includes(action)"
                    :disabled="role === 'owner'"
                    class="checkbox"
                    @change="toggle(role, action)"
                  />
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import axios from 'axios'

const props = defineProps({
  permissions: { type: Object, default: () => ({}) },
  roles: { type: Array, default: () => ['admin', 'manager', 'member', 'guest'] },
  actions: { type: Array, default: () => [] },
})

const saving = ref(false)
const saved = ref(false)

const permissions = reactive(
  Object.fromEntries(props.roles.map((role) => [role, [...(props.permissions[role] ?? [])]])),
)

const toggle = (role, action) => {
  if (role === 'owner') return
  const idx = permissions[role].indexOf(action)
  if (idx === -1) {
    permissions[role].push(action)
  } else {
    permissions[role].splice(idx, 1)
  }
}

const save = async () => {
  saving.value = true
  try {
    await axios.post(route('tenant.manager.settings.role-permissions.update'), { permissions })
    saved.value = true
    setTimeout(() => (saved.value = false), 3000)
  } finally {
    saving.value = false
  }
}

// Grouped actions
const groupedActions = [
  {
    name: t('common.projects'),
    actions: ['projects.view', 'projects.create', 'projects.edit', 'projects.delete', 'projects.manage_members'],
  },
  {
    name: t('common.tasks'),
    actions: ['tasks.view', 'tasks.create', 'tasks.edit', 'tasks.delete', 'tasks.assign', 'tasks.log_time'],
  },
  { name: 'CRM', actions: ['crm.clients.view', 'crm.clients.manage', 'crm.leads.manage', 'crm.deals.manage'] },
  {
    name: 'Finanse',
    actions: [
      'finance.invoices.view',
      'finance.invoices.manage',
      'finance.estimates.manage',
      'finance.expenses.view',
      'finance.expenses.approve',
    ],
  },
  { name: 'Support', actions: ['tickets.view', 'tickets.manage', 'kb.manage'] },
  { name: 'HR', actions: ['hr.attendance.view_all', 'hr.leave.approve', 'hr.staff.manage'] },
  { name: 'Raporty', actions: ['reports.view', 'reports.finance'] },
  { name: t('common.settings'), actions: ['settings.manage'] },
]

const roleLabel = (r) =>
  ({ admin: 'Admin', manager: 'Manager', member: t('manager.member'), guest: t('manager.guest') })[r] ?? r
const roleColor = (r) =>
  ({ admin: 'text-red-600', manager: 'text-orange-600', member: 'text-blue-600', guest: 'text-gray-500' })[r] ?? ''

const actionLabel = (action) => {
  const labels = {
    'projects.view': t('common.view_projects'),
    'projects.create': t('common.create_projects'),
    'projects.edit': t('common.edit_projects'),
    'projects.delete': t('common.delete_projects'),
    'projects.manage_members': t('common.manage_members'),
    'tasks.view': t('common.view_tasks'),
    'tasks.create': t('common.create_tasks'),
    'tasks.edit': t('common.edit_tasks'),
    'tasks.delete': t('common.delete_tasks'),
    'tasks.assign': 'Przypisuj zadania',
    'tasks.log_time': 'Loguj czas',
    'crm.clients.view': t('common.view_clients'),
    'crm.clients.manage': t('common.manage_clients'),
    'crm.leads.manage': t('common.manage_leads'),
    'crm.deals.manage': t('common.manage_deals'),
    'finance.invoices.view': t('common.view_invoices'),
    'finance.invoices.manage': t('common.manage_invoices'),
    'finance.estimates.manage': t('common.manage_estimates'),
    'finance.expenses.view': t('common.view_expenses'),
    'finance.expenses.approve': 'Zatwierdzaj wydatki',
    'tickets.view': t('common.view_tickets'),
    'tickets.manage': t('common.manage_tickets'),
    'kb.manage': 'Baza wiedzy',
    'hr.attendance.view_all': t('common.view_attendance'),
    'hr.leave.approve': t('common.approve_leave'),
    'hr.staff.manage': t('common.manage_staff'),
    'reports.view': t('common.view_reports'),
    'reports.finance': 'Raporty finansowe',
    'settings.manage': t('common.workspace_settings'),
  }
  return labels[action] ?? action
}
</script>
