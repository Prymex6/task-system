<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-lg p-8 space-y-6">
      <div class="text-center">
        <i class="fa-solid fa-mobile-screen text-4xl text-indigo-600 mb-3"></i>
        <h1 class="text-xl font-bold text-gray-900">{{ $t('auth.two_factor_authentication') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $t('auth.enter_the_6_digit_code_from') }}</p>
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <input
          v-model="code"
          type="text"
          inputmode="numeric"
          maxlength="6"
          class="input text-center text-2xl tracking-widest font-mono w-full"
          placeholder="000000"
          autofocus
          required
        />

        <button type="submit" :disabled="code.length < 6" class="btn-primary w-full">{{ $t('auth.verify') }}</button>

        <button type="button" @click="toggleRecovery" class="w-full text-sm text-gray-400 hover:text-gray-600">
          {{ useRecovery ? $t('common.use_an_authenticator_code') : $t('common.use_a_recovery_code') }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const code = ref('')
const useRecovery = ref(false)

const toggleRecovery = () => {
  useRecovery.value = !useRecovery.value
  code.value = ''
}

const submit = () => {
  const endpoint = useRecovery.value ? route('tenant.two-factor.recovery') : route('tenant.two-factor.verify')

  router.post(endpoint, { code: code.value })
}
</script>
