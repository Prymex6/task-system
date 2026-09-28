<template>
  <ManagerLayout :title="invoice.number">
    <div class="space-y-6">
      <div class="page-header">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.invoices.index')" class="btn-ghost btn-sm"
            ><i class="fa-solid fa-arrow-left"></i
          ></Link>
          <div>
            <h1 class="page-title">{{ $t('common.invoice') }} {{ invoice.number }}</h1>
            <p class="page-subtitle">{{ invoice.client?.company_name ?? invoice.client?.name }}</p>
          </div>
        </div>
        <div class="flex gap-2">
          <a :href="route('tenant.manager.invoices.pdf', invoice.id)" target="_blank" class="btn-secondary">
            <i class="fa-solid fa-file-pdf"></i> PDF
          </a>
          <button v-if="invoice.status === 'draft'" @click="sendInvoice" class="btn-secondary">
            <i class="fa-solid fa-paper-plane"></i> {{ $t('common.send') }}
          </button>
          <Link
            v-if="['draft', 'sent'].includes(invoice.status)"
            :href="route('tenant.manager.invoices.edit', invoice.id)"
            class="btn-secondary"
          >
            <i class="fa-solid fa-pen"></i> {{ $t('common.edit') }}
          </Link>
          <button v-if="invoice.balance_due > 0" @click="showPayment = true" class="btn-primary">
            <i class="fa-solid fa-circle-dollar-to-slot"></i> {{ $t('finance.record_payment') }}
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main invoice -->
        <div class="lg:col-span-2 space-y-4">
          <!-- Header info -->
          <div class="card p-6">
            <div class="flex justify-between items-start mb-6">
              <div>
                <div class="text-sm text-gray-500 mb-1">{{ $t('finance.issued_by') }}</div>
                <div class="font-semibold text-gray-900">{{ $page.props.workspace?.name }}</div>
              </div>
              <div class="text-right">
                <StatusBadge :status="invoice.status" />
                <div class="text-xs text-gray-400 mt-1">
                  {{ $t('finance.issued') }} {{ formatDate(invoice.issue_date) }}
                </div>
                <div class="text-xs text-gray-400">{{ $t('finance.due') }} {{ formatDate(invoice.due_date) }}</div>
              </div>
            </div>

            <!-- Items table -->
            <table class="w-full text-sm mb-4">
              <thead>
                <tr class="border-b border-gray-200">
                  <th class="text-left py-2 text-gray-500 font-medium">{{ $t('common.description') }}</th>
                  <th class="text-right py-2 text-gray-500 font-medium">{{ $t('common.quantity') }}</th>
                  <th class="text-right py-2 text-gray-500 font-medium">{{ $t('common.price') }}</th>
                  <th class="text-right py-2 text-gray-500 font-medium">VAT%</th>
                  <th class="text-right py-2 text-gray-500 font-medium">{{ $t('common.value') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in invoice.items" :key="item.id" class="border-b border-gray-100">
                  <td class="py-2.5 text-gray-800">{{ item.description }}</td>
                  <td class="py-2.5 text-right text-gray-600">{{ item.quantity }}</td>
                  <td class="py-2.5 text-right text-gray-600">{{ formatMoney(item.unit_price) }}</td>
                  <td class="py-2.5 text-right text-gray-500">{{ item.tax_rate ?? 0 }}%</td>
                  <td class="py-2.5 text-right font-medium text-gray-800">{{ formatMoney(item.total) }}</td>
                </tr>
              </tbody>
            </table>

            <!-- Totals -->
            <div class="flex justify-end">
              <div class="w-56 space-y-1.5 text-sm">
                <div class="flex justify-between text-gray-600">
                  <span>{{ $t('common.net') }}</span>
                  <span>{{ formatMoney(invoice.subtotal) }}</span>
                </div>
                <div class="flex justify-between text-gray-600">
                  <span>VAT:</span>
                  <span>{{ formatMoney(invoice.tax) }}</span>
                </div>
                <div class="flex justify-between font-bold text-base text-gray-900 border-t border-gray-200 pt-1.5">
                  <span>{{ $t('common.total') }}</span>
                  <span>{{ formatMoney(invoice.total) }}</span>
                </div>
                <div v-if="invoice.amount_paid > 0" class="flex justify-between text-emerald-600 text-sm">
                  <span>{{ $t('finance.paid_3') }}</span>
                  <span>-{{ formatMoney(invoice.amount_paid) }}</span>
                </div>
                <div
                  v-if="invoice.balance_due > 0"
                  class="flex justify-between font-semibold text-red-600 border-t border-gray-200 pt-1.5"
                >
                  <span>{{ $t('portal.outstanding') }}</span>
                  <span>{{ formatMoney(invoice.balance_due) }}</span>
                </div>
              </div>
            </div>

            <div v-if="invoice.notes" class="mt-6 pt-4 border-t border-gray-100 text-sm text-gray-500">
              {{ invoice.notes }}
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
          <div class="card p-5 space-y-3">
            <h3 class="section-title">{{ $t('common.client') }}</h3>
            <Link
              :href="route('tenant.manager.clients.show', invoice.client?.id)"
              class="text-sm font-medium text-indigo-600 hover:text-indigo-700"
            >
              {{ invoice.client?.company_name ?? invoice.client?.name }}
            </Link>
            <p class="text-xs text-gray-400">{{ invoice.client?.email }}</p>
          </div>

          <div v-if="invoice.payments?.length" class="card p-5">
            <h3 class="section-title">{{ $t('finance.payment_history') }}</h3>
            <div class="space-y-2">
              <div v-for="pmt in invoice.payments" :key="pmt.id" class="flex justify-between text-sm">
                <span class="text-gray-600">{{ formatDate(pmt.paid_at) }}</span>
                <span class="font-medium text-emerald-600">{{ formatMoney(pmt.amount) }}</span>
              </div>
            </div>
          </div>

          <div v-if="invoice.project" class="card p-5">
            <h3 class="section-title">{{ $t('common.project') }}</h3>
            <Link
              :href="route('tenant.manager.projects.show', invoice.project.id)"
              class="text-sm text-indigo-600 hover:text-indigo-700"
            >
              {{ invoice.project.name }}
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment modal -->
    <div v-if="showPayment" class="modal-backdrop" @click.self="showPayment = false">
      <div class="modal-sm">
        <div class="modal-header">
          <h3 class="modal-title">{{ $t('finance.record_payment') }}</h3>
          <button @click="showPayment = false" class="btn-ghost btn-sm"><i class="fa-solid fa-times"></i></button>
        </div>
        <form @submit.prevent="recordPayment">
          <div class="modal-body space-y-4">
            <div>
              <label class="label">{{ $t('common.amount') }}</label>
              <input
                v-model="paymentForm.amount"
                type="number"
                step="0.01"
                class="input"
                :max="invoice.balance_due"
                required
              />
            </div>
            <div>
              <label class="label">{{ $t('finance.payment_date') }}</label>
              <input v-model="paymentForm.paid_at" type="date" class="input" required />
            </div>
            <div>
              <label class="label">{{ $t('finance.method') }}</label>
              <select v-model="paymentForm.method" class="select">
                <option value="transfer">{{ $t('finance.bank_transfer') }}</option>
                <option value="cash">{{ $t('finance.cash') }}</option>
                <option value="card">{{ $t('finance.card') }}</option>
              </select>
            </div>
            <div>
              <label class="label">{{ $t('common.note') }}</label>
              <input v-model="paymentForm.note" type="text" class="input" :placeholder="$t('finance.optional_note')" />
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" @click="showPayment = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="paymentForm.processing" class="btn-primary">
              {{ $t('finance.save_payment') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import StatusBadge from '@/Components/Manager/StatusBadge.vue'

const props = defineProps({ invoice: Object })

const showPayment = ref(false)

const paymentForm = useForm({
  amount: props.invoice.balance_due,
  paid_at: new Date().toISOString().slice(0, 10),
  method: 'transfer',
  note: '',
})

const recordPayment = () => {
  paymentForm.post(route('tenant.manager.invoices.payment', props.invoice.id), {
    onSuccess: () => {
      showPayment.value = false
    },
  })
}

const sendInvoice = () => {
  if (!confirm(t('finance.send_this_invoice_to_the_client'))) return
  useForm({}).post(route('tenant.manager.invoices.send', props.invoice.id))
}

const formatMoney = (v) => new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(v ?? 0)
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')
</script>
