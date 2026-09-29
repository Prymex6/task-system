<template>
  <ManagerLayout :title="$t('clients.contacts')">
    <div class="max-w-4xl space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('clients.contacts') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ client.company_name || client.name }}</p>
        </div>
        <button class="btn-primary" @click="openCreate">{{ $t('clients.add_contact') }}</button>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">{{ $t('common.full_name') }}</th>
              <th class="th">E-mail</th>
              <th class="th">{{ $t('common.phone') }}</th>
              <th class="th">{{ $t('common.position') }}</th>
              <th class="th text-right">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="c in contacts" :key="c.id" class="hover:bg-gray-50">
              <td class="td text-gray-800">
                {{ c.name }}
                <span
                  v-if="c.is_primary"
                  class="ml-2 text-xs px-1.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700"
                  >{{ $t('clients.primary') }}</span
                >
              </td>
              <td class="td text-gray-600">{{ c.email }}</td>
              <td class="td text-gray-600">{{ c.phone || '—' }}</td>
              <td class="td text-gray-600">{{ c.position || '—' }}</td>
              <td class="td text-right">
                <button class="text-xs text-indigo-600 hover:text-indigo-800" @click="openEdit(c)">
                  {{ $t('common.edit') }}
                </button>
                <button class="text-xs text-red-400 hover:text-red-600 ml-3" @click="remove(c)">
                  {{ $t('common.delete') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!contacts.length" class="py-16 text-center">
          <i class="fa-solid fa-address-book text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('clients.no_contacts_yet') }}</p>
        </div>
      </div>
    </div>

    <div
      v-if="editing"
      class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50"
      @click.self="editing = null"
    >
      <form class="bg-white rounded-xl w-full max-w-md p-5 space-y-4" @submit.prevent="save">
        <h2 class="text-lg font-semibold text-gray-900">
          {{ editing.id ? $t('common.edit_contact') : $t('common.new_contact') }}
        </h2>

        <FormField :label="$t('common.full_name')" :error="form.errors.name" required>
          <input v-model="form.name" type="text" class="input" required />
        </FormField>
        <FormField label="E-mail" :error="form.errors.email" required>
          <input v-model="form.email" type="email" class="input" required />
        </FormField>
        <FormField :label="$t('common.phone')" :error="form.errors.phone">
          <input v-model="form.phone" type="text" class="input" />
        </FormField>
        <FormField :label="$t('common.position')" :error="form.errors.position">
          <input v-model="form.position" type="text" class="input" />
        </FormField>

        <label class="flex items-center gap-2 text-sm text-gray-700">
          <input v-model="form.is_primary" type="checkbox" class="rounded border-gray-300" />
          {{ $t('clients.primary_contact') }}
        </label>
        <label class="flex items-center gap-2 text-sm text-gray-700">
          <input v-model="form.portal_access" type="checkbox" class="rounded border-gray-300" />
          {{ $t('clients.client_portal_access') }}
        </label>

        <div class="flex items-center gap-3 pt-1">
          <button type="submit" class="btn-primary" :disabled="form.processing">{{ $t('common.save') }}</button>
          <button type="button" class="btn-ghost" @click="editing = null">{{ $t('common.cancel') }}</button>
        </div>
      </form>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import FormField from '@/Components/Manager/FormField.vue'

const props = defineProps({
  client: { type: Object, required: true },
  contacts: { type: Array, default: () => [] },
})

const editing = ref(null)

const form = useForm({
  name: '',
  email: '',
  phone: '',
  position: '',
  is_primary: false,
  portal_access: false,
})

const openCreate = () => {
  form.reset()
  form.clearErrors()
  editing.value = {}
}

const openEdit = (contact) => {
  form.clearErrors()
  Object.assign(form, {
    name: contact.name,
    email: contact.email,
    phone: contact.phone ?? '',
    position: contact.position ?? '',
    is_primary: Boolean(contact.is_primary),
    portal_access: Boolean(contact.portal_access),
  })
  editing.value = contact
}

const save = () => {
  const done = { onSuccess: () => (editing.value = null) }

  if (editing.value.id) {
    form.put(route('tenant.manager.clients.contacts.update', [props.client.id, editing.value.id]), done)
  } else {
    form.post(route('tenant.manager.clients.contacts.add', props.client.id), done)
  }
}

const remove = (contact) => {
  if (confirm(t('clients.delete_contact') + contact.name + '?')) {
    router.delete(route('tenant.manager.clients.contacts.remove', [props.client.id, contact.id]))
  }
}
</script>
