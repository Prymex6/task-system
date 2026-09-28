<template>
  <ManagerLayout :title="$t('settings.custom_fields')">
    <div class="space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('settings.custom_fields') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ $t('settings.extra_fields_on_projects_tasks_and') }}</p>
        </div>
        <button @click="showCreate = true" class="btn-primary text-sm">
          <i class="fa-solid fa-plus mr-1"></i> {{ $t('settings.new_field') }}
        </button>
      </div>

      <!-- Group by model -->
      <div
        v-for="group in grouped"
        :key="group.model"
        class="bg-white rounded-xl border border-gray-200 overflow-hidden"
      >
        <div class="p-4 border-b border-gray-200 bg-gray-50">
          <h2 class="text-sm font-semibold text-gray-700">{{ modelLabel(group.model) }}</h2>
        </div>
        <table class="min-w-full">
          <thead>
            <tr class="border-b border-gray-100">
              <th class="th">{{ $t('common.name') }}</th>
              <th class="th">{{ $t('common.type') }}</th>
              <th class="th">{{ $t('settings.required') }}</th>
              <th class="th">{{ $t('settings.options') }}</th>
              <th class="th"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="field in group.fields" :key="field.id" class="hover:bg-gray-50">
              <td class="td text-sm font-medium text-gray-900">{{ field.name }}</td>
              <td class="td">
                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                  {{ fieldTypeLabel(field.type) }}
                </span>
              </td>
              <td class="td text-center">
                <i
                  :class="field.is_required ? 'fa-solid fa-check text-green-500' : 'fa-solid fa-minus text-gray-300'"
                ></i>
              </td>
              <td class="td text-xs text-gray-500">
                {{ field.options?.join(', ') || '—' }}
              </td>
              <td class="td text-right">
                <button @click="deleteField(field)" class="text-xs text-red-400 hover:text-red-600">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!group.fields.length" class="py-8 text-center text-xs text-gray-400">
          {{ $t('settings.no_custom_fields_here') }}
        </div>
      </div>

      <!-- Create modal -->
      <div
        v-if="showCreate"
        class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center"
        @click.self="showCreate = false"
      >
        <div class="bg-white rounded-xl shadow-xl w-96 p-5">
          <h3 class="font-semibold text-gray-900 mb-4">{{ $t('settings.new_field') }}</h3>
          <form @submit.prevent="create" class="space-y-3">
            <div>
              <label class="label">{{ $t('common.model') }}</label>
              <select v-model="form.model" class="input" required>
                <option value="task">{{ $t('common.task') }}</option>
                <option value="project">{{ $t('common.project') }}</option>
                <option value="client">{{ $t('common.client') }}</option>
                <option value="lead">{{ $t('common.lead') }}</option>
                <option value="invoice">{{ $t('common.invoice') }}</option>
              </select>
            </div>
            <div>
              <label class="label">{{ $t('settings.field_name') }}</label>
              <input v-model="form.label" class="input" required />
            </div>
            <div>
              <label class="label">{{ $t('common.type') }}</label>
              <select v-model="form.type" class="input" required>
                <option value="text">{{ $t('settings.text') }}</option>
                <option value="number">{{ $t('settings.number') }}</option>
                <option value="date">{{ $t('common.date') }}</option>
                <option value="select">{{ $t('settings.dropdown') }}</option>
                <option value="checkbox">Checkbox</option>
                <option value="textarea">{{ $t('settings.longer_text') }}</option>
              </select>
            </div>
            <div v-if="form.type === 'select'">
              <label class="label">{{ $t('settings.options_one_per_line') }}</label>
              <textarea
                v-model="form.options_raw"
                rows="4"
                class="input"
                :placeholder="$t('settings.option_1_option_2_option_3')"
              ></textarea>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
              <input type="checkbox" v-model="form.is_required" />
              {{ $t('settings.required_field') }}
            </label>
            <div class="flex justify-end gap-2 pt-2">
              <button type="button" @click="showCreate = false" class="btn-ghost text-sm">
                {{ $t('common.cancel') }}
              </button>
              <button type="submit" class="btn-primary text-sm">{{ $t('settings.create_field') }}</button>
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

import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({ fields: { type: Array, default: () => [] } })

const showCreate = ref(false)
const form = reactive({ model: 'task', label: '', type: 'text', is_required: false, options_raw: '' })

const grouped = computed(() => {
  const models = ['task', 'project', 'client', 'lead', 'invoice']
  return models.map((m) => ({ model: m, fields: props.fields.filter((f) => f.model === m) }))
})

const modelLabel = (m) =>
  ({ task: 'Zadania', project: 'Projekty', client: 'Klienci', lead: 'Leady', invoice: 'Faktury' })[m] ?? m
const fieldTypeLabel = (t) =>
  ({
    text: t('settings.text'),
    number: 'Liczba',
    date: 'Data',
    select: 'Lista',
    checkbox: 'Checkbox',
    textarea: t('settings.longer_text'),
  })[t] ?? t

const create = () => {
  const payload = {
    ...form,
    options: form.type === 'select' ? form.options_raw.split('\n').filter(Boolean) : [],
  }
  router.post(route('tenant.manager.settings.custom-fields.store'), payload, {
    onSuccess: () => {
      showCreate.value = false
    },
  })
}

const deleteField = (field) => {
  if (confirm(t('settings.delete_this_field_everything_stored_in'))) {
    router.delete(route('tenant.manager.settings.custom-fields.destroy', field.id))
  }
}
</script>
