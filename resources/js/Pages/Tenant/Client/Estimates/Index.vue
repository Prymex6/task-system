<template>
  <ClientLayout :title="$t('common.estimates')">
    <div class="space-y-5">
      <h1 class="text-xl font-bold text-gray-900">{{ $t('common.estimates') }}</h1>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="th">{{ $t('common.number') }}</th>
              <th class="th">{{ $t('common.title') }}</th>
              <th class="th">{{ $t('common.value') }}</th>
              <th class="th">{{ $t('common.valid_until') }}</th>
              <th class="th">{{ $t('common.status') }}</th>
              <th class="th"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="!estimates.data?.length">
              <td colspan="6" class="td text-center text-gray-400 py-8">{{ $t('portal.no_estimates_2') }}</td>
            </tr>
            <tr v-for="est in estimates.data" :key="est.id" class="hover:bg-gray-50">
              <td class="td font-medium text-indigo-600">{{ est.number }}</td>
              <td class="td text-gray-700">{{ est.title }}</td>
              <td class="td font-semibold">{{ formatMoney(est.total) }}</td>
              <td class="td text-xs text-gray-500">{{ formatDate(est.valid_until) }}</td>
              <td class="td">
                <span class="badge text-xs" :class="estStatusClass(est.status)">{{ estStatusLabel(est.status) }}</span>
              </td>
              <td class="td">
                <div v-if="est.status === 'sent'" class="flex gap-1">
                  <button @click="accept(est.id)" class="btn-success btn-sm text-xs">{{ $t('portal.accept') }}</button>
                  <button @click="reject(est.id)" class="btn-danger btn-sm text-xs">{{ $t('common.reject') }}</button>
                </div>
                <a
                  v-else
                  :href="route('tenant.portal.estimates.pdf', est.id)"
                  target="_blank"
                  class="btn-ghost btn-sm text-xs"
                >
                  <i class="fa-solid fa-file-pdf"></i>
                </a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <Pagination :links="estimates.links" />
    </div>
  </ClientLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { Link, useForm } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({ estimates: Object })

const accept = (id) => {
  if (!confirm(t('portal.accept_this_estimate'))) return
  useForm({}).post(route('tenant.portal.estimates.accept', id))
}

const reject = (id) => {
  if (!confirm(t('portal.decline_this_estimate'))) return
  useForm({}).post(route('tenant.portal.estimates.reject', id))
}

const formatMoney = (v) => new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(v ?? 0)
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')
const estStatusLabel = (s) =>
  ({
    draft: 'Szkic',
    sent: 'Oczekuje',
    accepted: 'Zaakceptowana',
    rejected: 'Odrzucona',
    expired: t('common.expired'),
  })[s] ?? s
const estStatusClass = (s) =>
  ({
    sent: 'badge-yellow',
    accepted: 'badge-green',
    rejected: 'badge-red',
    expired: 'badge-gray',
    draft: 'badge-gray',
  })[s] ?? 'badge-gray'
</script>
