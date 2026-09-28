<template>
  <ManagerLayout :title="estimate.number">
    <div class="space-y-6">
      <div class="page-header">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.estimates.index')" class="btn-ghost btn-sm"
            ><i class="fa-solid fa-arrow-left"></i
          ></Link>
          <div>
            <h1 class="page-title">{{ $t('finance.estimate') }} {{ estimate.number }}</h1>
            <p class="page-subtitle">{{ estimate.client?.company_name ?? estimate.client?.name }}</p>
          </div>
        </div>
        <div class="flex gap-2">
          <a :href="route('tenant.manager.estimates.pdf', estimate.id)" target="_blank" class="btn-secondary">
            <i class="fa-solid fa-file-pdf"></i> PDF
          </a>
          <button v-if="estimate.status === 'accepted'" @click="convertToInvoice" class="btn-success">
            <i class="fa-solid fa-file-invoice"></i> {{ $t('finance.convert_to_invoice') }}
          </button>
          <Link
            v-if="['draft', 'sent'].includes(estimate.status)"
            :href="route('tenant.manager.estimates.edit', estimate.id)"
            class="btn-secondary"
          >
            <i class="fa-solid fa-pen"></i> {{ $t('common.edit') }}
          </Link>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 card p-6">
          <div class="flex justify-between mb-4">
            <div>
              <h2 class="text-lg font-semibold">{{ estimate.title }}</h2>
            </div>
            <div class="text-right">
              <StatusBadge :status="estimate.status" />
              <div class="text-xs text-gray-400 mt-1">
                {{ $t('finance.issued') }} {{ formatDate(estimate.issue_date) }}
              </div>
              <div class="text-xs text-gray-400">
                {{ $t('portal.valid_until') }} {{ formatDate(estimate.valid_until) }}
              </div>
            </div>
          </div>

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
              <tr v-for="item in estimate.items" :key="item.id" class="border-b border-gray-100">
                <td class="py-2.5 text-gray-800">{{ item.description }}</td>
                <td class="py-2.5 text-right text-gray-600">{{ item.quantity }}</td>
                <td class="py-2.5 text-right text-gray-600">{{ formatMoney(item.unit_price) }}</td>
                <td class="py-2.5 text-right text-gray-500">{{ item.tax_rate ?? 0 }}%</td>
                <td class="py-2.5 text-right font-medium text-gray-800">{{ formatMoney(item.total) }}</td>
              </tr>
            </tbody>
          </table>

          <div class="flex justify-end">
            <div class="w-52 space-y-1.5 text-sm">
              <div class="flex justify-between text-gray-600">
                <span>{{ $t('common.net') }}</span
                ><span>{{ formatMoney(estimate.subtotal) }}</span>
              </div>
              <div class="flex justify-between text-gray-600">
                <span>VAT:</span><span>{{ formatMoney(estimate.tax) }}</span>
              </div>
              <div class="flex justify-between font-bold text-base border-t border-gray-200 pt-1.5">
                <span>{{ $t('common.total') }}</span
                ><span>{{ formatMoney(estimate.total) }}</span>
              </div>
            </div>
          </div>

          <div v-if="estimate.notes" class="mt-6 pt-4 border-t border-gray-100 text-sm text-gray-500">
            {{ estimate.notes }}
          </div>
        </div>

        <div class="space-y-4">
          <div class="card p-5">
            <h3 class="section-title">{{ $t('common.client') }}</h3>
            <Link
              :href="route('tenant.manager.clients.show', estimate.client?.id)"
              class="text-sm font-medium text-indigo-600 hover:text-indigo-700"
            >
              {{ estimate.client?.company_name ?? estimate.client?.name }}
            </Link>
            <p class="text-xs text-gray-400 mt-1">{{ estimate.client?.email }}</p>
          </div>
          <div v-if="estimate.project" class="card p-5">
            <h3 class="section-title">{{ $t('common.project') }}</h3>
            <Link :href="route('tenant.manager.projects.show', estimate.project.id)" class="text-sm text-indigo-600">{{
              estimate.project.name
            }}</Link>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { Link, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import StatusBadge from '@/Components/Manager/StatusBadge.vue'

const props = defineProps({ estimate: Object })

const convertToInvoice = () => {
  if (!confirm(t('finance.convert_this_estimate_into_an_invoice'))) return
  useForm({}).post(route('tenant.manager.estimates.convert', props.estimate.id))
}

const formatMoney = (v) => new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(v ?? 0)
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')
</script>
