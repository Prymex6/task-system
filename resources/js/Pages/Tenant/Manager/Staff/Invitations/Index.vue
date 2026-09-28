<template>
  <ManagerLayout :title="$t('hr.invitations')">
    <div class="space-y-5">
      <div class="page-header">
        <div>
          <h1 class="page-title">{{ $t('hr.invitations') }}</h1>
          <p class="page-subtitle">{{ $t('manager.invitations_to_this_workspace') }}</p>
        </div>
        <button @click="showInvite = true" class="btn-primary">
          <i class="fa-solid fa-plus"></i> {{ $t('manager.send_invitation') }}
        </button>
      </div>

      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th class="th">E-mail</th>
              <th class="th">{{ $t('common.role') }}</th>
              <th class="th">{{ $t('manager.invited_by') }}</th>
              <th class="th">{{ $t('common.status') }}</th>
              <th class="th">{{ $t('manager.expires') }}</th>
              <th class="th"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="!invitations.length">
              <td colspan="6" class="td text-center text-gray-400 py-8">{{ $t('manager.no_open_invitations') }}</td>
            </tr>
            <tr v-for="inv in invitations" :key="inv.id" class="tr-hover">
              <td class="td font-medium text-gray-800">{{ inv.email }}</td>
              <td class="td">
                <span :class="roleColor(inv.workspace_role)" class="badge text-xs">{{
                  roleLabel(inv.workspace_role)
                }}</span>
              </td>
              <td class="td text-sm text-gray-500">{{ inv.invited_by?.name ?? '—' }}</td>
              <td class="td">
                <span
                  class="badge text-xs"
                  :class="inv.is_expired ? 'badge-red' : inv.accepted_at ? 'badge-green' : 'badge-yellow'"
                >
                  {{ inv.accepted_at ? 'Zaakceptowane' : inv.is_expired ? $t('common.expired_2') : 'Oczekuje' }}
                </span>
              </td>
              <td class="td text-xs text-gray-400">{{ formatDate(inv.expires_at) }}</td>
              <td class="td">
                <div class="flex gap-1">
                  <button
                    v-if="!inv.accepted_at && !inv.is_expired"
                    @click="resend(inv.id)"
                    class="btn-ghost btn-sm"
                    :title="$t('manager.send_again')"
                  >
                    <i class="fa-solid fa-rotate-right"></i>
                  </button>
                  <button @click="cancel(inv.id)" class="btn-ghost btn-sm text-red-500">
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Invite Modal -->
    <div v-if="showInvite" class="modal-backdrop" @click.self="showInvite = false">
      <div class="modal-sm">
        <div class="modal-header">
          <h3 class="modal-title">{{ $t('manager.send_invitation') }}</h3>
          <button @click="showInvite = false" class="btn-ghost btn-sm"><i class="fa-solid fa-times"></i></button>
        </div>
        <form @submit.prevent="sendInvite">
          <div class="modal-body space-y-4">
            <div>
              <label class="label">{{ $t('common.e_mail_address') }} <span class="text-red-500">*</span></label>
              <input
                v-model="inviteForm.email"
                type="email"
                class="input"
                :class="{ 'input-error': inviteForm.errors.email }"
                required
              />
              <p v-if="inviteForm.errors.email" class="form-error">{{ inviteForm.errors.email }}</p>
            </div>
            <div>
              <label class="label">{{ $t('common.role') }} <span class="text-red-500">*</span></label>
              <select v-model="inviteForm.workspace_role" class="select">
                <option value="admin">Admin</option>
                <option value="manager">Manager</option>
                <option value="member">{{ $t('manager.member') }}</option>
                <option value="guest">{{ $t('manager.guest') }}</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" @click="showInvite = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="inviteForm.processing" class="btn-primary">
              <i v-if="inviteForm.processing" class="fa-solid fa-spinner fa-spin"></i>
              {{ $t('manager.send_invitation') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  invitations: { type: Array, default: () => [] },
})

const showInvite = ref(false)

const inviteForm = useForm({ email: '', workspace_role: 'member' })

const sendInvite = () => {
  inviteForm.post(route('tenant.manager.invitations.store'), {
    onSuccess: () => {
      showInvite.value = false
      inviteForm.reset()
    },
  })
}

const resend = (id) => useForm({}).post(route('tenant.manager.invitations.resend', id))

const cancel = (id) => {
  if (!confirm(t('manager.cancel_this_invitation'))) return
  useForm({}).delete(route('tenant.manager.invitations.destroy', id))
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
  ({ admin: 'badge-red', manager: 'badge-orange', member: 'badge-blue', guest: 'badge-gray' })[r] ?? 'badge-gray'
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')
</script>
