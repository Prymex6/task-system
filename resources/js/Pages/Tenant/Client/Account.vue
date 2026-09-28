<template>
  <ClientLayout :title="$t('nav.my_account')">
    <div class="space-y-6 max-w-xl">
      <h1 class="text-xl font-bold text-gray-900">{{ $t('nav.my_account') }}</h1>

      <!-- Profile -->
      <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        <h2 class="font-semibold text-gray-900">{{ $t('portal.profile') }}</h2>

        <div v-if="$page.props.flash?.success" class="alert-success">
          <i class="fa-solid fa-circle-check"></i>
          {{ $page.props.flash.success }}
        </div>

        <form @submit.prevent="updateProfile" class="space-y-4">
          <div>
            <label class="label">{{ $t('common.full_name') }}</label>
            <input
              v-model="profileForm.name"
              type="text"
              class="input"
              :class="{ 'input-error': profileForm.errors.name }"
              required
            />
            <p v-if="profileForm.errors.name" class="form-error">{{ profileForm.errors.name }}</p>
          </div>
          <div>
            <label class="label">{{ $t('common.e_mail_address') }}</label>
            <input
              v-model="profileForm.email"
              type="email"
              class="input"
              :class="{ 'input-error': profileForm.errors.email }"
              required
            />
            <p v-if="profileForm.errors.email" class="form-error">{{ profileForm.errors.email }}</p>
          </div>
          <div>
            <label class="label">{{ $t('common.phone') }}</label>
            <input v-model="profileForm.phone" type="text" class="input" />
          </div>
          <div>
            <label class="label">{{ $t('common.position') }}</label>
            <input v-model="profileForm.position" type="text" class="input" :placeholder="$t('portal.e_g_director')" />
          </div>
          <div class="flex justify-end">
            <button type="submit" :disabled="profileForm.processing" class="btn-primary">
              <i v-if="profileForm.processing" class="fa-solid fa-spinner fa-spin"></i>
              {{ $t('common.save_changes') }}
            </button>
          </div>
        </form>
      </div>

      <!-- Change password -->
      <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        <h2 class="font-semibold text-gray-900">{{ $t('portal.change_password') }}</h2>
        <form @submit.prevent="changePassword" class="space-y-4">
          <div>
            <label class="label">{{ $t('portal.current_password') }}</label>
            <input
              v-model="passwordForm.current_password"
              type="password"
              class="input"
              :class="{ 'input-error': passwordForm.errors.current_password }"
              required
            />
            <p v-if="passwordForm.errors.current_password" class="form-error">
              {{ passwordForm.errors.current_password }}
            </p>
          </div>
          <div>
            <label class="label">{{ $t('common.new_password') }}</label>
            <input
              v-model="passwordForm.password"
              type="password"
              class="input"
              :class="{ 'input-error': passwordForm.errors.password }"
              required
            />
            <p v-if="passwordForm.errors.password" class="form-error">{{ passwordForm.errors.password }}</p>
          </div>
          <div>
            <label class="label">{{ $t('portal.confirm_new_password') }}</label>
            <input v-model="passwordForm.password_confirmation" type="password" class="input" required />
          </div>
          <div class="flex justify-end">
            <button type="submit" :disabled="passwordForm.processing" class="btn-primary">
              <i v-if="passwordForm.processing" class="fa-solid fa-spinner fa-spin"></i>
              {{ $t('portal.change_password') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </ClientLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'

const props = defineProps({
  contact: Object,
})

const profileForm = useForm({
  name: props.contact?.name ?? '',
  email: props.contact?.email ?? '',
  phone: props.contact?.phone ?? '',
  position: props.contact?.position ?? '',
})

const updateProfile = () => {
  profileForm.put(route('tenant.portal.account.update'))
}

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const changePassword = () => {
  passwordForm.put(route('tenant.portal.account.password'), {
    onSuccess: () => passwordForm.reset(),
  })
}
</script>
