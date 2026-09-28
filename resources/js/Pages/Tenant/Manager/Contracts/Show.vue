<template>
  <ManagerLayout :title="contract.number">
    <div class="space-y-6">
      <div class="page-header">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.contracts.index')" class="btn-ghost btn-sm"
            ><i class="fa-solid fa-arrow-left"></i
          ></Link>
          <div>
            <h1 class="page-title">{{ contract.title }}</h1>
            <p class="page-subtitle">
              {{ contract.number }} · {{ contract.client?.company_name ?? contract.client?.name }}
            </p>
          </div>
        </div>
        <div class="flex gap-2">
          <a :href="route('tenant.manager.contracts.pdf', contract.id)" target="_blank" class="btn-secondary">
            <i class="fa-solid fa-file-pdf"></i> PDF
          </a>
          <Link :href="route('tenant.manager.contracts.edit', contract.id)" class="btn-secondary">
            <i class="fa-solid fa-pen"></i> {{ $t('common.edit') }}
          </Link>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Content -->
        <div class="lg:col-span-2 space-y-4">
          <div class="card p-6">
            <div class="flex justify-between mb-4">
              <StatusBadge :status="contract.status" />
              <div class="text-sm text-gray-500">
                {{ formatDate(contract.start_date) }}
                <span v-if="contract.end_date"> → {{ formatDate(contract.end_date) }}</span>
              </div>
            </div>
            <div
              v-if="contract.signed_at"
              class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-sm text-emerald-700"
            >
              <i class="fa-solid fa-signature mr-2"></i>
              {{ $t('manager.signed_on') }} {{ formatDate(contract.signed_at) }}
            </div>
            <h3 class="section-title">{{ $t('manager.contract_body') }}</h3>
            <div class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ contract.content || '—' }}</div>
          </div>

          <div v-if="contract.notes" class="card p-5">
            <h3 class="section-title">{{ $t('common.notes') }}</h3>
            <p class="text-sm text-gray-600">{{ contract.notes }}</p>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
          <div class="card p-5 space-y-3">
            <h3 class="section-title">{{ $t('common.details') }}</h3>
            <div class="text-sm space-y-2">
              <div class="flex justify-between">
                <span class="text-gray-500">{{ $t('manager.value') }}</span>
                <span class="font-semibold">{{ contract.value ? formatMoney(contract.value) : '—' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">{{ $t('manager.currency') }}</span>
                <span>{{ contract.currency ?? 'PLN' }}</span>
              </div>
            </div>
          </div>

          <div v-if="contract.client" class="card p-5">
            <h3 class="section-title">{{ $t('common.client') }}</h3>
            <Link
              :href="route('tenant.manager.clients.show', contract.client.id)"
              class="text-sm font-medium text-indigo-600 hover:text-indigo-700"
            >
              {{ contract.client.company_name ?? contract.client.name }}
            </Link>
            <p class="text-xs text-gray-400 mt-1">{{ contract.client.email }}</p>
          </div>

          <div v-if="contract.project" class="card p-5">
            <h3 class="section-title">{{ $t('common.project') }}</h3>
            <Link :href="route('tenant.manager.projects.show', contract.project.id)" class="text-sm text-indigo-600">
              {{ contract.project.name }}
            </Link>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import StatusBadge from '@/Components/Manager/StatusBadge.vue'

const props = defineProps({ contract: Object })

const formatMoney = (v) => new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(v)
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')
</script>
