<template>
  <ManagerLayout :title="$t('nav.proposals')">
    <div class="space-y-5">
      <div class="page-header">
        <div>
          <h1 class="page-title">{{ $t('manager.proposals') }}</h1>
          <p class="page-subtitle">{{ proposals.total }} {{ $t('common.in_total') }}</p>
        </div>
        <Link :href="route('tenant.manager.proposals.create')" class="btn-primary">
          <i class="fa-solid fa-plus"></i> {{ $t('manager.new_proposal') }}
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
              <th class="th">{{ $t('common.valid_until') }}</th>
              <th class="th"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="!proposals.data.length">
              <td colspan="7" class="td text-center text-gray-400 py-8">{{ $t('manager.no_proposals') }}</td>
            </tr>
            <tr v-for="p in proposals.data" :key="p.id" class="tr-hover">
              <td class="td font-medium text-indigo-600">{{ p.number }}</td>
              <td class="td text-gray-800">{{ p.title }}</td>
              <td class="td text-gray-600">{{ p.client?.company_name ?? p.client?.name }}</td>
              <td class="td"><StatusBadge :status="p.status" /></td>
              <td class="td font-medium">{{ p.value ? formatMoney(p.value) : '—' }}</td>
              <td class="td text-xs text-gray-400">{{ p.valid_until ? formatDate(p.valid_until) : '—' }}</td>
              <td class="td">
                <div class="flex gap-1">
                  <Link :href="route('tenant.manager.proposals.show', p.id)" class="btn-ghost btn-sm"
                    ><i class="fa-solid fa-eye"></i
                  ></Link>
                  <Link :href="route('tenant.manager.proposals.edit', p.id)" class="btn-ghost btn-sm"
                    ><i class="fa-solid fa-pen"></i
                  ></Link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <Pagination :links="proposals.links" />
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
  proposals: Object,
  filters: { type: Object, default: () => ({}) },
})

const filters = reactive({ search: props.filters.search ?? '', status: props.filters.status ?? '' })
const search = () =>
  router.get(route('tenant.manager.proposals.index'), filters, { preserveState: true, replace: true })
const formatMoney = (v) => new Intl.NumberFormat(intlLocale(), { style: 'currency', currency: 'PLN' }).format(v)
const formatDate = (d) => new Date(d).toLocaleDateString(intlLocale())
</script>
