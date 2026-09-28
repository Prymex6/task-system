<template>
  <ClientLayout title="Dashboard">
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('portal.hello') }} {{ $page.props.auth.contact?.name }}!</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $t('portal.here_are_your_projects_and_documents') }}</p>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
          <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600">
            <i class="fa-solid fa-diagram-project"></i>
          </div>
          <div>
            <div class="text-xl font-bold text-gray-900">{{ stats.projects }}</div>
            <div class="text-xs text-gray-500">{{ $t('portal.projects') }}</div>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
          <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600">
            <i class="fa-solid fa-file-invoice-dollar"></i>
          </div>
          <div>
            <div class="text-xl font-bold text-gray-900">{{ stats.unpaid_invoices }}</div>
            <div class="text-xs text-gray-500">{{ $t('portal.invoices_to_pay') }}</div>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
          <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600">
            <i class="fa-solid fa-file-lines"></i>
          </div>
          <div>
            <div class="text-xl font-bold text-gray-900">{{ stats.pending_estimates }}</div>
            <div class="text-xs text-gray-500">{{ $t('portal.estimates_awaiting_you') }}</div>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
          <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center text-purple-600">
            <i class="fa-solid fa-headset"></i>
          </div>
          <div>
            <div class="text-xl font-bold text-gray-900">{{ stats.open_tickets }}</div>
            <div class="text-xs text-gray-500">{{ $t('portal.open_tickets') }}</div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent projects -->
        <div class="bg-white rounded-xl border border-gray-200">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">{{ $t('common.my_projects') }}</h2>
            <Link :href="route('tenant.portal.projects')" class="text-xs text-indigo-600 hover:text-indigo-700">{{
              $t('portal.all')
            }}</Link>
          </div>
          <div class="divide-y divide-gray-100">
            <div v-if="!recentProjects.length" class="px-5 py-6 text-center text-sm text-gray-400">
              {{ $t('common.no_projects') }}
            </div>
            <div v-for="p in recentProjects" :key="p.id" class="px-5 py-3 hover:bg-gray-50">
              <div class="flex items-center justify-between">
                <Link
                  :href="route('tenant.portal.projects.show', p.id)"
                  class="text-sm font-medium text-gray-800 hover:text-indigo-600"
                >
                  {{ p.name }}
                </Link>
                <span class="badge badge-indigo text-xs">{{ p.status }}</span>
              </div>
              <div class="mt-1.5 flex items-center gap-2">
                <div class="h-1.5 flex-1 bg-gray-200 rounded-full overflow-hidden">
                  <div class="h-1.5 bg-indigo-500 rounded-full" :style="{ width: p.progress + '%' }"></div>
                </div>
                <span class="text-xs text-gray-400">{{ p.progress }}%</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent invoices -->
        <div class="bg-white rounded-xl border border-gray-200">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">{{ $t('portal.recent_invoices') }}</h2>
            <Link :href="route('tenant.portal.invoices')" class="text-xs text-indigo-600 hover:text-indigo-700">{{
              $t('portal.all')
            }}</Link>
          </div>
          <div class="divide-y divide-gray-100">
            <div v-if="!recentInvoices.length" class="px-5 py-6 text-center text-sm text-gray-400">
              {{ $t('common.no_invoices') }}
            </div>
            <div
              v-for="inv in recentInvoices"
              :key="inv.id"
              class="px-5 py-3 flex items-center justify-between hover:bg-gray-50"
            >
              <div>
                <Link
                  :href="route('tenant.portal.invoices.show', inv.id)"
                  class="text-sm font-medium text-gray-800 hover:text-indigo-600"
                >
                  {{ inv.number }}
                </Link>
                <div class="text-xs text-gray-400">{{ formatDate(inv.due_date) }}</div>
              </div>
              <div class="text-right">
                <div class="text-sm font-semibold text-gray-900">{{ formatMoney(inv.total) }}</div>
                <span class="badge text-xs" :class="inv.status === 'paid' ? 'badge-green' : 'badge-yellow'">
                  {{ inv.status === 'paid' ? $t('finance.paid') : $t('common.outstanding') }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </ClientLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'

const props = defineProps({
  stats: { type: Object, default: () => ({ projects: 0, unpaid_invoices: 0, pending_estimates: 0, open_tickets: 0 }) },
  recentProjects: { type: Array, default: () => [] },
  recentInvoices: { type: Array, default: () => [] },
})

const formatMoney = (v) => new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(v ?? 0)
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')
</script>
