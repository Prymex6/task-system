<template>
  <ManagerLayout :title="$t('automations.automations')">
    <div class="space-y-5">
      <div class="page-header">
        <div>
          <h1 class="page-title">{{ $t('automations.automations') }}</h1>
          <p class="page-subtitle">{{ $t('automations.rules_that_run_on_tasks_and') }}</p>
        </div>
        <button @click="showCreate = true" class="btn-primary">
          <i class="fa-solid fa-plus"></i> {{ $t('automations.new_rule') }}
        </button>
      </div>

      <!-- Category tabs -->
      <div class="flex gap-2 border-b border-gray-200">
        <button
          v-for="cat in categories"
          :key="cat.key"
          @click="activeCategory = cat.key"
          class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
          :class="
            activeCategory === cat.key
              ? 'border-indigo-600 text-indigo-600'
              : 'border-transparent text-gray-500 hover:text-gray-700'
          "
        >
          {{ cat.label }}
          <span class="ml-1.5 text-xs bg-gray-100 text-gray-600 rounded-full px-1.5 py-0.5">
            {{ filteredRules(cat.key).length }}
          </span>
        </button>
      </div>

      <!-- Rules list -->
      <div class="space-y-3">
        <div v-if="!filteredRules(activeCategory).length" class="card p-8 text-center text-gray-400">
          <i class="fa-solid fa-robot text-3xl mb-3"></i>
          <div>{{ $t('automations.no_rules_in_this_category') }}</div>
          <button @click="showCreate = true" class="btn-primary mt-4">
            {{ $t('automations.create_the_first_rule') }}
          </button>
        </div>

        <div v-for="rule in filteredRules(activeCategory)" :key="rule.id" class="card p-5">
          <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-4 flex-1 min-w-0">
              <div class="mt-0.5">
                <div
                  class="w-10 h-10 rounded-lg flex items-center justify-center text-lg"
                  :class="triggerIcon(rule.trigger_event).bg"
                >
                  <i :class="triggerIcon(rule.trigger_event).icon"></i>
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                  <span class="font-semibold text-gray-900">{{ rule.name }}</span>
                  <span class="badge text-xs" :class="rule.is_active ? 'badge-green' : 'badge-gray'">
                    {{ rule.is_active ? 'Aktywna' : $t('common.off') }}
                  </span>
                </div>
                <div class="text-sm text-gray-500 mb-3">{{ rule.description }}</div>
                <div class="flex flex-wrap items-center gap-2 text-xs">
                  <div class="flex items-center gap-1.5 bg-blue-50 text-blue-700 rounded-lg px-2.5 py-1">
                    <i class="fa-solid fa-bolt"></i>
                    <span
                      >{{ $t('automations.when') }} <strong>{{ triggerLabel(rule.trigger_event) }}</strong></span
                    >
                  </div>
                  <i class="fa-solid fa-arrow-right text-gray-300"></i>
                  <div
                    v-for="(action, i) in rule.actions"
                    :key="i"
                    class="flex items-center gap-1.5 bg-indigo-50 text-indigo-700 rounded-lg px-2.5 py-1"
                  >
                    <i class="fa-solid fa-gear"></i>
                    <span
                      >{{ actionLabel(action.type) }}: <strong>{{ action.value }}</strong></span
                    >
                  </div>
                </div>
                <div class="mt-2 text-xs text-gray-400">
                  {{ $t('automations.ran') }} {{ rule.run_count ?? 0 }} {{ $t('automations.times') }}
                  <span v-if="rule.last_run_at"> {{ $t('automations.last') }} {{ formatDate(rule.last_run_at) }}</span>
                </div>
              </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <button
                @click="toggleActive(rule)"
                class="btn-ghost btn-sm"
                :title="rule.is_active ? $t('common.turn_off') : $t('common.turn_on')"
              >
                <i
                  :class="
                    rule.is_active ? 'fa-solid fa-toggle-on text-indigo-600' : 'fa-solid fa-toggle-off text-gray-400'
                  "
                  class="text-xl"
                ></i>
              </button>
              <button @click="editRule(rule)" class="btn-ghost btn-sm" :title="$t('common.edit')">
                <i class="fa-solid fa-pen"></i>
              </button>
              <button @click="deleteRule(rule.id)" class="btn-ghost btn-sm text-red-500" :title="$t('common.delete')">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit modal -->
    <div v-if="showCreate || editingRule" class="modal-backdrop" @click.self="closeModal">
      <div class="modal-lg">
        <div class="modal-header">
          <h3 class="modal-title">{{ editingRule ? $t('common.edit_rule') : $t('common.new_automation_rule') }}</h3>
          <button @click="closeModal" class="btn-ghost btn-sm"><i class="fa-solid fa-times"></i></button>
        </div>
        <form @submit.prevent="submitRule">
          <div class="modal-body space-y-5">
            <div>
              <label class="label">{{ $t('automations.rule_name') }} <span class="text-red-500">*</span></label>
              <input v-model="form.name" type="text" class="input" required />
            </div>
            <div>
              <label class="label">{{ $t('common.description') }}</label>
              <input
                v-model="form.description"
                type="text"
                class="input"
                :placeholder="$t('automations.optional_description')"
              />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="label">{{ $t('common.category') }}</label>
                <select v-model="form.category" class="select">
                  <option v-for="cat in categories" :key="cat.key" :value="cat.key">{{ cat.label }}</option>
                </select>
              </div>
              <div>
                <label class="label">{{ $t('automations.trigger') }} <span class="text-red-500">*</span></label>
                <select v-model="form.trigger_event" class="select" required>
                  <optgroup v-for="group in triggerGroups" :key="group.label" :label="group.label">
                    <option v-for="t in group.triggers" :key="t.value" :value="t.value">{{ t.label }}</option>
                  </optgroup>
                </select>
              </div>
            </div>

            <!-- Conditions -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="label mb-0">{{ $t('automations.conditions_optional') }}</label>
                <button type="button" @click="addCondition" class="btn-ghost btn-sm text-indigo-600">
                  <i class="fa-solid fa-plus"></i> {{ $t('automations.add_condition') }}
                </button>
              </div>
              <div
                v-if="form.conditions.length === 0"
                class="text-sm text-gray-400 text-center py-3 bg-gray-50 rounded-lg"
              >
                {{ $t('automations.no_conditions_this_rule_always_runs') }}
              </div>
              <div v-for="(cond, i) in form.conditions" :key="i" class="flex gap-2 mb-2 items-center">
                <select v-model="cond.field" class="select flex-1">
                  <option value="priority">{{ $t('automations.task_priority') }}</option>
                  <option value="project_id">{{ $t('common.project') }}</option>
                  <option value="assignee_id">{{ $t('automations.assigned_to') }}</option>
                  <option value="status">{{ $t('common.status') }}</option>
                  <option value="tag">{{ $t('automations.tag') }}</option>
                </select>
                <select v-model="cond.operator" class="select w-36">
                  <option value="equals">{{ $t('automations.is') }}</option>
                  <option value="not_equals">{{ $t('automations.is_not') }}</option>
                  <option value="contains">{{ $t('automations.contains') }}</option>
                </select>
                <input v-model="cond.value" type="text" class="input flex-1" :placeholder="$t('automations.value')" />
                <button type="button" @click="removeCondition(i)" class="btn-ghost btn-sm text-red-400">
                  <i class="fa-solid fa-times"></i>
                </button>
              </div>
            </div>

            <!-- Actions -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="label mb-0">{{ $t('common.actions') }} <span class="text-red-500">*</span></label>
                <button type="button" @click="addAction" class="btn-ghost btn-sm text-indigo-600">
                  <i class="fa-solid fa-plus"></i> {{ $t('automations.add_action') }}
                </button>
              </div>
              <div v-if="form.actions.length === 0" class="text-sm text-red-400 text-center py-3 bg-red-50 rounded-lg">
                {{ $t('automations.add_at_least_one_action') }}
              </div>
              <div v-for="(action, i) in form.actions" :key="i" class="flex gap-2 mb-2 items-center">
                <select v-model="action.type" class="select flex-1">
                  <option value="assign_to">{{ $t('automations.assign_to_somebody') }}</option>
                  <option value="change_status">{{ $t('common.change_status') }}</option>
                  <option value="change_priority">{{ $t('automations.change_the_priority') }}</option>
                  <option value="add_tag">{{ $t('automations.add_a_tag') }}</option>
                  <option value="send_notification">{{ $t('automations.send_a_notification') }}</option>
                  <option value="move_to_project">{{ $t('automations.move_to_a_project') }}</option>
                  <option value="set_due_date">{{ $t('automations.set_a_due_date_days') }}</option>
                  <option value="create_task">{{ $t('automations.create_a_subtask') }}</option>
                  <option value="send_email">{{ $t('automations.send_an_e_mail') }}</option>
                </select>
                <input v-model="action.value" type="text" class="input flex-1" :placeholder="$t('automations.value')" />
                <button type="button" @click="removeAction(i)" class="btn-ghost btn-sm text-red-400">
                  <i class="fa-solid fa-times"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" @click="closeModal" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="form.processing || form.actions.length === 0" class="btn-primary">
              <i v-if="form.processing" class="fa-solid fa-spinner fa-spin"></i>
              {{ editingRule ? 'Zapisz zmiany' : $t('common.create_rule') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref, reactive } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  rules: { type: Array, default: () => [] },
})

