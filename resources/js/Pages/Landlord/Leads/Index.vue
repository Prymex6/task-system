<template>
  <LandlordLayout :title="$t('platform.leads')">
    <div class="space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('platform.sales_leads') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ $t('platform.prospective_platform_customers') }}</p>
        </div>
        <button @click="showCreate = true" class="btn-primary text-sm">
          <i class="fa-solid fa-plus mr-1"></i> {{ $t('common.new_lead') }}
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
        <input
          v-model="search"
          @input="apply"
          :placeholder="$t('platform.search_leads')"
          class="input-sm flex-1 min-w-48"
        />
        <select v-model="statusFilter" @change="apply" class="input-sm">
          <option value="">{{ $t('common.all_statuses') }}</option>
          <option value="new">{{ $t('common.new') }}</option>
          <option value="contacted">{{ $t('common.contacted') }}</option>
          <option value="demo">Demo</option>
          <option value="trial">Trial</option>
          <option value="converted">{{ $t('platform.converted') }}</option>
          <option value="lost">{{ $t('platform.lost') }}</option>
        </select>
        <select v-model="sourceFilter" @change="apply" class="input-sm">
          <option value="">{{ $t('platform.all_sources') }}</option>
          <option value="website">{{ $t('common.website') }}</option>
          <option value="referral">{{ $t('platform.referral') }}</option>
          <option value="linkedin">LinkedIn</option>
          <option value="cold_outreach">Cold outreach</option>
        </select>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">{{ $t('common.lead') }}</th>
              <th class="th">{{ $t('common.company') }}</th>
              <th class="th text-center">{{ $t('common.status') }}</th>
              <th class="th text-center">{{ $t('common.source') }}</th>
              <th class="th">{{ $t('common.assigned') }}</th>
              <th class="th">{{ $t('common.date') }}</th>
              <th class="th text-right">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="lead in leads.data" :key="lead.id" class="hover:bg-gray-50">
              <td class="td">
                <div>
                  <p class="font-medium text-gray-900">{{ lead.name }}</p>
                  <p class="text-xs text-gray-400">{{ lead.email }}</p>
                </div>
              </td>
              <td class="td text-gray-600">{{ lead.company ?? '—' }}</td>
              <td class="td text-center">
                <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="statusClass(lead.status)">
                  {{ statusLabel(lead.status) }}
                </span>
              </td>
              <td class="td text-center">
                <span class="text-xs text-gray-500">{{ sourceLabel(lead.source) }}</span>
              </td>
              <td class="td text-gray-600">{{ lead.assigned_to?.name ?? '—' }}</td>
              <td class="td text-gray-400">{{ formatDate(lead.created_at) }}</td>
              <td class="td text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="selected = lead" class="text-xs text-indigo-600 hover:text-indigo-800">
                    {{ $t('common.details') }}
                  </button>
                  <button
                    v-if="lead.status !== 'converted'"
                    @click="convert(lead)"
                    class="text-xs text-green-600 hover:text-green-800"
                  >
                    {{ $t('platform.convert') }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!leads.data?.length" class="py-16 text-center">
          <i class="fa-solid fa-user-plus text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('platform.no_leads_match') }}</p>
        </div>
      </div>

      <Pagination :links="leads.links" />

      <!-- Detail modal -->
      <div
        v-if="selected"
        class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center"
        @click.self="selected = null"
      >
        <div class="bg-white rounded-xl shadow-xl w-[520px] p-5 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">{{ selected.name }}</h3>
            <button @click="selected = null" class="text-gray-400 hover:text-gray-600">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
              <p class="text-xs text-gray-500">Email</p>
              <p class="text-gray-900">{{ selected.email }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">{{ $t('common.phone') }}</p>
              <p class="text-gray-900">{{ selected.phone ?? '—' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">{{ $t('common.company') }}</p>
              <p class="text-gray-900">{{ selected.company ?? '—' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">{{ $t('common.source') }}</p>
              <p class="text-gray-900">{{ sourceLabel(selected.source) }}</p>
            </div>
            <div class="col-span-2">
              <p class="text-xs text-gray-500 mb-1">{{ $t('common.change_status') }}</p>
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="s in statuses"
                  :key="s.value"
                  @click="changeStatus(selected, s.value)"
                  class="text-xs px-2 py-1 rounded-full transition-colors"
                  :class="
                    selected.status === s.value ? statusClass(s.value) : 'bg-gray-100 text-gray-500 hover:bg-gray-200'
                  "
                >
                  {{ s.label }}
                </button>
              </div>
            </div>
            <div class="col-span-2" v-if="selected.notes">
              <p class="text-xs text-gray-500 mb-1">{{ $t('common.notes') }}</p>
              <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ selected.notes }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Create modal -->
      <div
        v-if="showCreate"
        class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center"
        @click.self="showCreate = false"
      >
        <div class="bg-white rounded-xl shadow-xl w-96 p-5">
          <h3 class="font-semibold text-gray-900 mb-4">{{ $t('common.new_lead') }}</h3>
          <form @submit.prevent="createLead" class="space-y-3">
            <div>
              <label class="label">{{ $t('common.full_name') }}</label>
              <input v-model="form.name" class="input" required />
            </div>
            <div>
              <label class="label">Email</label>
              <input v-model="form.email" type="email" class="input" required />
            </div>
            <div>
              <label class="label">{{ $t('common.company') }}</label>
              <input v-model="form.company" class="input" />
            </div>
            <div>
              <label class="label">{{ $t('common.phone') }}</label>
              <input v-model="form.phone" class="input" />
            </div>
            <div>
              <label class="label">{{ $t('common.source') }}</label>
              <select v-model="form.source" class="input">
                <option value="website">{{ $t('common.website') }}</option>
                <option value="referral">{{ $t('platform.referral') }}</option>
                <option value="linkedin">LinkedIn</option>
                <option value="cold_outreach">Cold outreach</option>
                <option value="other">{{ $t('common.other') }}</option>
              </select>
            </div>
            <div>
              <label class="label">{{ $t('common.notes') }}</label>
              <textarea v-model="form.notes" rows="3" class="input"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
              <button type="button" @click="showCreate = false" class="btn-ghost text-sm">
                {{ $t('common.cancel') }}
              </button>
              <button type="submit" class="btn-primary text-sm">{{ $t('platform.save_lead') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </LandlordLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  leads: Object,
  filters: Object,
})

const search = ref(props.filters?.search ?? '')
const statusFilter = ref(props.filters?.status ?? '')
const sourceFilter = ref(props.filters?.source ?? '')
const showCreate = ref(false)
const selected = ref(null)
const form = reactive({ name: '', email: '', company: '', phone: '', source: 'website', notes: '' })

const apply = () => {
  router.get(
    route('landlord.leads.index'),
    {
      search: search.value,
      status: statusFilter.value,
      source: sourceFilter.value,
    },
    { preserveState: true, replace: true },
  )
}

const createLead = () => {
  router.post(route('landlord.leads.store'), form, {
    onSuccess: () => {
      showCreate.value = false
    },
  })
}

const changeStatus = (lead, status) => {
  router.patch(route('landlord.leads.update', lead.id), { status })
}

const convert = (lead) => {
  if (confirm(t('platform.convert_this_lead_into_a_tenant'))) {
    router.post(route('landlord.leads.convert', lead.id))
  }
}

const statuses = [
  { value: 'new', label: t('common.new') },
  { value: 'contacted', label: t('common.contacted') },
  { value: 'demo', label: 'Demo' },
  { value: 'trial', label: 'Trial' },
  { value: 'converted', label: t('platform.converted') },
  { value: 'lost', label: t('platform.lost') },
]

const statusClass = (s) =>
  ({
    new: 'bg-blue-100 text-blue-700',
    contacted: 'bg-yellow-100 text-yellow-700',
    demo: 'bg-purple-100 text-purple-700',
    trial: 'bg-indigo-100 text-indigo-700',
    converted: 'bg-green-100 text-green-700',
    lost: 'bg-red-100 text-red-700',
  })[s] ?? 'bg-gray-100 text-gray-600'

const statusLabel = (s) =>
  ({
    new: t('common.new'),
    contacted: 'Skontaktowany',
    demo: 'Demo',
    trial: 'Trial',
    converted: 'Skonwertowany',
    lost: 'Utracony',
  })[s] ?? s
const sourceLabel = (s) =>
  ({ website: 'WWW', referral: 'Polecenie', linkedin: 'LinkedIn', cold_outreach: 'Cold', other: 'Inne' })[s] ?? s
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')
</script>
