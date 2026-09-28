<template>
  <Head :title="$t('auth.accept_invitation')" />

  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full space-y-6">
      <div class="text-center">
        <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <i class="fa-solid fa-envelope-open text-indigo-600 text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('auth.team_invitation') }}</h1>
        <p class="text-sm text-gray-500 mt-2">
          {{ $t('auth.you_were_invited_by') }} <strong>{{ invitation.invited_by }}</strong> {{ $t('auth.to_join_as') }}
          <strong>{{ roleLabel }}</strong
          >.
        </p>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
        <p class="text-sm text-gray-600 mb-6">{{ $t('auth.set_a_password_to_finish_setting') }}</p>

        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label class="label">{{ $t('common.e_mail_address') }}</label>
            <input :value="invitation.email" type="email" disabled class="input bg-gray-50 text-gray-500" />
          </div>

          <div>
            <label for="name" class="label">{{ $t('common.full_name') }}</label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              autofocus
              class="input"
              :class="{ 'input-error': form.errors.name }"
              :placeholder="$t('common.jane_cooper')"
            />
            <p v-if="form.errors.name" class="form-error">{{ form.errors.name }}</p>
          </div>

          <div>
            <label for="password" class="label">{{ $t('common.password') }}</label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              class="input"
              :class="{ 'input-error': form.errors.password }"
              :placeholder="$t('common.at_least_8_characters')"
            />
            <p v-if="form.errors.password" class="form-error">{{ form.errors.password }}</p>
          </div>

          <div>
            <label for="password_confirmation" class="label">{{ $t('common.confirm_password') }}</label>
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              required
              class="input"
              :placeholder="$t('auth.repeat_password')"
            />
          </div>

          <button type="submit" :disabled="form.processing" class="btn-primary w-full py-3">
            <i v-if="form.processing" class="fa-solid fa-spinner fa-spin"></i>
            {{ form.processing ? 'Tworzenie konta...' : $t('common.create_account_and_join') }}
          </button>
        </form>
      </div>

      <p class="text-center text-xs text-gray-400">{{ $t('auth.invitation_expires') }} {{ expiresAt }}.</p>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { Head, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  invitation: Object,
  token: String,
})

const form = useForm({
  token: props.token,
  name: '',
  password: '',
  password_confirmation: '',
})

const roleLabel = computed(() => {
  const labels = {
    admin: 'Administrator',
    manager: 'Manager',
    member: t('common.team_member'),
    guest: t('manager.guest'),
  }
  return labels[props.invitation?.workspace_role] ?? props.invitation?.workspace_role
})

const expiresAt = computed(() => {
  if (!props.invitation?.expires_at) return ''
  return new Date(props.invitation.expires_at).toLocaleDateString('pl-PL')
})

const submit = () => {
  form.post(route('tenant.invitation.accept', props.token), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>
