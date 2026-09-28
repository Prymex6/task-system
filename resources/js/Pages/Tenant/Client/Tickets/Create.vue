<template>
  <ClientLayout :title="$t('common.new_ticket')">
    <div class="space-y-5 max-w-2xl">
      <div class="flex items-center gap-3">
        <Link :href="route('tenant.portal.tickets')" class="text-sm text-indigo-600 hover:text-indigo-700">{{
          $t('portal.tickets_2')
        }}</Link>
        <span class="text-gray-300">/</span>
        <h1 class="text-xl font-bold text-gray-900">{{ $t('common.new_ticket') }}</h1>
      </div>

      <form @submit.prevent="submit" class="space-y-5 bg-white rounded-xl border border-gray-200 p-6">
        <div>
          <label class="label">{{ $t('common.subject') }} <span class="text-red-500">*</span></label>
          <input
            v-model="form.subject"
            type="text"
            class="input"
            :class="{ 'input-error': form.errors.subject }"
            required
          />
          <p v-if="form.errors.subject" class="form-error">{{ form.errors.subject }}</p>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="label">{{ $t('common.priority') }}</label>
            <select v-model="form.priority" class="select">
              <option value="low">{{ $t('common.low') }}</option>
              <option value="medium">{{ $t('common.medium') }}</option>
              <option value="high">{{ $t('common.high') }}</option>
              <option value="urgent">{{ $t('common.urgent') }}</option>
            </select>
          </div>
          <div>
            <label class="label">{{ $t('common.department') }}</label>
            <select v-model="form.department" class="select">
              <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
            </select>
          </div>
        </div>
        <div>
          <label class="label">{{ $t('common.description') }} <span class="text-red-500">*</span></label>
          <textarea
            v-model="form.message"
            class="textarea"
            rows="6"
            :placeholder="$t('portal.describe_the_problem_or_question_in')"
            required
          ></textarea>
          <p v-if="form.errors.message" class="form-error">{{ form.errors.message }}</p>
        </div>
        <div>
          <label class="label">{{ $t('portal.attachments') }}</label>
          <input
            type="file"
            multiple
            @change="(e) => (form.attachments = Array.from(e.target.files))"
            class="input"
            accept="image/*,.pdf,.doc,.docx,.txt"
          />
          <p class="form-hint">{{ $t('portal.up_to_10_mb_per_file') }}</p>
        </div>
        <div class="flex justify-end gap-3">
          <Link :href="route('tenant.portal.tickets')" class="btn-secondary">{{ $t('common.cancel') }}</Link>
          <button type="submit" :disabled="form.processing" class="btn-primary">
            <i v-if="form.processing" class="fa-solid fa-spinner fa-spin"></i>
            {{ $t('portal.submit_ticket') }}
          </button>
        </div>
      </form>
    </div>
  </ClientLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { Link, useForm } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'

// The departments come from the workspace's own list; there is no sensible
// hard-coded fallback, and defineProps is hoisted so it could not translate
// one anyway.
const props = defineProps({
  departments: { type: Array, default: () => [] },
})

const form = useForm({
  subject: '',
  priority: 'medium',
  department: props.departments[0] ?? '',
  message: '',
  attachments: [],
})

const submit = () => {
  form.post(route('tenant.portal.tickets.store'), {
    forceFormData: true,
  })
}
</script>
