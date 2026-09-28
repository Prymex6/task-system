<template>
  <ManagerLayout :title="$t('automations.new_automation')">
    <div class="max-w-2xl space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('automations.new_automation') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ $t('automations.set_a_trigger_then_conditions_then') }}</p>
        </div>
        <Link :href="route('tenant.manager.automations.index')" class="btn-ghost text-sm">
          <i class="fa-solid fa-arrow-left mr-1"></i> {{ $t('automations.back') }}
        </Link>
      </div>

      <form @submit.prevent="save" class="space-y-5">
        <!-- Name -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <label class="label mb-2">{{ $t('automations.automation_name') }}</label>
          <input v-model="form.name" class="input" required :placeholder="$t('automations.e_g_notify_when_a_task')" />
        </div>

        <!-- Step 1: Trigger -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="flex items-center gap-2 mb-4">
            <div
              class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold"
            >
              1
            </div>
            <h2 class="font-semibold text-gray-900">{{ $t('automations.trigger') }}</h2>
          </div>
          <select v-model="form.trigger_event" class="input" required>
            <option value="">{{ $t('automations.choose_an_event') }}</option>
            <optgroup v-for="group in triggerGroups" :key="group.label" :label="group.label">
              <option v-for="ev in group.events" :key="ev.value" :value="ev.value">{{ ev.label }}</option>
            </optgroup>
          </select>
        </div>

        <!-- Step 2: Conditions -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
              <div
                class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold"
              >
                2
              </div>
              <h2 class="font-semibold text-gray-900">{{ $t('automations.conditions_optional') }}</h2>
            </div>
            <button type="button" @click="addCondition" class="btn-ghost text-xs">
              <i class="fa-solid fa-plus mr-1"></i> {{ $t('automations.add_condition') }}
            </button>
          </div>

          <div v-if="!form.conditions.length" class="text-sm text-gray-400 italic">
            {{ $t('automations.no_conditions_this_will_always_run') }}
          </div>

          <div v-for="(cond, i) in form.conditions" :key="i" class="flex items-center gap-2 mb-2">
            <select v-model="cond.field" class="input-sm flex-1">
              <option value="priority">{{ $t('common.priority') }}</option>
              <option value="status">{{ $t('common.status') }}</option>
              <option value="assignee_id">{{ $t('common.assigned') }}</option>
              <option value="project_id">{{ $t('common.project') }}</option>
            </select>
            <select v-model="cond.operator" class="input-sm w-32">
              <option value="equals">{{ $t('automations.is') }}</option>
              <option value="not_equals">{{ $t('automations.is_not_2') }}</option>
              <option value="contains">{{ $t('automations.contains') }}</option>
            </select>
            <input v-model="cond.value" class="input-sm flex-1" :placeholder="$t('common.value')" />
            <button type="button" @click="form.conditions.splice(i, 1)" class="text-red-400 hover:text-red-600">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
        </div>

        <!-- Step 3: Action -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="flex items-center gap-2 mb-4">
            <div
              class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold"
            >
              3
            </div>
            <h2 class="font-semibold text-gray-900">{{ $t('common.action') }}</h2>
          </div>
          <select v-model="form.action" class="input mb-3" required>
            <option value="">{{ $t('automations.choose_an_action') }}</option>
            <option value="send_email">{{ $t('automations.send_an_e_mail') }}</option>
            <option value="send_notification">{{ $t('automations.send_an_in_app_notification') }}</option>
            <option value="assign_task">{{ $t('automations.assign_the_task_to_somebody') }}</option>
            <option value="change_status">{{ $t('automations.change_the_task_status') }}</option>
            <option value="call_webhook">{{ $t('automations.call_a_webhook') }}</option>
            <option value="add_label">{{ $t('automations.add_a_label') }}</option>
          </select>

          <!-- Action config -->
          <div v-if="form.action === 'send_email'" class="space-y-2">
            <input v-model="form.action_config.to" class="input" placeholder="E-mail odbiorcy lub {{assignee.email}}" />
            <input v-model="form.action_config.subject" class="input" :placeholder="$t('automations.subject')" />
            <textarea
              v-model="form.action_config.body"
              rows="3"
              class="input"
              :placeholder="$t('automations.body')"
            ></textarea>
          </div>
          <div v-else-if="form.action === 'send_notification'" class="space-y-2">
            <select v-model="form.action_config.user_id" class="input">
              <option value="">{{ $t('automations.choose_a_person') }}</option>
              <option v-for="u in staff" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
            <input
              v-model="form.action_config.message"
              class="input"
              :placeholder="$t('automations.notification_text')"
            />
          </div>
          <div v-else-if="form.action === 'assign_task'">
            <select v-model="form.action_config.user_id" class="input">
              <option value="">{{ $t('automations.choose_a_person') }}</option>
              <option v-for="u in staff" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
          </div>
          <div v-else-if="form.action === 'change_status'">
            <select v-model="form.action_config.status_id" class="input">
              <option value="">{{ $t('automations.choose_a_status') }}</option>
              <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div v-else-if="form.action === 'call_webhook'">
            <select v-model="form.action_config.webhook_id" class="input">
              <option value="">{{ $t('automations.choose_a_webhook') }}</option>
              <option v-for="wh in webhooks" :key="wh.id" :value="wh.id">{{ wh.url }}</option>
            </select>
          </div>
        </div>

        <div class="flex justify-end gap-3">
          <Link :href="route('tenant.manager.automations.index')" class="btn-ghost">{{ $t('common.cancel') }}</Link>
          <button type="submit" class="btn-primary">{{ $t('automations.create_automation') }}</button>
        </div>
      </form>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  staff: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
  webhooks: { type: Array, default: () => [] },
})

const form = reactive({
  name: '',
  trigger_event: '',
  conditions: [],
  action: '',
  action_config: {},
  is_active: true,
})

const triggerGroups = [
  {
    label: t('common.tasks'),
    events: [
      { value: 'task.created', label: t('automations.a_task_is_created') },
      { value: 'task.assigned', label: t('automations.a_task_is_assigned') },
      { value: 'task.status_changed', label: t('automations.a_task_changes_status') },
      { value: 'task.due_soon', label: t('automations.a_task_is_nearly_due') },
      { value: 'task.overdue', label: t('automations.a_task_is_overdue') },
    ],
  },
  {
    label: t('common.invoices'),
    events: [
      { value: 'invoice.paid', label: t('automations.an_invoice_has_been_paid') },
      { value: 'invoice.overdue', label: t('automations.an_invoice_is_overdue_2') },
    ],
  },
  {
    label: 'CRM',
    events: [
      { value: 'lead.created', label: t('common.new_lead') },
      { value: 'lead.converted', label: t('automations.a_lead_is_converted') },
      { value: 'deal.stage_changed', label: t('automations.a_deal_moves_stage') },
    ],
  },
  {
    label: 'Support',
    events: [
      { value: 'ticket.created', label: t('automations.a_ticket_is_opened') },
      { value: 'ticket.closed', label: t('automations.a_ticket_is_closed') },
    ],
  },
  {
    label: t('common.projects'),
    events: [{ value: 'project.completed', label: t('automations.a_project_is_completed') }],
  },
]

const addCondition = () => form.conditions.push({ field: 'priority', operator: 'equals', value: '' })

const save = () => {
  router.post(route('tenant.manager.automations.store'), form)
}
</script>
