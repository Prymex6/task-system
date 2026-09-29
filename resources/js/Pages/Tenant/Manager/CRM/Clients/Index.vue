<template>
  <ManagerLayout :title="$t('crm.clients')">
    <div class="space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('crm.clients') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ clients.total }} {{ $t('crm.clients_2') }}</p>
        </div>
        <Link
          :href="route('tenant.manager.clients.create')"
          class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
        >
          <i class="fa-solid fa-plus"></i> {{ $t('crm.new_client') }}
        </Link>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
        <input
          v-model="filters.search"
          @input="apply"
          :placeholder="$t('crm.search_clients')"
          class="input-sm flex-1 min-w-[200px]"
        />
        <select v-model="filters.status" @change="apply" class="input-sm">
          <option value="">{{ $t('common.everyone') }}</option>
          <option value="active">{{ $t('crm.active') }}</option>
          <option value="inactive">{{ $t('crm.inactive') }}</option>
        </select>
        <select v-model="filters.group_id" @change="apply" class="input-sm">
          <option value="">{{ $t('crm.all_groups') }}</option>
          <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
        </select>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">{{ $t('common.client') }}</th>
              <th class="th">E-mail</th>
              <th class="th">{{ $t('common.phone') }}</th>
              <th class="th">{{ $t('common.projects') }}</th>
              <th class="th">{{ $t('common.invoices') }}</th>
              <th class="th">{{ $t('common.status') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="client in clients.data"
              :key="client.id"
              class="hover:bg-gray-50 cursor-pointer"
              @click="router.visit(route('tenant.manager.clients.show', client.id))"
            >
              <td class="td">
                <div>
                  <p class="font-medium text-gray-900">{{ client.company_name || client.name }}</p>
                  <p v-if="client.company_name" class="text-xs text-gray-400">{{ client.name }}</p>
                </div>
              </td>
              <td class="td text-sm text-gray-600">{{ client.email }}</td>
              <td class="td text-sm text-gray-600">{{ client.phone ?? '—' }}</td>
              <td class="td text-sm text-gray-700">{{ client.projects_count }}</td>
              <td class="td text-sm text-gray-700">{{ client.invoices_count }}</td>
              <td class="td">
                <span
                  :class="client.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                  class="text-xs px-2 py-0.5 rounded-full"
                >
                  {{ client.is_active ? $t('common.active') : $t('common.inactive') }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!clients.data.length" class="py-16 text-center">
          <i class="fa-solid fa-building text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('crm.no_clients') }}</p>
        </div>
      </div>

      <Pagination :links="clients.links" />
    </div>
  </ManagerLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  clients: Object,
  groups: Array,
  filters: Object,
})

const filters = reactive({ ...props.filters })
const apply = () => router.get(route('tenant.manager.clients.index'), filters, { preserveState: true, replace: true })
</script>
