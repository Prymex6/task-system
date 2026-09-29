<template>
  <ManagerLayout :title="$t('common.estimates')">
    <div class="space-y-5">
      <div class="page-header">
        <div>
          <h1 class="page-title">{{ $t('common.estimates') }}</h1>
          <p class="page-subtitle">{{ estimates.total }} {{ $t('common.in_total') }}</p>
        </div>
        <Link :href="route('tenant.manager.estimates.create')" class="btn-primary">
          <i class="fa-solid fa-plus"></i> {{ $t('finance.new_estimate') }}
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
          <option value="sent">{{ $t('common.sent') }}</option>
          <option value="accepted">{{ $t('common.accepted') }}</option>
          <option value="rejected">{{ $t('common.rejected') }}</option>
          <option value="expired">{{ $t('common.expired') }}</option>
        </select>
      </div>

      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th class="th">{{ $t('common.number') }}</th>
              <th class="th">{{ $t('common.client') }}</th>
              <th class="th">{{ $t('common.title') }}</th>
              <th class="th">{{ $t('common.status') }}</th>
              <th class="th">{{ $t('common.value') }}</th>
              <th class="th">{{ $t('common.valid_until') }}</th>
              <th class="th"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="!estimates.data.length">
              <td colspan="7" class="td text-center text-gray-400 py-8">{{ $t('portal.no_estimates_2') }}</td>
            </tr>
            <tr v-for="est in estimates.data" :key="est.id" class="tr-hover">
              <td class="td font-medium">
                <Link
                  :href="route('tenant.manager.estimates.show', est.id)"
                  class="text-indigo-600 hover:text-indigo-700"
                >
                  {{ est.number }}
                </Link>
              </td>
              <td class="td text-gray-600">{{ est.client?.company_name ?? est.client?.name }}</td>
              <td class="td text-gray-700">{{ est.title }}</td>
              <td class="td"><StatusBadge :status="est.status" /></td>
              <td class="td font-medium">{{ formatMoney(est.total) }}</td>
              <td class="td text-xs text-gray-400">{{ formatDate(est.valid_until) }}</td>
              <td class="td">
                <div class="flex gap-1">
                  <Link :href="route('tenant.manager.estimates.show', est.id)" class="btn-ghost btn-sm"
                    ><i class="fa-solid fa-eye"></i
                  ></Link>
                  <a :href="route('tenant.manager.estimates.pdf', est.id)" target="_blank" class="btn-ghost btn-sm"
                    ><i class="fa-solid fa-file-pdf"></i
                  ></a>
                  <Link
                    v-if="['draft', 'sent'].includes(est.status)"
                    :href="route('tenant.manager.estimates.edit', est.id)"
                    class="btn-ghost btn-sm"
                    ><i class="fa-solid fa-pen"></i
                  ></Link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <Pagination :links="estimates.links" />
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
  estimates: Object,
  filters: { type: Object, default: () => ({}) },
})

const filters = reactive({ search: props.filters.search ?? '', status: props.filters.status ?? '' })

const search = () => {
  router.get(route('tenant.manager.estimates.index'), filters, { preserveState: true, replace: true })
}

const formatMoney = (v) => new Intl.NumberFormat(intlLocale(), { style: 'currency', currency: 'PLN' }).format(v ?? 0)
const formatDate = (d) => (d ? new Date(d).toLocaleDateString(intlLocale()) : '—')
</script>
