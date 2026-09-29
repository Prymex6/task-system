<template>
  <ClientLayout :title="$t('common.invoices')">
    <div class="space-y-5">
      <h1 class="text-xl font-bold text-gray-900">{{ $t('common.invoices') }}</h1>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="th">{{ $t('common.number') }}</th>
              <th class="th">{{ $t('common.date') }}</th>
              <th class="th">{{ $t('common.due') }}</th>
              <th class="th">{{ $t('common.value') }}</th>
              <th class="th">{{ $t('common.outstanding') }}</th>
              <th class="th">{{ $t('common.status') }}</th>
              <th class="th"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="!invoices.data?.length">
              <td colspan="7" class="td text-center text-gray-400 py-8">{{ $t('common.no_invoices') }}</td>
            </tr>
            <tr v-for="inv in invoices.data" :key="inv.id" class="hover:bg-gray-50">
              <td class="td font-medium">
                <Link
                  :href="route('tenant.portal.invoices.show', inv.id)"
                  class="text-indigo-600 hover:text-indigo-700"
                >
                  {{ inv.number }}
                </Link>
              </td>
              <td class="td text-xs text-gray-500">{{ formatDate(inv.issue_date) }}</td>
              <td class="td text-xs" :class="isOverdue(inv) ? 'text-red-500 font-medium' : 'text-gray-500'">
                {{ formatDate(inv.due_date) }}
              </td>
              <td class="td font-semibold">{{ formatMoney(inv.total) }}</td>
              <td class="td font-semibold" :class="inv.balance_due > 0 ? 'text-red-600' : 'text-emerald-600'">
                {{ formatMoney(inv.balance_due) }}
              </td>
              <td class="td">
                <span class="badge text-xs" :class="invStatusClass(inv.status)">{{ invStatusLabel(inv.status) }}</span>
              </td>
              <td class="td">
                <a :href="route('tenant.portal.invoices.pdf', inv.id)" target="_blank" class="btn-ghost btn-sm text-xs">
                  <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <Pagination :links="invoices.links" />
    </div>
  </ClientLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { Link } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { intlLocale } from '@/format'

const props = defineProps({ invoices: Object })

const formatMoney = (v) => new Intl.NumberFormat(intlLocale(), { style: 'currency', currency: 'PLN' }).format(v ?? 0)
const formatDate = (d) => (d ? new Date(d).toLocaleDateString(intlLocale()) : '—')
const isOverdue = (inv) => inv.balance_due > 0 && inv.due_date && new Date(inv.due_date) < new Date()
const invStatusLabel = (s) =>
  ({ draft: 'Szkic', sent: t('common.sent'), paid: t('finance.paid'), overdue: 'Przeterminowana' })[s] ?? s
const invStatusClass = (s) =>
  ({ paid: 'badge-green', overdue: 'badge-red', sent: 'badge-yellow', draft: 'badge-gray' })[s] ?? 'badge-gray'
</script>
