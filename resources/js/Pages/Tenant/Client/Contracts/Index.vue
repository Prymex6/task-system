<template>
  <ClientLayout :title="$t('common.contracts')">
    <div class="space-y-5">
      <h1 class="text-xl font-bold text-gray-900">{{ $t('common.contracts') }}</h1>

      <div v-if="!contracts.data?.length" class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <div class="text-4xl mb-3">📄</div>
        <div class="font-semibold text-gray-600">{{ $t('portal.no_contracts') }}</div>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="c in contracts.data"
          :key="c.id"
          class="bg-white rounded-xl border border-gray-200 p-5 flex items-center justify-between hover:shadow-sm transition-shadow"
        >
          <div>
            <div class="flex items-center gap-2 mb-1">
              <span class="font-semibold text-gray-900">{{ c.title }}</span>
              <span class="badge text-xs" :class="contractStatusClass(c.status)">{{
                contractStatusLabel(c.status)
              }}</span>
            </div>
            <div class="text-xs text-gray-400">
              {{ c.number }} · {{ formatDate(c.start_date) }}
              <span v-if="c.end_date"> → {{ formatDate(c.end_date) }}</span>
            </div>
            <div v-if="c.value" class="text-sm font-medium text-gray-700 mt-1">{{ formatMoney(c.value) }}</div>
          </div>
          <div class="flex gap-2">
            <button v-if="c.status === 'active' && !c.signed_at" @click="sign(c.id)" class="btn-primary btn-sm">
              <i class="fa-solid fa-signature"></i> {{ $t('portal.sign') }}
            </button>
            <a
              v-if="c.signed_at"
              :href="route('tenant.portal.contracts.pdf', c.id)"
              target="_blank"
              class="btn-secondary btn-sm"
            >
              <i class="fa-solid fa-file-pdf"></i> PDF
            </a>
          </div>
        </div>
      </div>

      <Pagination :links="contracts.links" />
    </div>
  </ClientLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { useForm } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { intlLocale } from '@/format'

const props = defineProps({ contracts: Object })

const sign = (id) => {
  if (!confirm(t('portal.sign_this_contract_electronically_you_confirm'))) return
  useForm({}).post(route('tenant.portal.contracts.sign', id))
}

const formatMoney = (v) => new Intl.NumberFormat(intlLocale(), { style: 'currency', currency: 'PLN' }).format(v ?? 0)
const formatDate = (d) => (d ? new Date(d).toLocaleDateString(intlLocale()) : '—')
const contractStatusLabel = (s) =>
  ({ draft: 'Szkic', active: 'Aktywna', signed: 'Podpisana', expired: t('common.expired'), cancelled: 'Anulowana' })[
    s
  ] ?? s
const contractStatusClass = (s) =>
  ({ active: 'badge-blue', signed: 'badge-green', expired: 'badge-gray', cancelled: 'badge-red', draft: 'badge-gray' })[
    s
  ] ?? 'badge-gray'
</script>
