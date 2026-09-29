<template>
  <ManagerLayout :title="$t('common.contracts')">
    <div class="space-y-5">
      <div class="page-header">
        <div>
          <h1 class="page-title">{{ $t('common.contracts') }}</h1>
          <p class="page-subtitle">{{ contracts.total }} {{ $t('common.in_total') }}</p>
        </div>
        <Link :href="route('tenant.manager.contracts.create')" class="btn-primary">
          <i class="fa-solid fa-plus"></i> {{ $t('manager.new_contract') }}
        </Link>
      </div>

      <div class="filter-bar">
        <input
          v-model="filters.search"
          type="text"
          :placeholder="$t('common.search')"
          class="input input-sm w-48"
          @input="search"
        />
        <select v-model="filters.status" class="select input-sm w-36" @change="search">
          <option value="">{{ $t('common.all') }}</option>
          <option value="draft">{{ $t('common.draft') }}</option>
          <option value="active">{{ $t('common.active_2') }}</option>
          <option value="signed">{{ $t('manager.signed') }}</option>
          <option value="expired">{{ $t('common.expired') }}</option>
          <option value="cancelled">{{ $t('common.cancelled_2') }}</option>
        </select>
      </div>

      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th class="th">{{ $t('common.number') }}</th>
              <th class="th">{{ $t('common.title') }}</th>
              <th class="th">{{ $t('common.client') }}</th>
              <th class="th">{{ $t('common.status') }}</th>
              <th class="th">{{ $t('common.value') }}</th>
              <th class="th">{{ $t('hr.period') }}</th>
              <th class="th"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="!contracts.data.length">
              <td colspan="7" class="td text-center text-gray-400 py-8">{{ $t('portal.no_contracts') }}</td>
            </tr>
            <tr v-for="c in contracts.data" :key="c.id" class="tr-hover">
              <td class="td font-medium">
                <Link
                  :href="route('tenant.manager.contracts.show', c.id)"
                  class="text-indigo-600 hover:text-indigo-700"
                >
                  {{ c.number }}
                </Link>
              </td>
              <td class="td text-gray-800">{{ c.title }}</td>
              <td class="td text-gray-600">{{ c.client?.company_name ?? c.client?.name }}</td>
              <td class="td"><StatusBadge :status="c.status" /></td>
              <td class="td font-medium">{{ c.value ? formatMoney(c.value) : '—' }}</td>
              <td class="td text-xs text-gray-500">
                {{ formatDate(c.start_date) }}
                <span v-if="c.end_date"> → {{ formatDate(c.end_date) }}</span>
              </td>
              <td class="td">
                <div class="flex gap-1">
                  <Link :href="route('tenant.manager.contracts.show', c.id)" class="btn-ghost btn-sm"
                    ><i class="fa-solid fa-eye"></i
                  ></Link>
                  <a :href="route('tenant.manager.contracts.pdf', c.id)" target="_blank" class="btn-ghost btn-sm"
                    ><i class="fa-solid fa-file-pdf"></i
                  ></a>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <Pagination :links="contracts.links" />
    </div>
  </ManagerLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import StatusBadge from '@/Components/Manager/StatusBadge.vue'
import Pagination from '@/Components/Pagination.vue'
import { intlLocale } from '@/format'

const props = defineProps({
  contracts: Object,
  filters: { type: Object, default: () => ({}) },
})

const filters = reactive({ search: props.filters.search ?? '', status: props.filters.status ?? '' })
const search = () =>
  router.get(route('tenant.manager.contracts.index'), filters, { preserveState: true, replace: true })
const formatMoney = (v) => new Intl.NumberFormat(intlLocale(), { style: 'currency', currency: 'PLN' }).format(v)
const formatDate = (d) => (d ? new Date(d).toLocaleDateString(intlLocale()) : '—')
</script>
