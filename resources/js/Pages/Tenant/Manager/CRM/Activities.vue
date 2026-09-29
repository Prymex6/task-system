<template>
  <ManagerLayout :title="$t('crm.crm_activity')">
    <div class="space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('crm.crm_activity') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ $t('crm.every_contact_with_leads_and_clients') }}</p>
        </div>
        <button @click="showCreate = true" class="btn-primary text-sm">
          <i class="fa-solid fa-plus mr-1"></i> {{ $t('crm.add_activity') }}
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
        <select v-model="filters.type" @change="apply" class="input-sm">
          <option value="">{{ $t('crm.all_types') }}</option>
          <option value="call">{{ $t('crm.call') }}</option>
          <option value="email">Email</option>
          <option value="meeting">{{ $t('common.meeting') }}</option>
          <option value="note">{{ $t('common.note') }}</option>
          <option value="demo">Demo</option>
          <option value="follow_up">Follow-up</option>
        </select>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">{{ $t('common.type') }}</th>
              <th class="th">{{ $t('crm.record') }}</th>
              <th class="th">{{ $t('common.description') }}</th>
              <th class="th">{{ $t('crm.user') }}</th>
              <th class="th">{{ $t('common.date') }}</th>
              <th class="th text-right">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="a in activities.data" :key="a.id" class="hover:bg-gray-50">
              <td class="td">
                <span
                  class="inline-flex items-center gap-1.5 text-xs px-2 py-0.5 rounded-full"
                  :class="typeClass(a.type)"
                >
                  <i :class="typeIcon(a.type)"></i>
                  {{ typeLabel(a.type) }}
                </span>
              </td>
              <td class="td">
                <p class="font-medium text-gray-900">{{ a.subject?.name ?? a.subject?.company_name ?? '—' }}</p>
                <p class="text-xs text-gray-400">{{ a.subject_type }}</p>
              </td>
              <td class="td text-gray-600 max-w-xs truncate">{{ a.description ?? '—' }}</td>
              <td class="td text-gray-600">{{ a.user?.name }}</td>
              <td class="td text-gray-400">{{ formatDate(a.occurred_at) }}</td>
              <td class="td text-right">
                <button @click="del(a)" class="text-xs text-red-400 hover:text-red-600">
                  {{ $t('common.delete') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!activities.data?.length" class="py-16 text-center">
          <i class="fa-solid fa-timeline text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('crm.no_activity') }}</p>
        </div>
      </div>

      <Pagination :links="activities.links" />

      <!-- Create modal -->
      <div
        v-if="showCreate"
        class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center"
        @click.self="showCreate = false"
      >
        <div class="bg-white rounded-xl shadow-xl w-96 p-5">
          <h3 class="font-semibold text-gray-900 mb-4">{{ $t('crm.new_activity') }}</h3>
          <form @submit.prevent="create" class="space-y-3">
            <div>
              <label class="label">{{ $t('common.type') }}</label>
              <select v-model="form.type" class="input" required>
                <option value="call">{{ $t('crm.call') }}</option>
                <option value="email">Email</option>
                <option value="meeting">{{ $t('common.meeting') }}</option>
                <option value="note">{{ $t('common.note') }}</option>
                <option value="demo">Demo</option>
                <option value="follow_up">Follow-up</option>
              </select>
            </div>
            <div>
              <label class="label">{{ $t('crm.record') }}</label>
              <select v-model="form.subject_type" class="input" required>
                <option value="lead">{{ $t('common.lead') }}</option>
                <option value="client">{{ $t('common.client') }}</option>
              </select>
            </div>
            <div>
              <label class="label">{{ $t('crm.record_id') }}</label>
              <input v-model="form.subject_id" type="number" class="input" required />
            </div>
            <div>
              <label class="label">{{ $t('common.description') }}</label>
              <textarea v-model="form.description" rows="3" class="input"></textarea>
            </div>
            <div>
              <label class="label">{{ $t('common.date') }}</label>
              <input v-model="form.occurred_at" type="datetime-local" class="input" />
            </div>
            <div class="flex justify-end gap-2 pt-2">
              <button type="button" @click="showCreate = false" class="btn-ghost text-sm">
                {{ $t('common.cancel') }}
              </button>
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
import Pagination from '@/Components/Pagination.vue'
import { intlLocale } from '@/format'

const props = defineProps({
  activities: Object,
  filters: Object,
})

const showCreate = ref(false)
const filters = reactive({ type: props.filters?.type ?? '' })
const form = reactive({ type: 'call', subject_type: 'lead', subject_id: '', description: '', occurred_at: '' })

const apply = () =>
  router.get(route('tenant.manager.crm.activities.index'), filters, { preserveState: true, replace: true })
const create = () =>
  router.post(route('tenant.manager.crm.activities.store'), form, {
    onSuccess: () => {
      showCreate.value = false
    },
  })
const del = (a) => {
  if (confirm(t('crm.delete_this_activity'))) router.delete(route('tenant.manager.crm.activities.destroy', a.id))
}

const typeClass = (t) =>
  ({
    call: 'bg-blue-100 text-blue-700',
    email: 'bg-indigo-100 text-indigo-700',
    meeting: 'bg-purple-100 text-purple-700',
    note: 'bg-yellow-100 text-yellow-700',
    demo: 'bg-green-100 text-green-700',
    follow_up: 'bg-orange-100 text-orange-700',
  })[t] ?? 'bg-gray-100 text-gray-600'
const typeIcon = (t) =>
  ({
    call: 'fa-solid fa-phone',
    email: 'fa-solid fa-envelope',
    meeting: 'fa-solid fa-users',
    note: 'fa-solid fa-sticky-note',
    demo: 'fa-solid fa-laptop',
    follow_up: 'fa-solid fa-reply',
  })[t] ?? 'fa-solid fa-circle'
const typeLabel = (t) =>
  ({ call: 'Rozmowa', email: 'Email', meeting: 'Spotkanie', note: 'Notatka', demo: 'Demo', follow_up: 'Follow-up' })[
    t
  ] ?? t
const formatDate = (d) =>
  d ? new Date(d).toLocaleDateString(intlLocale(), { day: 'numeric', month: 'short', year: 'numeric' }) : '—'
</script>
