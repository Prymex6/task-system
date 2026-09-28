<template>
  <ManagerLayout :title="$t('common.invoices')">
    <div class="space-y-5">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('common.invoices') }}</h1>
        <Link
          :href="route('tenant.manager.invoices.create')"
          class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
        >
          <i class="fa-solid fa-plus"></i> {{ $t('crm.new_invoice') }}
        </Link>
      </div>

      <!-- Summary cards -->
      <div class="grid grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500">{{ $t('finance.total') }}</p>
          <p class="text-xl font-bold text-gray-900 mt-1">{{ fmt(summary.total) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500">{{ $t('common.paid') }}</p>
          <p class="text-xl font-bold text-green-600 mt-1">{{ fmt(summary.paid) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500">{{ $t('common.pending') }}</p>
          <p class="text-xl font-bold text-blue-600 mt-1">{{ fmt(summary.pending) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500">{{ $t('common.overdue') }}</p>
          <p class="text-xl font-bold text-red-600 mt-1">{{ fmt(summary.overdue) }}</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
        <input
          v-model="filters.search"
          @input="apply"
          :placeholder="$t('finance.search_invoices')"
          class="input-sm flex-1 min-w-[200px]"
        />
        <select v-model="filters.status" @change="apply" class="input-sm">
          <option value="">{{ $t('common.all_statuses') }}</option>
          <option value="draft">{{ $t('common.draft') }}</option>
          <option value="sent">{{ $t('common.sent') }}</option>
          <option value="paid">{{ $t('finance.paid') }}</option>
          <option value="partially_paid">{{ $t('finance.part_paid') }}</option>
          <option value="overdue">{{ $t('finance.overdue') }}</option>
          <option value="cancelled">{{ $t('common.cancelled_2') }}</option>
        </select>
        <select v-model="filters.client_id" @change="apply" class="input-sm">
          <option value="">{{ $t('finance.all_clients') }}</option>
          <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.company_name || c.name }}</option>
        </select>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">{{ $t('common.number') }}</th>
              <th class="th">{{ $t('common.client') }}</th>
              <th class="th">{{ $t('common.issue_date') }}</th>
              <th class="th">{{ $t('common.payment_due') }}</th>
              <th class="th">{{ $t('common.amount') }}</th>
              <th class="th">{{ $t('common.outstanding') }}</th>
              <th class="th">{{ $t('common.status') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="inv in invoices.data"
              :key="inv.id"
              class="hover:bg-gray-50 cursor-pointer"
              @click="router.visit(route('tenant.manager.invoices.show', inv.id))"
            >
              <td class="td font-medium text-gray-900">{{ inv.number }}</td>
              <td class="td text-sm text-gray-600">{{ inv.client?.company_name || inv.client?.name }}</td>
              <td class="td text-sm text-gray-600">{{ inv.issue_date }}</td>
              <td class="td text-sm" :class="isOverdue(inv) ? 'text-red-500 font-medium' : 'text-gray-600'">
                {{ inv.due_date }}
              </td>
              <td class="td text-sm font-medium text-gray-900">{{ fmt(inv.total) }}</td>
              <td class="td text-sm" :class="inv.balance_due > 0 ? 'text-red-600 font-semibold' : 'text-green-600'">
                {{ fmt(inv.balance_due) }}
              </td>
              <td class="td">
                <StatusBadge :status="inv.status" />
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!invoices.data.length" class="py-16 text-center">
          <i class="fa-solid fa-file-invoice-dollar text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('common.no_invoices') }}</p>
        </div>
      </div>

      <Pagination :links="invoices.links" />
    </div>
  </ManagerLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import StatusBadge from '@/Components/Manager/StatusBadge.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  invoices: Object,
  summary: Object,
  clients: Array,
  filters: Object,
})

const filters = reactive({ ...props.filters })
const apply = () => router.get(route('tenant.manager.invoices.index'), filters, { preserveState: true, replace: true })

const fmt = (v) =>
  Number(v ?? 0).toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' PLN'

const isOverdue = (inv) => {
  if (!inv.due_date || inv.status === 'paid') return false
  return new Date(inv.due_date) < new Date()
}
</script>
