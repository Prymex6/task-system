<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-lg p-8 text-center space-y-5">
      <i class="fa-solid fa-clock text-5xl text-orange-400"></i>
      <h1 class="text-xl font-bold text-gray-900">{{ $t('auth.invitation_expired') }}</h1>
      <p class="text-sm text-gray-500">
        {{ $t('auth.that_invitation_has_expired_or_has') }}
      </p>
      <Link :href="route('tenant.login')" class="btn-primary inline-block text-sm px-6">
        {{ $t('auth.go_to_sign_in') }}
      </Link>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
</script>
