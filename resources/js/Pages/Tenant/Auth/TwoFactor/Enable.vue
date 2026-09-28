<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8 space-y-6">
      <div class="text-center">
        <i class="fa-solid fa-shield-halved text-4xl text-indigo-600 mb-3"></i>
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('auth.turn_on_two_factor_authentication') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $t('auth.scan_this_qr_code_in_your') }}</p>
      </div>

      <div class="flex justify-center">
        <div v-html="qrCodeSvg" class="w-48 h-48"></div>
      </div>

      <div class="bg-gray-50 rounded-lg p-3 text-center">
        <p class="text-xs text-gray-500 mb-1">{{ $t('auth.manual_key') }}</p>
        <code class="text-sm font-mono text-gray-800 break-all select-all">{{ secret }}</code>
      </div>

      <form @submit.prevent="confirm" class="space-y-4">
        <div>
          <label class="label">{{ $t('auth.confirmation_code_6_digits') }}</label>
          <input
            v-model="code"
            type="text"
            inputmode="numeric"
            maxlength="6"
            class="input text-center text-xl tracking-widest"
            placeholder="000000"
            required
          />
        </div>
        <button type="submit" class="btn-primary w-full">{{ $t('auth.turn_on_2fa') }}</button>
      </form>

      <div v-if="recoveryCodes.length" class="mt-4">
        <p class="text-sm font-semibold text-gray-700 mb-2">
          {{ $t('auth.recovery_codes_keep_these_somewhere_safe') }}
        </p>
        <div class="bg-gray-50 rounded-lg p-3 grid grid-cols-2 gap-1">
          <code v-for="rc in recoveryCodes" :key="rc" class="text-xs font-mono text-gray-700 text-center py-0.5">{{
            rc
          }}</code>
        </div>
      </div>

      <div class="text-center">
        <Link :href="route('tenant.manager.profile')" class="text-sm text-gray-400 hover:text-gray-600">
          {{ $t('common.cancel') }}
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  qrCodeSvg: String,
  secret: String,
  recoveryCodes: { type: Array, default: () => [] },
})

const code = ref('')

const confirm = () => {
  router.post(route('tenant.two-factor.confirm'), { code: code.value })
}
</script>
