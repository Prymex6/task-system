<template>
  <ManagerLayout :title="staff.name">
    <div class="max-w-3xl space-y-5">
      <div class="flex items-center gap-3">
        <Link :href="route('tenant.manager.staff.index')" class="text-gray-400 hover:text-gray-600">
          <i class="fa-solid fa-arrow-left"></i>
        </Link>
        <h1 class="text-2xl font-bold text-gray-900">{{ staff.name }}</h1>
        <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="roleClass(staff.role)">{{
          staff.role
        }}</span>
      </div>

      <!-- Profile info -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('common.basics') }}</h2>
        <form @submit.prevent="saveProfile" class="grid grid-cols-2 gap-4">
          <div>
            <label class="label">{{ $t('common.full_name') }}</label>
            <input v-model="profileForm.name" class="input" required />
          </div>
          <div>
            <label class="label">Email</label>
            <input v-model="profileForm.email" type="email" class="input" required />
          </div>
          <div>
            <label class="label">{{ $t('common.phone') }}</label>
            <input v-model="profileForm.phone" class="input" />
          </div>
          <div>
            <label class="label">{{ $t('common.position') }}</label>
            <input v-model="profileForm.position" class="input" />
          </div>
          <div>
            <label class="label">{{ $t('hr.time_zone') }}</label>
            <input v-model="profileForm.timezone" class="input" placeholder="Europe/Warsaw" />
          </div>
          <div class="flex items-end">
            <button type="submit" class="btn-primary text-sm">{{ $t('common.save_changes') }}</button>
          </div>
        </form>
      </div>

      <!-- Role -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('hr.role') }}</h2>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="r in roles"
            :key="r.value"
            @click="changeRole(r.value)"
            class="px-3 py-1.5 rounded-lg border text-sm font-medium transition-colors"
            :class="
              staff.role === r.value
                ? 'bg-indigo-600 border-indigo-600 text-white'
                : 'border-gray-200 text-gray-600 hover:border-indigo-300'
            "
          >
            {{ r.label }}
          </button>
        </div>
      </div>

      <!-- Reset password -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('common.password') }}</h2>
        <form @submit.prevent="resetPassword" class="flex items-end gap-3">
          <div class="flex-1">
            <label class="label">{{ $t('common.new_password') }}</label>
            <input v-model="pwForm.password" type="password" class="input" required minlength="8" />
          </div>
          <div class="flex-1">
            <label class="label">{{ $t('common.confirm_password') }}</label>
            <input v-model="pwForm.password_confirmation" type="password" class="input" required />
          </div>
          <button type="submit" class="btn-ghost text-sm border border-gray-200">{{ $t('hr.reset_password') }}</button>
        </form>
      </div>

      <!-- Recent activity -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('common.recent_tasks') }}</h2>
        <div class="space-y-2">
          <div
            v-for="task in (staff.tasks ?? []).slice(0, 5)"
            :key="task.id"
            class="flex items-center justify-between text-sm"
          >
            <Link
              :href="route('tenant.manager.tasks.show', task.id)"
              class="text-gray-900 hover:text-indigo-600 truncate flex-1"
              >{{ task.title }}</Link
            >
            <span
              v-if="task.status"
              class="text-xs px-2 py-0.5 rounded-full ml-2"
              :style="{ background: task.status.color + '22', color: task.status.color }"
            >
              {{ task.status.name }}
            </span>
          </div>
          <p v-if="!staff.tasks?.length" class="text-sm text-gray-400">{{ $t('common.no_tasks') }}</p>
        </div>
      </div>

      <!-- Danger zone -->
      <div class="bg-red-50 border border-red-200 rounded-xl p-5">
        <h2 class="text-sm font-semibold text-red-700 mb-3">{{ $t('platform.danger_zone') }}</h2>
        <button @click="deleteUser" class="text-sm text-red-600 hover:text-red-800 font-medium">
          <i class="fa-solid fa-trash mr-1"></i> {{ $t('hr.delete_employee') }}
        </button>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({ staff: Object })

const profileForm = reactive({
  name: props.staff.name,
  email: props.staff.email,
  phone: props.staff.phone ?? '',
  position: props.staff.position ?? '',
  timezone: props.staff.timezone ?? '',
})

const pwForm = reactive({ password: '', password_confirmation: '' })

const roles = [
  { value: 'admin', label: 'Admin' },
  { value: 'manager', label: 'Manager' },
  { value: 'employee', label: t('common.employee') },
  { value: 'viewer', label: t('hr.watcher') },
]

const saveProfile = () => router.put(route('tenant.manager.hr.staff.profile.update', props.staff.id), profileForm)
const changeRole = (r) => router.put(route('tenant.manager.hr.staff.role', props.staff.id), { role: r })
const resetPassword = () =>
  router.post(route('tenant.manager.hr.staff.reset-password', props.staff.id), pwForm, {
    onSuccess: () => Object.assign(pwForm, { password: '', password_confirmation: '' }),
  })
const deleteUser = () => {
  if (confirm(t('hr.delete_this_employee_this_cannot_be')))
    router.delete(route('tenant.manager.hr.staff.delete', props.staff.id))
}

const roleClass = (r) =>
  ({
    admin: 'bg-purple-100 text-purple-700',
    manager: 'bg-indigo-100 text-indigo-700',
    employee: 'bg-blue-100 text-blue-700',
    viewer: 'bg-gray-100 text-gray-600',
  })[r] ?? 'bg-gray-100 text-gray-600'
</script>
