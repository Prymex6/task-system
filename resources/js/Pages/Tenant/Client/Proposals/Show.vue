<template>
  <ClientLayout :title="proposal.title">
    <div class="max-w-3xl space-y-5">
      <!-- Header -->
      <div class="flex items-start justify-between gap-4">
        <div>
          <Link
            :href="route('tenant.client.proposals.index')"
            class="text-sm text-indigo-600 hover:text-indigo-800 mb-2 block"
          >
            <i class="fa-solid fa-arrow-left mr-1"></i> {{ $t('portal.all_estimates') }}
          </Link>
          <h1 class="text-2xl font-bold text-gray-900">{{ proposal.title }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ proposal.number }}</p>
        </div>
        <span class="flex-shrink-0 text-sm px-3 py-1 rounded-full font-medium" :class="statusClass(proposal.status)">
          {{ statusLabel(proposal.status) }}
        </span>
      </div>

      <!-- Info block -->
      <div class="bg-white rounded-xl border border-gray-200 p-5 grid grid-cols-2 gap-4 text-sm">
        <div>
          <p class="text-xs text-gray-500">{{ $t('common.issue_date') }}</p>
          <p class="font-medium text-gray-900">{{ formatDate(proposal.issue_date) }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('common.valid_until') }}</p>
          <p class="font-medium" :class="isExpired ? 'text-red-500' : 'text-gray-900'">
            {{ formatDate(proposal.valid_until) ?? '—' }}
          </p>
        </div>
        <div v-if="proposal.notes" class="col-span-2">
          <p class="text-xs text-gray-500 mb-1">{{ $t('common.notes') }}</p>
          <p class="text-gray-700 whitespace-pre-wrap">{{ proposal.notes }}</p>
        </div>
      </div>

      <!-- Line items -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200">
          <h2 class="text-sm font-semibold text-gray-700">{{ $t('common.items') }}</h2>
        </div>
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="th">{{ $t('common.description') }}</th>
              <th class="th text-right">{{ $t('common.quantity') }}</th>
              <th class="th text-right">{{ $t('common.net_price') }}</th>
              <th class="th text-right">VAT</th>
              <th class="th text-right">{{ $t('portal.gross_total') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="item in proposal.items" :key="item.id" class="hover:bg-gray-50">
              <td class="td text-gray-900">{{ item.name }}</td>
              <td class="td text-right text-gray-600">{{ item.quantity }} {{ item.unit }}</td>
              <td class="td text-right text-gray-600">{{ formatMoney(item.unit_price) }}</td>
              <td class="td text-right text-gray-500">{{ item.tax_rate }}%</td>
              <td class="td text-right font-medium text-gray-900">{{ formatMoney(item.total_gross) }}</td>
            </tr>
          </tbody>
        </table>

        <!-- Totals -->
        <div class="p-4 border-t border-gray-200 flex justify-end">
          <div class="w-64 space-y-1 text-sm">
            <div class="flex justify-between text-gray-600">
              <span>{{ $t('common.net') }}</span>
              <span>{{ formatMoney(proposal.total_net) }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
              <span>VAT:</span>
              <span>{{ formatMoney(proposal.total_tax) }}</span>
            </div>
            <div class="flex justify-between text-base font-bold text-gray-900 pt-2 border-t border-gray-200">
              <span>{{ $t('common.gross_total') }}</span>
              <span>{{ formatMoney(proposal.total_gross) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Accept / Reject actions -->
      <div
        v-if="proposal.status === 'sent' || proposal.status === 'viewed'"
        class="bg-white rounded-xl border border-gray-200 p-5"
      >
        <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('portal.your_decision') }}</h2>

        <div v-if="!showRejectForm" class="flex items-center gap-3">
          <button @click="accept" :disabled="processing" class="btn-primary flex-1">
            <i class="fa-solid fa-check mr-2"></i> {{ $t('portal.i_accept_this_estimate') }}
          </button>
          <button @click="showRejectForm = true" class="btn-ghost flex-1 border border-gray-200">
            <i class="fa-solid fa-xmark mr-2"></i> {{ $t('portal.i_decline_this_estimate') }}
          </button>
        </div>

        <div v-else class="space-y-3">
          <div>
            <label class="label">{{ $t('portal.reason_optional') }}</label>
            <textarea
              v-model="rejectReason"
              rows="3"
              class="input w-full"
              :placeholder="$t('portal.tell_us_why')"
            ></textarea>
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="reject"
              :disabled="processing"
              class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium"
            >
              {{ $t('portal.confirm_decline') }}
            </button>
            <button @click="showRejectForm = false" class="btn-ghost text-sm">{{ $t('common.cancel') }}</button>
          </div>
        </div>
      </div>

      <!-- Accepted message -->
      <div
        v-if="proposal.status === 'accepted'"
        class="bg-green-50 border border-green-200 rounded-xl p-5 flex items-center gap-3"
      >
        <i class="fa-solid fa-circle-check text-2xl text-green-500"></i>
        <div>
          <p class="text-sm font-semibold text-green-900">{{ $t('portal.estimate_accepted') }}</p>
          <p class="text-xs text-green-700">{{ $t('portal.thank_you_we_will_be_in') }}</p>
        </div>
      </div>

      <!-- PDF download -->
      <div class="flex justify-end">
        <a :href="route('tenant.client.proposals.pdf', proposal.id)" target="_blank" class="btn-ghost text-sm">
          <i class="fa-solid fa-file-pdf text-red-500 mr-1.5"></i> {{ $t('portal.download_pdf') }}
        </a>
      </div>
    </div>
  </ClientLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import { intlLocale } from '@/format'

const props = defineProps({ proposal: Object })

const showRejectForm = ref(false)
const rejectReason = ref('')
const processing = ref(false)

const isExpired = computed(() => props.proposal.valid_until && new Date(props.proposal.valid_until) < new Date())

const accept = () => {
  processing.value = true
  router.post(
    route('tenant.client.proposals.accept', props.proposal.id),
    {},
    {
      onFinish: () => {
        processing.value = false
      },
    },
  )
}

const reject = () => {
  processing.value = true
  router.post(
    route('tenant.client.proposals.reject', props.proposal.id),
    { reason: rejectReason.value },
    {
      onFinish: () => {
        processing.value = false
        showRejectForm.value = false
      },
    },
  )
}

const statusClass = (s) =>
  ({
    draft: 'bg-gray-100 text-gray-600',
    sent: 'bg-blue-100 text-blue-700',
    viewed: 'bg-purple-100 text-purple-700',
    accepted: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-700',
    expired: 'bg-orange-100 text-orange-700',
  })[s] ?? 'bg-gray-100 text-gray-600'

const statusLabel = (s) =>
  ({
    draft: 'Szkic',
    sent: t('common.sent'),
    viewed: t('common.viewed'),
    accepted: 'Zaakceptowana',
    rejected: 'Odrzucona',
    expired: t('common.expired'),
  })[s] ?? s

const formatDate = (d) => (d ? new Date(d).toLocaleDateString(intlLocale()) : null)
const formatMoney = (v) =>
  v != null ? new Intl.NumberFormat(intlLocale(), { style: 'currency', currency: 'PLN' }).format(v) : '—'
</script>
