<template>
  <ManagerLayout :title="$t('manager.profile')">
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">{{ $t('manager.profile') }}</h1>
          <p class="page-subtitle">{{ $t('manager.your_own_details') }}</p>
        </div>
      </div>

      <div class="card p-6 max-w-2xl">
        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('common.full_name') }}</label>
            <input type="text" v-model="form.name" class="input w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('common.e_mail_address') }}</label>
            <input type="email" v-model="form.email" class="input w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{
              $t('manager.new_password_optional')
            }}</label>
            <input type="password" v-model="form.password" class="input w-full" />
          </div>
          <button type="submit" class="btn-primary">{{ $t('common.save_changes') }}</button>
        </form>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  user: Object,
})

const form = useForm({
  name: props.user?.name ?? '',
  email: props.user?.email ?? '',
  password: '',
})

function submit() {
  form.put(route('tenant.manager.profile.update'))
}
</script>
