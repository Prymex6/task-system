<template>
  <ManagerLayout :title="$t('common.deals')">
    <div class="space-y-5">
      <div class="page-header">
        <div>
          <h1 class="page-title">{{ $t('common.deals') }}</h1>
          <p class="page-subtitle">{{ deals.total }} {{ $t('crm.deals_in_total') }}</p>
        </div>
        <div class="flex gap-2">
          <Link :href="route('tenant.manager.deals.pipeline')" class="btn-secondary">
            <i class="fa-solid fa-columns"></i> Pipeline
          </Link>
          <Link :href="route('tenant.manager.deals.create')" class="btn-primary">
            <i class="fa-solid fa-plus"></i> {{ $t('crm.new_deal') }}
          </Link>
        </div>
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
        <select v-model="filters.stage_id" class="select input-sm w-40" @change="search">
          <option value="">{{ $t('crm.all_stages') }}</option>
          <option v-for="stage in stages" :key="stage.id" :value="stage.id">{{ stage.name }}</option>
        </select>
      </div>

      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th class="th">Deal</th>
              <th class="th">{{ $t('common.client') }}</th>
              <th class="th">{{ $t('common.stage') }}</th>
              <th class="th">{{ $t('common.value') }}</th>
              <th class="th">{{ $t('crm.probability') }}</th>
              <th class="th">{{ $t('common.due') }}</th>
              <th class="th">{{ $t('common.assigned') }}</th>
              <th class="th"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="!deals.data.length">
              <td colspan="8" class="td text-center text-gray-400 py-8">{{ $t('crm.no_deals') }}</td>
            </tr>
            <tr v-for="deal in deals.data" :key="deal.id" class="tr-hover">
              <td class="td">
                <Link
                  :href="route('tenant.manager.deals.show', deal.id)"
                  class="font-medium text-gray-900 hover:text-indigo-600"
                >
                  {{ deal.title }}
                </Link>
              </td>
              <td class="td text-gray-600 text-sm">{{ deal.client?.company_name ?? deal.client?.name }}</td>
              <td class="td">
                <span v-if="deal.stage" class="badge badge-indigo">{{ deal.stage.name }}</span>
              </td>
              <td class="td font-medium">{{ formatMoney(deal.value) }}</td>
              <td class="td">
                <div class="flex items-center gap-2">
                  <div class="progress-bar h-1.5 w-16">
                    <div class="progress-fill h-1.5" :style="{ width: deal.probability + '%' }"></div>
                  </div>
                  <span class="text-xs text-gray-500">{{ deal.probability }}%</span>
                </div>
              </td>
              <td class="td text-xs text-gray-400">
                {{ deal.expected_close_date ? formatDate(deal.expected_close_date) : '—' }}
              </td>
              <td class="td text-xs text-gray-500">{{ deal.assigned?.name ?? '—' }}</td>
              <td class="td">
                <Link :href="route('tenant.manager.deals.show', deal.id)" class="btn-ghost btn-sm">
                  <i class="fa-solid fa-eye"></i>
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <Pagination :links="deals.links" />
    </div>
  </ManagerLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  deals: Object,
  stages: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
})

const filters = reactive({ search: props.filters.search ?? '', stage_id: props.filters.stage_id ?? '' })

const search = () => {
  router.get(route('tenant.manager.deals.index'), filters, { preserveState: true, replace: true })
}

const formatMoney = (v) => new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(v ?? 0)
const formatDate = (d) => new Date(d).toLocaleDateString('pl-PL')
</script>
