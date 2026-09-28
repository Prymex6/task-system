<template>
  <Head :title="$t('portal.client_portal_reset_password')" />

  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full space-y-6">
      <div class="text-center">
        <div class="w-14 h-14 bg-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <i class="fa-solid fa-lock-open text-white text-xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('portal.reset_password') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $t('common.client_portal') }}</p>
      </div>

      <div v-if="$page.props.flash?.success" class="alert-success">
        <i class="fa-solid fa-circle-check"></i>
        {{ $page.props.flash.success }}
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
        <p class="text-sm text-gray-600 mb-6">
          {{ $t('portal.give_us_the_e_mail_address') }}
        </p>

        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label for="email" class="label">{{ $t('common.e_mail_address') }}</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              autofocus
              class="input"
              :class="{ 'input-error': form.errors.email }"
              :placeholder="$t('common.jane_example_com')"
            />
            <p v-if="form.errors.email" class="form-error">{{ form.errors.email }}</p>
          </div>

          <button type="submit" :disabled="form.processing" class="btn-primary w-full py-3">
            <i v-if="form.processing" class="fa-solid fa-spinner fa-spin"></i>
            {{ form.processing ? $t('common.sending') : $t('common.send_reset_link') }}
          </button>
        </form>
      </div>

      <div class="text-center">
        <Link :href="route('tenant.client.login')" class="text-sm text-indigo-600 hover:text-indigo-700">
          {{ $t('auth.back_to_sign_in') }}
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({ email: '' })

const submit = () => {
  form.post(route('tenant.client.password.email'))
}
</script>
