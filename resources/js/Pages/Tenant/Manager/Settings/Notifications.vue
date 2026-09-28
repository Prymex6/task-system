<template>
  <ManagerLayout :title="$t('common.notifications')">
    <form class="max-w-2xl space-y-5" @submit.prevent="save">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('common.notifications') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $t('settings.which_events_reach_the_team_by') }}</p>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
        <label v-for="opt in options" :key="opt.key" class="flex items-start gap-3 p-4 cursor-pointer hover:bg-gray-50">
          <input v-model="form[opt.key]" type="checkbox" class="mt-0.5 rounded border-gray-300" />
          <span>
            <span class="block text-sm font-medium text-gray-800">{{ opt.label }}</span>
            <span class="block text-xs text-gray-500 mt-0.5">{{ opt.hint }}</span>
          </span>
        </label>
      </div>

      <button type="submit" class="btn-primary" :disabled="form.processing">{{ $t('common.save') }}</button>
    </form>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({ settings: { type: Object, default: () => ({}) } })

const options = [
  {
    key: 'notify_task_assigned',
    label: t('settings.task_assigned'),
    hint: t('settings.when_somebody_assigns_a_task_to'),
  },
  { key: 'notify_task_commented', label: t('settings.comment_on_a_task'), hint: t('settings.a_new_comment_on_a_task') },
  { key: 'notify_task_due_soon', label: t('settings.due_soon'), hint: t('settings.the_day_before_a_task_is') },
  { key: 'notify_invoice_paid', label: t('settings.invoice_paid'), hint: t('settings.when_a_client_settles_up') },
  { key: 'notify_ticket_created', label: t('common.new_ticket'), hint: t('settings.a_ticket_from_a_client') },
  {
    key: 'notify_daily_digest',
    label: t('settings.daily_digest'),
    hint: t('settings.one_e_mail_in_the_morning'),
  },
]

const form = useForm(Object.fromEntries(options.map((o) => [o.key, Boolean(props.settings?.[o.key])])))

const save = () => form.post(route('tenant.manager.settings.notifications.update'), { preserveScroll: true })
</script>
