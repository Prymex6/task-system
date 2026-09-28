<template>
  <ManagerLayout :title="proposal.number">
    <div class="space-y-6">
      <div class="page-header">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.proposals.index')" class="btn-ghost btn-sm">
            <i class="fa-solid fa-arrow-left"></i>
          </Link>
          <div>
            <h1 class="page-title">{{ proposal.number }}</h1>
            <p class="page-subtitle">{{ proposal.title }}</p>
          </div>
        </div>
        <div class="flex gap-2">
          <a :href="route('tenant.manager.proposals.pdf', proposal.id)" target="_blank" class="btn-secondary">
            <i class="fa-solid fa-file-pdf text-red-500"></i> PDF
          </a>
          <button v-if="proposal.status === 'draft'" @click="sendProposal" class="btn-secondary text-indigo-600">
            <i class="fa-solid fa-paper-plane"></i> {{ $t('common.send') }}
          </button>
          <Link :href="route('tenant.manager.proposals.edit', proposal.id)" class="btn-primary">
            <i class="fa-solid fa-pen"></i> {{ $t('common.edit') }}
          </Link>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Proposal content -->
          <div class="card p-6">
            <h2 class="section-title">{{ $t('manager.proposal_body') }}</h2>
            <div v-if="proposal.content" class="prose prose-sm max-w-none text-gray-700 whitespace-pre-wrap">
              {{ proposal.content }}
            </div>
            <div v-else class="text-gray-400 text-sm italic">{{ $t('manager.no_content') }}</div>
          </div>

          <!-- Internal notes -->
          <div v-if="proposal.notes" class="card p-6">
            <h2 class="section-title">{{ $t('common.internal_notes') }}</h2>
            <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ proposal.notes }}</div>
          </div>

          <!-- Status history -->
          <div class="card p-6">
            <h2 class="section-title">{{ $t('manager.status_history') }}</h2>
            <div v-if="!proposal.activities?.length" class="text-sm text-gray-400">{{ $t('crm.no_history') }}</div>
            <div v-else class="space-y-3">
              <div v-for="act in proposal.activities" :key="act.id" class="flex gap-3 text-sm">
                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center shrink-0">
                  <i class="fa-solid fa-clock-rotate-left text-indigo-600 text-xs"></i>
                </div>
                <div>
                  <div class="text-gray-700">{{ act.description }}</div>
                  <div class="text-xs text-gray-400">{{ formatDate(act.created_at) }} · {{ act.user?.name }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
          <!-- Status card -->
          <div class="card p-5">
            <h3 class="section-title">{{ $t('common.details') }}</h3>
            <dl class="space-y-3 text-sm">
              <div>
                <dt class="text-xs text-gray-400 uppercase tracking-wide mb-1">{{ $t('common.status') }}</dt>
                <dd>
                  <span :class="statusClass(proposal.status)" class="badge">
                    {{ statusLabel(proposal.status) }}
                  </span>
                </dd>
              </div>
              <div>
                <dt class="text-xs text-gray-400 uppercase tracking-wide mb-1">{{ $t('common.client') }}</dt>
                <dd class="font-medium text-gray-800">
                  <Link
                    v-if="proposal.client"
                    :href="route('tenant.manager.clients.show', proposal.client.id)"
                    class="hover:text-indigo-600"
                  >
                    {{ proposal.client.company_name || proposal.client.name }}
                  </Link>
                  <span v-else class="text-gray-400">—</span>
                </dd>
              </div>
              <div v-if="proposal.project">
                <dt class="text-xs text-gray-400 uppercase tracking-wide mb-1">{{ $t('common.project') }}</dt>
                <dd>
                  <Link
                    :href="route('tenant.manager.projects.show', proposal.project.id)"
                    class="text-indigo-600 hover:text-indigo-700 font-medium"
                  >
                    {{ proposal.project.name }}
                  </Link>
                </dd>
              </div>
              <div>
                <dt class="text-xs text-gray-400 uppercase tracking-wide mb-1">{{ $t('common.value') }}</dt>
                <dd class="text-xl font-bold text-gray-900">{{ formatMoney(proposal.value, proposal.currency) }}</dd>
              </div>
              <div v-if="proposal.valid_until">
                <dt class="text-xs text-gray-400 uppercase tracking-wide mb-1">{{ $t('common.valid_until') }}</dt>
                <dd :class="isExpired ? 'text-red-600 font-medium' : 'text-gray-700'">
                  {{ formatDateShort(proposal.valid_until) }}
                  <span v-if="isExpired" class="text-xs">{{ $t('platform.expired') }}</span>
                </dd>
              </div>
              <div>
                <dt class="text-xs text-gray-400 uppercase tracking-wide mb-1">{{ $t('manager.prepared_by') }}</dt>
                <dd class="text-gray-700">{{ proposal.created_by?.name ?? '—' }}</dd>
              </div>
              <div>
                <dt class="text-xs text-gray-400 uppercase tracking-wide mb-1">{{ $t('manager.created') }}</dt>
                <dd class="text-gray-700">{{ formatDate(proposal.created_at) }}</dd>
              </div>
              <div v-if="proposal.sent_at">
                <dt class="text-xs text-gray-400 uppercase tracking-wide mb-1">{{ $t('common.sent') }}</dt>
                <dd class="text-gray-700">{{ formatDate(proposal.sent_at) }}</dd>
              </div>
            </dl>
          </div>

          <!-- Status change -->
          <div class="card p-5">
            <h3 class="section-title">{{ $t('common.change_status') }}</h3>
            <div class="space-y-2">
              <button
                v-for="s in availableStatuses"
                :key="s.value"
                @click="changeStatus(s.value)"
                :disabled="proposal.status === s.value"
                class="w-full btn-secondary text-sm justify-start"
                :class="proposal.status === s.value ? 'opacity-40 cursor-not-allowed' : ''"
              >
                <i :class="s.icon" class="w-4"></i>
                {{ s.label }}
              </button>
            </div>
          </div>

          <!-- Convert to invoice -->
          <div v-if="proposal.status === 'accepted'" class="card p-5 bg-emerald-50 border-emerald-200">
            <div class="flex items-start gap-3">
              <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5"></i>
              <div>
                <div class="font-semibold text-emerald-800 mb-1">{{ $t('manager.proposal_accepted') }}</div>
                <div class="text-xs text-emerald-700 mb-3">{{ $t('manager.you_can_turn_it_into_an') }}</div>
                <button
                  @click="convertToInvoice"
                  class="btn-primary bg-emerald-600 hover:bg-emerald-700 text-sm w-full"
                >
                  <i class="fa-solid fa-file-invoice-dollar"></i> {{ $t('manager.create_invoice') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  proposal: Object,
})

const isExpired = computed(() => {
  if (!props.proposal.valid_until) return false
  return new Date(props.proposal.valid_until) < new Date()
})

const availableStatuses = [
  { value: 'draft', label: t('common.draft'), icon: 'fa-solid fa-file text-gray-500' },
  { value: 'sent', label: t('common.sent'), icon: 'fa-solid fa-paper-plane text-blue-500' },
  { value: 'accepted', label: t('common.accepted'), icon: 'fa-solid fa-circle-check text-emerald-500' },
  { value: 'rejected', label: t('common.rejected'), icon: 'fa-solid fa-circle-xmark text-red-500' },
  { value: 'expired', label: t('common.expired'), icon: 'fa-solid fa-clock text-gray-400' },
]

const sendProposal = () => {
  router.post(route('tenant.manager.proposals.send', props.proposal.id), {}, { preserveScroll: true })
}

const changeStatus = (status) => {
  router.patch(route('tenant.manager.proposals.status', props.proposal.id), { status }, { preserveScroll: true })
}

const convertToInvoice = () => {
  router.post(route('tenant.manager.proposals.convert', props.proposal.id))
}

const statusLabel = (s) =>
  ({
    draft: 'Szkic',
    sent: t('common.sent'),
    accepted: 'Zaakceptowana',
    rejected: 'Odrzucona',
    expired: t('common.expired'),
  })[s] ?? s

const statusClass = (s) =>
  ({
    draft: 'badge-gray',
    sent: 'badge-blue',
    accepted: 'badge-green',
    rejected: 'badge-red',
    expired: 'badge-orange',
  })[s] ?? 'badge-gray'

const formatMoney = (val, currency = 'PLN') => {
  if (!val) return `0,00 ${currency}`
  return new Intl.NumberFormat('pl-PL', { style: 'currency', currency }).format(val)
}

const formatDate = (d) => (d ? new Date(d).toLocaleString('pl-PL') : '—')
const formatDateShort = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')
</script>