const showCreate = ref(false)
const editingRule = ref(null)
const activeCategory = ref('all')

const categories = [
  { key: 'all', label: t('common.all') },
  { key: 'tasks', label: t('common.tasks') },
  { key: 'projects', label: t('common.projects') },
  { key: 'crm', label: 'CRM' },
  { key: 'hr', label: 'HR' },
  { key: 'finance', label: t('common.finance') },
]

const triggerGroups = [
  {
    label: t('common.tasks'),
    triggers: [
      { value: 'task.created', label: t('automations.a_task_is_created') },
      { value: 'task.status_changed', label: t('automations.task_status_changed') },
      { value: 'task.assigned', label: t('automations.a_task_is_assigned_to_somebody') },
      { value: 'task.due_date_approaching', label: t('automations.a_task_is_due_tomorrow') },
      { value: 'task.overdue', label: t('automations.a_task_is_overdue') },
      { value: 'task.completed', label: t('automations.a_task_is_completed') },
      { value: 'task.comment_added', label: t('automations.a_comment_is_added_to_a') },
    ],
  },
  {
    label: t('common.projects'),
    triggers: [
      { value: 'project.created', label: t('automations.a_project_is_created') },
      { value: 'project.status_changed', label: t('automations.project_status_changed') },
      { value: 'project.member_added', label: t('automations.somebody_joins_a_project') },
      { value: 'project.deadline_approaching', label: t('automations.a_project_is_due_within_a') },
    ],
  },
  {
    label: 'CRM',
    triggers: [
      { value: 'lead.created', label: t('common.new_lead') },
      { value: 'lead.status_changed', label: t('automations.lead_status_changed') },
      { value: 'deal.stage_changed', label: t('automations.deal_stage_changed') },
      { value: 'deal.won', label: t('automations.a_deal_is_won') },
      { value: 'deal.lost', label: t('automations.a_deal_is_lost') },
    ],
  },
  {
    label: t('common.finance'),
    triggers: [
      { value: 'invoice.created', label: t('automations.an_invoice_is_issued') },
      { value: 'invoice.overdue', label: t('automations.an_invoice_is_overdue') },
      { value: 'invoice.paid', label: t('automations.an_invoice_is_paid') },
    ],
  },
  {
    label: 'HR',
    triggers: [
      { value: 'leave.requested', label: t('automations.leave_is_requested') },
      { value: 'leave.approved', label: t('automations.leave_is_approved') },
    ],
  },
]

