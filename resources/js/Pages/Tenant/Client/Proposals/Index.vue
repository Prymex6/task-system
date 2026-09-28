<template>
  <ClientLayout :title="$t('portal.my_estimates')">
    <div class="space-y-5">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('portal.estimates') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $t('portal.estimates_sent_to_your_company') }}</p>
      </div>

      <div class="grid grid-cols-1 gap-3">
        <div
          v-for="proposal in proposals.data"
          :key="proposal.id"
          class="bg-white rounded-xl border border-gray-200 p-5 hover:border-indigo-300 hover:shadow-sm transition-all"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <h3 class="text-sm font-semibold text-gray-900">{{ proposal.title }}</h3>
                <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="statusClass(proposal.status)">
                  {{ statusLabel(proposal.status) }}
                </span>
              </div>
              <p class="text-xs text-gray-500">
                {{ $t('portal.number') }} <span class="font-medium text-gray-700">{{ proposal.number }}</span>
                <span v-if="proposal.valid_until" class="ml-3">
                  {{ $t('portal.valid_until') }}
                  <span class="font-medium" :class="isExpired(proposal) ? 'text-red-500' : 'text-gray-700'">
                    {{ formatDate(proposal.valid_until) }}
                  </span>
                </span>
              </p>
            </div>
            <div class="text-right flex-shrink-0">
              <p class="text-lg font-bold text-gray-900">{{ formatMoney(proposal.total_gross) }}</p>
              <Link
                :href="route('tenant.client.proposals.show', proposal.id)"
                class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
              >
                {{ $t('common.details') }} <i class="fa-solid fa-arrow-right ml-0.5 text-xs"></i>
              </Link>
            </div>
          </div>

          <!-- Action buttons for pending proposals -->
          <div v-if="proposal.status === 'sent'" class="mt-4 pt-4 border-t border-gray-100 flex items-center gap-2">
            <Link :href="route('tenant.client.proposals.show', proposal.id)" class="btn-primary text-sm">
              <i class="fa-solid fa-check mr-1"></i> {{ $t('portal.review_estimate') }}
            </Link>
          </div>
        </div>

        <div v-if="!proposals.data?.length" class="py-16 text-center bg-white rounded-xl border border-gray-200">
          <i class="fa-solid fa-file-contract text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('portal.no_estimates') }}</p>
        </div>
      </div>

      <Pagination :links="proposals.links" />
    </div>
  </ClientLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { Link } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({ proposals: Object })

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

const isExpired = (p) => p.valid_until && new Date(p.valid_until) < new Date() && p.status !== 'accepted'
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')
const formatMoney = (v) =>
  v != null ? new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(v) : '—'
</script>
