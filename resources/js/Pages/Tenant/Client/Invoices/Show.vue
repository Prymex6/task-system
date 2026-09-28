<template>
  <ClientLayout :title="invoice.number">
    <div class="space-y-6 max-w-3xl">
      <div class="flex items-center gap-3">
        <Link :href="route('tenant.portal.invoices')" class="text-sm text-indigo-600 hover:text-indigo-700">{{
          $t('portal.invoices')
        }}</Link>
        <span class="text-gray-300">/</span>
        <h1 class="text-xl font-bold text-gray-900">{{ invoice.number }}</h1>
        <span class="badge text-xs" :class="invStatusClass(invoice.status)">{{ invStatusLabel(invoice.status) }}</span>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex justify-between mb-6">
          <div>
            <div class="text-sm text-gray-500">{{ $t('common.issue_date') }}</div>
            <div class="font-semibold">{{ formatDate(invoice.issue_date) }}</div>
          </div>
          <div class="text-right">
            <div class="text-sm text-gray-500">{{ $t('common.payment_due') }}</div>
            <div class="font-semibold" :class="isOverdue ? 'text-red-600' : ''">{{ formatDate(invoice.due_date) }}</div>
          </div>
        </div>

        <table class="w-full text-sm mb-6">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-2 text-gray-500 font-medium">{{ $t('common.description') }}</th>
              <th class="text-right py-2 text-gray-500 font-medium">{{ $t('common.quantity') }}</th>
              <th class="text-right py-2 text-gray-500 font-medium">{{ $t('common.price') }}</th>
              <th class="text-right py-2 text-gray-500 font-medium">{{ $t('common.value') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in invoice.items" :key="item.id" class="border-b border-gray-100">
              <td class="py-2.5 text-gray-800">{{ item.description }}</td>
              <td class="py-2.5 text-right text-gray-600">{{ item.quantity }}</td>
              <td class="py-2.5 text-right text-gray-600">{{ formatMoney(item.unit_price) }}</td>
              <td class="py-2.5 text-right font-medium">{{ formatMoney(item.total) }}</td>
            </tr>
          </tbody>
        </table>

        <div class="flex justify-end">
          <div class="w-52 space-y-1.5 text-sm">
            <div class="flex justify-between text-gray-600">
              <span>{{ $t('common.net') }}</span
              ><span>{{ formatMoney(invoice.subtotal) }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
              <span>VAT:</span><span>{{ formatMoney(invoice.tax) }}</span>
            </div>
            <div class="flex justify-between font-bold text-base border-t border-gray-200 pt-1.5">
              <span>{{ $t('common.total') }}</span
              ><span>{{ formatMoney(invoice.total) }}</span>
            </div>
            <div
              v-if="invoice.balance_due > 0"
              class="flex justify-between font-semibold text-red-600 border-t border-gray-200 pt-1.5"
            >
              <span>{{ $t('portal.outstanding') }}</span
              ><span>{{ formatMoney(invoice.balance_due) }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="flex gap-3">
        <a :href="route('tenant.portal.invoices.pdf', invoice.id)" target="_blank" class="btn-primary">
          <i class="fa-solid fa-file-pdf"></i> {{ $t('portal.download_pdf') }}
        </a>
      </div>
    </div>
  </ClientLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'

const props = defineProps({ invoice: Object })

const isOverdue = computed(
  () => props.invoice.balance_due > 0 && props.invoice.due_date && new Date(props.invoice.due_date) < new Date(),
)

const formatMoney = (v) => new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(v ?? 0)
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')
const invStatusLabel = (s) =>
  ({ draft: 'Szkic', sent: t('common.sent'), paid: t('finance.paid'), overdue: 'Przeterminowana' })[s] ?? s
const invStatusClass = (s) =>
  ({ paid: 'badge-green', overdue: 'badge-red', sent: 'badge-yellow', draft: 'badge-gray' })[s] ?? 'badge-gray'
</script>