const makeForm = (rule = null) =>
  useForm({
    name: rule?.name ?? '',
    description: rule?.description ?? '',
    category: rule?.category ?? 'tasks',
    trigger_event: rule?.trigger_event ?? 'task.created',
    conditions: rule?.conditions ? JSON.parse(JSON.stringify(rule.conditions)) : [],
    actions: rule?.actions ? JSON.parse(JSON.stringify(rule.actions)) : [],
    is_active: rule?.is_active ?? true,
  })

let form = ref(makeForm())

const filteredRules = (cat) => {
  if (cat === 'all') return props.rules
  return props.rules.filter((r) => r.category === cat)
}

const editRule = (rule) => {
  editingRule.value = rule
  form.value = makeForm(rule)
}

const closeModal = () => {
  showCreate.value = false
  editingRule.value = null
  form.value = makeForm()
}

const addCondition = () => form.value.conditions.push({ field: 'priority', operator: 'equals', value: '' })
const removeCondition = (i) => form.value.conditions.splice(i, 1)
const addAction = () => form.value.actions.push({ type: 'send_notification', value: '' })
const removeAction = (i) => form.value.actions.splice(i, 1)

const submitRule = () => {
  if (editingRule.value) {
    form.value.put(route('tenant.manager.automations.update', editingRule.value.id), {
      onSuccess: closeModal,
    })
  } else {
    form.value.post(route('tenant.manager.automations.store'), {
      onSuccess: closeModal,
    })
  }
}

