<template>
  <ManagerLayout :title="$t('settings.activity_log')">
    <div class="space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('settings.activity_log') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ $t('settings.what_changed_and_who_changed_it') }}</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
        <input
          v-model="filters.search"
          @input="apply"
          :placeholder="$t('settings.search_actions')"
          class="input-sm flex-1 min-w-[200px]"
        />
        <select v-model="filters.user_id" @change="apply" class="input-sm">
          <option value="">{{ $t('settings.all_users') }}</option>
          <option v-for="u in staff" :key="u.id" :value="u.id">{{ u.name }}</option>
        </select>
        <input v-model="filters.from" type="date" @change="apply" class="input-sm" />
        <input v-model="filters.to" type="date" @change="apply" class="input-sm" />
      </div>

      <!-- Log table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">{{ $t('settings.time') }}</th>
              <th class="th">{{ $t('crm.user') }}</th>
              <th class="th">{{ $t('common.action') }}</th>
              <th class="th">{{ $t('common.model') }}</th>
              <th class="th">IP</th>
              <th class="th text-right">{{ $t('common.details') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50">
              <td class="td text-xs text-gray-500">{{ formatDate(log.created_at) }}</td>
              <td class="td">
                <span class="text-sm text-gray-700">{{ log.user?.name ?? log.user_type }}</span>
              </td>
              <td class="td">
                <code class="text-xs bg-gray-100 text-gray-700 px-1.5 py-0.5 rounded">{{ log.action }}</code>
              </td>
              <td class="td text-xs text-gray-500">
                {{ log.subject_type }}
                <span v-if="log.subject_id" class="text-gray-400">#{{ log.subject_id }}</span>
              </td>
              <td class="td text-xs text-gray-400">{{ log.ip_address }}</td>
              <td class="td text-right">
                <button
                  v-if="log.old_values || log.new_values"
                  @click="selected = log"
                  class="text-xs text-indigo-600 hover:text-indigo-800"
                >
                  {{ $t('settings.show_diff') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!logs.data?.length" class="py-16 text-center">
          <i class="fa-solid fa-list-ul text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('settings.no_entries') }}</p>
        </div>
      </div>

      <Pagination :links="logs.links" />

      <!-- Diff modal -->
      <div
        v-if="selected"
        class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center"
        @click.self="selected = null"
      >
        <div class="bg-white rounded-xl shadow-xl w-[600px] p-5 max-h-[80vh] overflow-y-auto">
          <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-900">{{ selected.action }}</h3>
            <button @click="selected = null" class="text-gray-400 hover:text-gray-600">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div v-if="selected.old_values">
              <p class="text-xs font-semibold text-red-600 mb-1">{{ $t('common.before') }}</p>
              <pre class="text-xs bg-red-50 p-3 rounded overflow-x-auto">{{
                JSON.stringify(selected.old_values, null, 2)
              }}</pre>
            </div>
            <div v-if="selected.new_values">
              <p class="text-xs font-semibold text-green-600 mb-1">{{ $t('common.after') }}</p>
              <pre class="text-xs bg-green-50 p-3 rounded overflow-x-auto">{{
                JSON.stringify(selected.new_values, null, 2)
              }}</pre>
            </div>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  logs: Object,
  staff: { type: Array, default: () => [] },
  filters: Object,
})

const filters = reactive({
  search: props.filters?.search ?? '',
  user_id: props.filters?.user_id ?? '',
  from: props.filters?.from ?? '',
  to: props.filters?.to ?? '',
})
const selected = ref(null)

const apply = () => router.get(route('tenant.manager.audit-log.index'), filters, { preserveState: true, replace: true })

const formatDate = (d) => (d ? new Date(d).toLocaleString('pl-PL') : '—')
</script>
