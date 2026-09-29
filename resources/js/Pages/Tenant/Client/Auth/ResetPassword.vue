<template>
  <Head :title="$t('portal.client_portal_new_password')" />

  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full space-y-6">
      <div class="text-center">
        <div class="w-14 h-14 bg-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <i class="fa-solid fa-key text-white text-xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('portal.set_a_new_password') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $t('common.client_portal') }}</p>
      </div>

      <div v-if="!tokenValid" class="alert-error">
        <i class="fa-solid fa-triangle-exclamation"></i>
        {{ $t('auth.that_reset_link_is_invalid_or') }}
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label for="email" class="label">{{ $t('common.e_mail_address') }}</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              :disabled="!tokenValid"
              class="input"
              :class="{ 'input-error': form.errors.email }"
            />
            <p v-if="form.errors.email" class="form-error">{{ form.errors.email }}</p>
          </div>

          <div>
            <label for="password" class="label">{{ $t('common.new_password') }}</label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              :disabled="!tokenValid"
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
              :disabled="!tokenValid"
              class="input"
              :placeholder="$t('auth.repeat_new_password')"
            />
          </div>

          <button type="submit" :disabled="form.processing || !tokenValid" class="btn-primary w-full py-3">
            <i v-if="form.processing" class="fa-solid fa-spinner fa-spin"></i>
            {{ form.processing ? $t('common.saving') : $t('portal.set_a_new_password') }}
          </button>
        </form>
      </div>

      <div class="text-center">
        <Link :href="route('tenant.client.password.request')" class="text-sm text-indigo-600 hover:text-indigo-700">
          {{ $t('auth.send_another_reset_link') }}
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
  token: String,
  email: String,
  tokenValid: Boolean,
})

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post(route('tenant.client.password.update'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>
