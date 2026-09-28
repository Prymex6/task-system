<template>
  <Head :title="$t('portal.client_portal_sign_in')" />

  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full space-y-6">
      <div class="text-center">
        <div class="w-14 h-14 bg-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <i class="fa-solid fa-briefcase text-white text-xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('common.client_portal') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $t('portal.sign_in_to_see_your_projects') }}</p>
      </div>

      <div v-if="$page.props.errors?.throttle" class="alert-error">
        <i class="fa-solid fa-triangle-exclamation"></i>
        {{ $page.props.errors.throttle }}
      </div>

      <div v-if="$page.props.flash?.success" class="alert-success">
        <i class="fa-solid fa-circle-check"></i>
        {{ $page.props.flash.success }}
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
              autofocus
              autocomplete="username"
              class="input"
              :class="{ 'input-error': form.errors.email }"
              :placeholder="$t('common.jane_example_com')"
            />
            <p v-if="form.errors.email" class="form-error">{{ form.errors.email }}</p>
          </div>

          <div>
            <div class="flex items-center justify-between mb-1">
              <label for="password" class="label mb-0">{{ $t('common.password') }}</label>
              <Link
                :href="route('tenant.client.password.request')"
                class="text-xs text-indigo-600 hover:text-indigo-700"
              >
                {{ $t('auth.forgotten_password') }}
              </Link>
            </div>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              autocomplete="current-password"
              class="input"
              :class="{ 'input-error': form.errors.password }"
              placeholder="••••••••"
            />
            <p v-if="form.errors.password" class="form-error">{{ form.errors.password }}</p>
          </div>

          <div class="flex items-center gap-2">
            <input id="remember" v-model="form.remember" type="checkbox" class="checkbox" />
            <label for="remember" class="text-sm text-gray-600">{{ $t('common.stay_signed_in') }}</label>
          </div>

          <button type="submit" :disabled="form.processing" class="btn-primary w-full py-3">
            <i v-if="form.processing" class="fa-solid fa-spinner fa-spin"></i>
            {{ form.processing ? 'Logowanie...' : $t('portal.sign_in') }}
          </button>
        </form>
      </div>

      <p class="text-center text-xs text-gray-400">{{ $t('portal.your_account_is_created_by_the') }}</p>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const submit = () => {
  form.post(route('tenant.client.login'), {
    onFinish: () => form.reset('password'),
  })
}
</script>
