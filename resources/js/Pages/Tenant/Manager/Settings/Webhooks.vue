<template>
  <ManagerLayout :title="$t('settings.webhooks')">
    <div class="space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('settings.webhooks') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ $t('settings.send_events_to_an_external_url') }}</p>
        </div>
        <button @click="showCreate = true" class="btn-primary text-sm">
          <i class="fa-solid fa-plus mr-1"></i> {{ $t('settings.new_webhook') }}
        </button>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">URL</th>
              <th class="th">{{ $t('settings.events') }}</th>
              <th class="th">{{ $t('common.status') }}</th>
              <th class="th">{{ $t('settings.last_call') }}</th>
              <th class="th text-right">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="wh in webhooks" :key="wh.id" class="hover:bg-gray-50">
              <td class="td">
                <code class="text-xs text-gray-700 bg-gray-100 px-2 py-0.5 rounded">{{ wh.url }}</code>
              </td>
              <td class="td">
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="ev in wh.events ?? []"
                    :key="ev"
                    class="text-xs bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded-full"
                  >
                    {{ labelFor(ev) }}
                  </span>
                </div>
              </td>
              <td class="td">
                <button
                  @click="toggleActive(wh)"
                  class="text-xs px-2 py-0.5 rounded-full font-medium transition-colors"
                  :class="wh.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                >
                  {{ wh.is_active ? 'Aktywny' : $t('common.off_2') }}
                </button>
              </td>
              <td class="td text-xs text-gray-400">{{ formatDate(wh.last_triggered_at) }}</td>
              <td class="td text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="test(wh)" class="text-xs text-indigo-600 hover:text-indigo-800">Test</button>
                  <button @click="del(wh)" class="text-xs text-red-400 hover:text-red-600">
                    {{ $t('common.delete') }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!webhooks.length" class="py-16 text-center">
          <i class="fa-solid fa-webhook text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('settings.no_webhooks_yet_add_the_first') }}</p>
        </div>
      </div>

      <!-- Create modal -->
      <div
        v-if="showCreate"
        class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center"
        @click.self="showCreate = false"
      >
        <div class="bg-white rounded-xl shadow-xl w-[480px] p-5">
          <h3 class="font-semibold text-gray-900 mb-4">{{ $t('settings.new_webhook') }}</h3>
          <form @submit.prevent="create" class="space-y-3">
            <div>
              <label class="label">{{ $t('settings.endpoint_url') }}</label>
              <input v-model="form.url" type="url" class="input" required placeholder="https://..." />
            </div>
            <div>
              <label class="label">{{ $t('settings.secret_optional') }}</label>
              <input
                v-model="form.secret"
                class="input font-mono"
                :placeholder="$t('settings.for_verifying_the_signature')"
              />
            </div>
            <div>
              <label class="label mb-2 block">{{ $t('settings.events') }}</label>
              <div class="grid grid-cols-2 gap-y-1.5 gap-x-3">
                <label v-for="ev in availableEvents" :key="ev" class="flex items-center gap-2 text-sm text-gray-700">
                  <input type="checkbox" :value="ev" v-model="form.events" />
                  {{ labelFor(ev) }}
                </label>
              </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
              <button type="button" @click="showCreate = false" class="btn-ghost text-sm">
                {{ $t('common.cancel') }}
              </button>
              <button type="submit" class="btn-primary text-sm">{{ $t('projects.create') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import axios from 'axios'

defineProps({
  webhooks: { type: Array, default: () => [] },
  availableEvents: { type: Array, default: () => [] },
})

const showCreate = ref(false)
const form = reactive({ url: '', secret: '', events: [] })

const eventLabels = {
  'task.created': t('common.task_created'),
  'task.status_changed': 'Zmiana statusu zadania',
  'invoice.paid': t('common.invoice_paid'),
  'ticket.created': t('automations.a_ticket_is_opened'),
  'ticket.closed': t('automations.a_ticket_is_closed'),
  'deal.stage_changed': 'Zmiana etapu deala',
  'project.completed': t('automations.a_project_is_completed'),
  'lead.converted': 'Lead przekonwertowany',
}

const labelFor = (event) => eventLabels[event] ?? event

const create = () => {
  router.post(route('tenant.manager.webhooks.store'), form, {
    onSuccess: () => {
      showCreate.value = false
      Object.assign(form, { url: '', secret: '', events: [] })
    },
  })
}

const test = async (wh) => {
  await axios.post(route('tenant.manager.webhooks.test', wh.id))
  alert(t('settings.test_request_sent'))
}

const toggleActive = (wh) =>
  router.patch(route('tenant.manager.webhooks.update', wh.id), { is_active: !wh.is_active }, { preserveScroll: true })

const del = (wh) => {
  if (confirm(t('settings.delete_this_webhook'))) router.delete(route('tenant.manager.webhooks.destroy', wh.id))
}

const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')
</script>
