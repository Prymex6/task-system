<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ title }}</h1>
        <p v-if="description" class="text-sm text-gray-500 mt-0.5">{{ description }}</p>
      </div>
      <button @click="openCreate" class="btn-primary text-sm">
        <i class="fa-solid fa-plus mr-1"></i> {{ addText }}
      </button>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <table class="min-w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th v-for="field in fields" :key="field.key" class="th">{{ field.label }}</th>
            <th v-if="countKey" class="th text-right">{{ countText }}</th>
            <th class="th text-right">{{ $t('common.actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="row in rows" :key="row.id" class="hover:bg-gray-50">
            <td v-for="field in fields" :key="field.key" class="td">
              <span v-if="field.type === 'color'" class="inline-flex items-center gap-2">
                <span
                  class="w-3 h-3 rounded-full border border-black/10"
                  :style="{ background: row[field.key] }"
                ></span>
                <code class="text-xs text-gray-500">{{ row[field.key] }}</code>
              </span>
              <span v-else-if="field.type === 'checkbox'">
                <i v-if="row[field.key]" class="fa-solid fa-check text-green-600"></i>
                <span v-else class="text-gray-300">—</span>
              </span>
              <span v-else-if="field.type === 'number'" class="tabular-nums">{{ row[field.key] }}</span>
              <span v-else class="text-gray-800">{{ row[field.key] || '—' }}</span>
            </td>
            <td v-if="countKey" class="td text-right text-gray-400 tabular-nums">{{ row[countKey] ?? 0 }}</td>
            <td class="td text-right">
              <div class="flex items-center justify-end gap-3">
                <button @click="openEdit(row)" class="text-xs text-indigo-600 hover:text-indigo-800">
                  {{ $t('common.edit') }}
                </button>
                <button @click="remove(row)" class="text-xs text-red-400 hover:text-red-600">
                  {{ $t('common.delete') }}
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="!rows.length" class="py-16 text-center">
        <i :class="emptyIcon" class="text-4xl text-gray-300 mb-3"></i>
        <p class="text-gray-500">{{ emptyText }}</p>
      </div>
    </div>

    <div v-if="open" class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center" @click.self="close">
      <div class="bg-white rounded-xl shadow-xl w-96 p-5">
        <h3 class="font-semibold text-gray-900 mb-4">{{ editing ? editText : addText }}</h3>

        <form @submit.prevent="save" class="space-y-3">
          <div v-for="field in fields" :key="field.key">
            <label v-if="field.type !== 'checkbox'" class="label">{{ field.label }}</label>

            <div v-if="field.type === 'color'" class="flex items-center gap-3">
              <input v-model="form[field.key]" type="color" class="w-10 h-10 rounded cursor-pointer border-0 p-0.5" />
              <input v-model="form[field.key]" class="input flex-1" />
            </div>

            <label v-else-if="field.type === 'checkbox'" class="flex items-center gap-2 text-sm text-gray-700">
              <input v-model="form[field.key]" type="checkbox" />
              {{ field.label }}
            </label>

            <select
              v-else-if="field.type === 'select'"
              v-model="form[field.key]"
              class="input"
              :required="field.required"
            >
              <option v-for="option in field.options" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>

            <textarea
              v-else-if="field.type === 'textarea'"
              v-model="form[field.key]"
              rows="3"
              class="input"
              :required="field.required"
            ></textarea>

            <input
              v-else
              v-model="form[field.key]"
              :type="field.type ?? 'text'"
              :step="field.step"
              class="input"
              :required="field.required"
            />
          </div>

          <p v-if="errorText" class="text-xs text-red-600">{{ errorText }}</p>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="close" class="btn-ghost text-sm">{{ $t('common.cancel') }}</button>
            <button type="submit" class="btn-primary text-sm">{{ $t('common.save') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { computed, reactive, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

/**
 * A table with a create/edit modal for one of the small dictionaries the
 * workspace is configured with — task statuses, tax rates, departments and
 * the rest. They all have the same shape, so they share one screen rather
 * than a dozen near-identical copies.
 */
// The label defaults are resolved below rather than here: defineProps is
// hoisted out of setup(), so it cannot reach the translator.
const props = defineProps({
  rows: { type: Array, default: () => [] },
  fields: { type: Array, required: true },
  routeBase: { type: String, required: true },
  title: { type: String, required: true },
  description: { type: String, default: '' },
  addLabel: { type: String, default: '' },
  editLabel: { type: String, default: '' },
  emptyLabel: { type: String, default: '' },
  emptyIcon: { type: String, default: 'fa-solid fa-list' },
  countKey: { type: String, default: '' },
  countLabel: { type: String, default: '' },
})

const addText = computed(() => props.addLabel || t('common.add'))
const editText = computed(() => props.editLabel || t('common.edit'))
const emptyText = computed(() => props.emptyLabel || t('common.no_entries'))
const countText = computed(() => props.countLabel || t('common.uses'))

const open = ref(false)
const editing = ref(null)
const form = reactive({})

const errors = computed(() => usePage().props.errors ?? {})
const errorText = computed(() => Object.values(errors.value)[0] ?? '')

const blank = () => {
  for (const field of props.fields) {
    form[field.key] = field.type === 'checkbox' ? false : (field.default ?? '')
  }
}

const openCreate = () => {
  editing.value = null
  blank()
  open.value = true
}

const openEdit = (row) => {
  editing.value = row
  for (const field of props.fields) {
    form[field.key] = row[field.key] ?? (field.type === 'checkbox' ? false : '')
  }
  open.value = true
}

const close = () => {
  open.value = false
  editing.value = null
}

const save = () => {
  const options = { preserveScroll: true, onSuccess: close }

  if (editing.value) {
    router.put(route(`${props.routeBase}.update`, editing.value.id), { ...form }, options)
  } else {
    router.post(route(`${props.routeBase}.store`), { ...form }, options)
  }
}

const remove = (row) => {
  const name = row.name ?? row.id
  if (confirm(`Usunąć "${name}"?`)) {
    router.delete(route(`${props.routeBase}.destroy`, row.id), { preserveScroll: true })
  }
}
</script>
