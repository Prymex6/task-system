<template>
  <ManagerLayout :title="$t('platform.leads')">
    <div class="space-y-5">
      <div class="page-header">
        <div>
          <h1 class="page-title">{{ $t('crm.leads') }}</h1>
          <p class="page-subtitle">{{ leads.total }} {{ $t('crm.leads_in_total') }}</p>
        </div>
        <Link :href="route('tenant.manager.leads.create')" class="btn-primary">
          <i class="fa-solid fa-plus"></i> {{ $t('common.new_lead') }}
        </Link>
      </div>

      <!-- Filters -->
      <div class="filter-bar">
        <input
          v-model="filters.search"
          type="text"
          :placeholder="$t('common.search')"
          class="input input-sm w-48"
          @input="search"
        />
        <select v-model="filters.status" class="select input-sm w-36" @change="search">
          <option value="">{{ $t('common.all_statuses') }}</option>
          <option value="new">{{ $t('common.new') }}</option>
          <option value="contacted">{{ $t('common.contacted') }}</option>
          <option value="qualified">{{ $t('crm.qualified') }}</option>
          <option value="proposal">{{ $t('common.proposal') }}</option>
          <option value="won">{{ $t('common.won') }}</option>
          <option value="lost">{{ $t('common.lost') }}</option>
        </select>
        <select v-model="filters.source" class="select input-sm w-36" @change="search">
          <option value="">{{ $t('platform.all_sources') }}</option>
          <option value="website">{{ $t('crm.website') }}</option>
          <option value="referral">{{ $t('platform.referral') }}</option>
          <option value="cold_call">Cold call</option>
          <option value="linkedin">LinkedIn</option>
          <option value="other">{{ $t('common.other') }}</option>
        </select>
      </div>

      <!-- Table -->
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th class="th">{{ $t('platform.contact_2') }}</th>
              <th class="th">{{ $t('common.company') }}</th>
              <th class="th">{{ $t('common.status') }}</th>
              <th class="th">{{ $t('common.source') }}</th>
              <th class="th">{{ $t('common.value') }}</th>
              <th class="th">{{ $t('common.assigned') }}</th>
              <th class="th">{{ $t('common.date') }}</th>
              <th class="th"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="!leads.data.length">
              <td colspan="8" class="td text-center text-gray-400 py-8">{{ $t('platform.no_leads') }}</td>
            </tr>
            <tr v-for="lead in leads.data" :key="lead.id" class="tr-hover">
              <td class="td">
                <Link
                  :href="route('tenant.manager.leads.show', lead.id)"
                  class="font-medium text-gray-900 hover:text-indigo-600"
                >
                  {{ lead.name }}
                </Link>
                <div class="text-xs text-gray-400">{{ lead.email }}</div>
              </td>
              <td class="td text-gray-600">{{ lead.company }}</td>
              <td class="td">
                <span :class="leadStatusClass(lead.status)" class="badge">{{ leadStatusLabel(lead.status) }}</span>
              </td>
              <td class="td text-gray-500 text-xs">{{ lead.source }}</td>
              <td class="td text-gray-700">{{ lead.value ? formatMoney(lead.value) : '—' }}</td>
              <td class="td text-xs text-gray-500">{{ lead.assigned?.name ?? '—' }}</td>
              <td class="td text-xs text-gray-400">{{ formatDate(lead.created_at) }}</td>
              <td class="td">
                <div class="flex gap-1">
                  <Link :href="route('tenant.manager.leads.show', lead.id)" class="btn-ghost btn-sm">
                    <i class="fa-solid fa-eye"></i>
                  </Link>
                  <Link :href="route('tenant.manager.leads.edit', lead.id)" class="btn-ghost btn-sm">
                    <i class="fa-solid fa-pen"></i>
                  </Link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <Pagination :links="leads.links" />
    </div>
  </ManagerLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { intlLocale } from '@/format'

const { t } = useI18n()

const props = defineProps({
  leads: { type: Object, default: () => ({ data: [] }) },
  filters: { type: Object, default: () => ({}) },
})

const filters = reactive({
  search: props.filters.search ?? '',
  status: props.filters.status ?? '',
  source: props.filters.source ?? '',
})

const search = () => {
  router.get(route('tenant.manager.leads.index'), filters, { preserveState: true, replace: true })
}

const leadStatusLabel = (s) =>
  ({
    new: t('common.new'),
    contacted: t('common.contacted'),
    qualified: t('crm.qualified'),
    proposal: t('common.proposal'),
    won: t('common.won'),
    lost: t('common.lost'),
  })[s] ?? s

const leadStatusClass = (s) =>
  ({
    new: 'badge-blue',
    contacted: 'badge-indigo',
    qualified: 'badge-purple',
    proposal: 'badge-yellow',
    won: 'badge-green',
    lost: 'badge-red',
  })[s] ?? 'badge-gray'

const formatMoney = (v) => new Intl.NumberFormat(intlLocale(), { style: 'currency', currency: 'PLN' }).format(v)
const formatDate = (d) => (d ? new Date(d).toLocaleDateString(intlLocale()) : '—')
</script>
