<template>
  <ManagerLayout :title="$t('settings.integrations')">
    <div class="space-y-5">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('settings.integrations') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $t('settings.connect_this_workspace_to_other_tools') }}</p>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div
          v-for="integration in integrationList"
          :key="integration.type"
          class="bg-white rounded-xl border border-gray-200 p-5"
        >
          <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg flex items-center justify-center text-xl" :class="integration.iconBg">
                <i :class="integration.icon"></i>
              </div>
              <div>
                <h2 class="font-semibold text-gray-900">{{ integration.name }}</h2>
                <p class="text-xs text-gray-500">{{ integration.description }}</p>
              </div>
            </div>
            <span
              class="text-xs px-2 py-0.5 rounded-full font-medium"
              :class="isConnected(integration.type) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
            >
              {{ isConnected(integration.type) ? $t('common.connected') : $t('common.not_connected') }}
            </span>
          </div>

          <div v-if="!isConnected(integration.type)">
            <div v-if="integration.type === 'slack'" class="space-y-2">
              <label class="label">Webhook URL</label>
              <input
                v-model="configs[integration.type].webhook_url"
                class="input text-sm"
                placeholder="https://hooks.slack.com/..."
              />
            </div>
            <div v-else-if="integration.type === 'github'" class="space-y-2">
              <label class="label">Personal Access Token</label>
              <input v-model="configs[integration.type].token" class="input text-sm" type="password" />
            </div>
            <div v-else-if="integration.type === 'google_calendar'" class="space-y-2">
              <label class="label">Calendar ID</label>
              <input v-model="configs[integration.type].calendar_id" class="input text-sm" placeholder="primary" />
            </div>
            <button @click="connect(integration.type)" class="mt-3 btn-primary text-sm w-full">
              {{ $t('settings.connect') }}
            </button>
          </div>
          <div v-else class="flex gap-2">
            <button @click="disconnect(integration.type)" class="btn-danger-sm w-full">
              {{ $t('settings.disconnect') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({ integrations: { type: Array, default: () => [] } })

const integrationList = [
  {
    type: 'slack',
    name: 'Slack',
    description: t('settings.notifications_about_tasks_and_tickets'),
    icon: 'fa-brands fa-slack',
    iconBg: 'bg-purple-100 text-purple-600',
  },
  {
    type: 'github',
    name: 'GitHub',
    description: t('settings.link_commits_to_tasks_with_id'),
    icon: 'fa-brands fa-github',
    iconBg: 'bg-gray-100 text-gray-800',
  },
  {
    type: 'google_calendar',
    name: 'Google Calendar',
    description: t('settings.sync_task_due_dates'),
    icon: 'fa-brands fa-google',
    iconBg: 'bg-blue-100 text-blue-600',
  },
  {
    type: 'zapier',
    name: 'Zapier / Make',
    description: t('settings.webhooks_and_no_code_automation'),
    icon: 'fa-solid fa-bolt',
    iconBg: 'bg-orange-100 text-orange-600',
  },
]

const configs = reactive({
  slack: { webhook_url: '' },
  github: { token: '' },
  google_calendar: { calendar_id: 'primary' },
  zapier: {},
})

const isConnected = (type) => props.integrations.some((i) => i.type === type && i.is_active)

const connect = (type) => {
  router.post(
    route('tenant.manager.settings.integrations.connect'),
    { type, config: configs[type] },
    { preserveScroll: true },
  )
}

const disconnect = (type) => {
  if (confirm(t('settings.disconnect_this_integration'))) {
    router.delete(route('tenant.manager.settings.integrations.disconnect', { type }), { preserveScroll: true })
  }
}
</script>