const toggleActive = (rule) => {
  router.patch(route('tenant.manager.automations.toggle', rule.id), {}, { preserveScroll: true })
}

const deleteRule = (id) => {
  if (!confirm(t('automations.delete_this_automation'))) return
  router.delete(route('tenant.manager.automations.destroy', id), { preserveScroll: true })
}

const triggerLabel = (event) => {
  for (const group of triggerGroups) {
    const found = group.triggers.find((t) => t.value === event)
    if (found) return found.label
  }
  return event
}

const actionLabel = (type) =>
  ({
    assign_to: 'Przypisz do',
    change_status: t('common.change_status_to'),
    change_priority: t('common.change_priority_to'),
    add_tag: 'Dodaj tag',
    send_notification: 'Powiadom',
    move_to_project: t('automations.move_to_a_project'),
    set_due_date: 'Ustaw termin',
    create_task: t('automations.create_a_subtask'),
    send_email: t('common.send_an_e_mail_to'),
  })[type] ?? type

const triggerIcon = (event) => {
  if (event?.startsWith('task.')) return { icon: 'fa-solid fa-check-square text-indigo-600', bg: 'bg-indigo-50' }
  if (event?.startsWith('project.')) return { icon: 'fa-solid fa-diagram-project text-blue-600', bg: 'bg-blue-50' }
  if (event?.startsWith('lead.') || event?.startsWith('deal.'))
    return { icon: 'fa-solid fa-handshake text-amber-600', bg: 'bg-amber-50' }
  if (event?.startsWith('invoice.'))
    return { icon: 'fa-solid fa-file-invoice-dollar text-emerald-600', bg: 'bg-emerald-50' }
  if (event?.startsWith('leave.')) return { icon: 'fa-solid fa-umbrella-beach text-orange-600', bg: 'bg-orange-50' }
  return { icon: 'fa-solid fa-bolt text-gray-600', bg: 'bg-gray-100' }
}

const formatDate = (d) => (d ? new Date(d).toLocaleString('pl-PL') : '—')
</script>
