<template>
  <ManagerLayout :title="$t('settings.payment_methods')">
    <div class="max-w-2xl space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('settings.payment_methods') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ $t('settings.which_payment_methods_appear_on_invoices') }}</p>
        </div>
        <button @click="showCreate = true" class="btn-primary text-sm">
          <i class="fa-solid fa-plus mr-1"></i> {{ $t('settings.new_method') }}
        </button>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">{{ $t('common.name') }}</th>
              <th class="th">{{ $t('common.description') }}</th>
              <th class="th text-center">{{ $t('common.active_2') }}</th>
              <th class="th text-right">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="m in methods" :key="m.id" class="hover:bg-gray-50">
              <td class="td font-medium text-gray-900">{{ m.name }}</td>
              <td class="td text-gray-500">{{ m.description ?? '—' }}</td>
              <td class="td text-center">
                <button
                  @click="toggle(m)"
                  class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors"
                  :class="m.is_active ? 'bg-indigo-600' : 'bg-gray-200'"
                >
                  <span
                    class="translate-y-0 inline-block h-4 w-4 rounded-full bg-white shadow transition-transform"
                    :class="m.is_active ? 'translate-x-4' : 'translate-x-0'"
                  ></span>
                </button>
              </td>
              <td class="td text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="edit(m)" class="text-xs text-indigo-600 hover:text-indigo-800">
                    {{ $t('common.edit') }}
                  </button>
                  <button @click="del(m)" class="text-xs text-red-400 hover:text-red-600">
                    {{ $t('common.delete') }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!methods.length" class="py-16 text-center">
          <i class="fa-solid fa-credit-card text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('settings.no_payment_methods') }}</p>
        </div>
      </div>

      <!-- Create/Edit modal -->
      <div
        v-if="showCreate || editing"
        class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center"
        @click.self="closeModal"
      >
        <div class="bg-white rounded-xl shadow-xl w-80 p-5">
          <h3 class="font-semibold text-gray-900 mb-4">
            {{ editing ? $t('common.edit_method') : $t('common.new_payment_method') }}
          </h3>
          <form @submit.prevent="save" class="space-y-3">
            <div>
              <label class="label">{{ $t('common.name') }}</label>
              <input v-model="form.name" class="input" required />
            </div>
            <div>
              <label class="label">{{ $t('common.description') }}</label>
              <input v-model="form.description" class="input" />
            </div>
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="form.is_active" /> {{ $t('common.active_2') }}
            </label>
            <div class="flex justify-end gap-2 pt-2">
              <button type="button" @click="closeModal" class="btn-ghost text-sm">{{ $t('common.cancel') }}</button>
              <button type="submit" class="btn-primary text-sm">{{ $t('common.save') }}</button>
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

const props = defineProps({ methods: { type: Array, default: () => [] } })

const showCreate = ref(false)
const editing = ref(null)
const form = reactive({ name: '', description: '', is_active: true })

const edit = (m) => {
  editing.value = m
  Object.assign(form, { name: m.name, description: m.description ?? '', is_active: m.is_active })
}
const closeModal = () => {
  showCreate.value = false
  editing.value = null
  Object.assign(form, { name: '', description: '', is_active: true })
}
const save = () => {
  if (editing.value) router.put(route('payment-methods.update', editing.value.id), form, { onSuccess: closeModal })
  else router.post(route('payment-methods.store'), form, { onSuccess: closeModal })
}
const toggle = (m) => router.put(route('payment-methods.update', m.id), { ...m, is_active: !m.is_active })
const del = (m) => {
  if (confirm(t('settings.delete_this_payment_method'))) router.delete(route('payment-methods.destroy', m.id))
}
</script>
