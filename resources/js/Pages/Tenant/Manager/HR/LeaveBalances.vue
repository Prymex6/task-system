<template>
  <ManagerLayout :title="$t('hr.leave_balances')">
    <div class="space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('hr.leave_balances') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ $t('hr.allocating_and_managing_leave') }}</p>
        </div>
        <div class="flex items-center gap-2">
          <select v-model="selectedYear" @change="apply" class="input-sm">
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
          <button @click="showCreate = true" class="btn-primary text-sm">
            <i class="fa-solid fa-plus mr-1"></i> {{ $t('hr.allocate_leave') }}
          </button>
        </div>
      </div>

      <!-- Pending requests banner -->
      <div v-if="pendingRequests.length" class="bg-orange-50 border border-orange-200 rounded-xl p-4">
        <p class="text-sm font-semibold text-orange-800 mb-2">
          <i class="fa-solid fa-clock mr-1"></i>
          {{ $t('hr.leave_requests_waiting') }}{{ pendingRequests.length }})
        </p>
        <div class="space-y-2">
          <div
            v-for="req in pendingRequests"
            :key="req.id"
            class="flex items-center justify-between bg-white rounded-lg p-3"
          >
            <div>
              <p class="text-sm font-medium text-gray-900">{{ req.user?.name }}</p>
              <p class="text-xs text-gray-500">
                {{ formatDate(req.start_date) }} — {{ formatDate(req.end_date) }} · {{ req.leave_type }}
              </p>
            </div>
            <div class="flex gap-2">
              <button
                @click="approve(req)"
                class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded hover:bg-green-200"
              >
                {{ $t('common.approve') }}
              </button>
              <button @click="reject(req)" class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded hover:bg-red-200">
                {{ $t('common.reject') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Balances table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">{{ $t('common.employee') }}</th>
              <th class="th">{{ $t('hr.leave_type') }}</th>
              <th class="th text-right">{{ $t('hr.allocated') }}</th>
              <th class="th text-right">{{ $t('hr.used') }}</th>
              <th class="th text-right">{{ $t('hr.remaining') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="b in balances.data" :key="b.id" class="hover:bg-gray-50">
              <td class="td">
                <div class="flex items-center gap-2">
                  <div
                    class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold"
                  >
                    {{ b.user?.name?.charAt(0) }}
                  </div>
                  <span class="font-medium text-gray-900">{{ b.user?.name }}</span>
                </div>
              </td>
              <td class="td text-gray-600">{{ b.leave_type }}</td>
              <td class="td text-right font-semibold text-gray-900">{{ b.total_days }}</td>
              <td class="td text-right text-orange-600">{{ b.used_days ?? 0 }}</td>
              <td class="td text-right font-semibold" :class="remaining(b) > 0 ? 'text-green-600' : 'text-red-500'">
                {{ remaining(b) }}
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!balances.data?.length" class="py-16 text-center">
          <i class="fa-solid fa-umbrella-beach text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('hr.no_balances_for') }} {{ year }}</p>
        </div>
      </div>

      <Pagination :links="balances.links" />

      <!-- Create modal -->
      <div
        v-if="showCreate"
        class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center"
        @click.self="showCreate = false"
      >
        <div class="bg-white rounded-xl shadow-xl w-80 p-5">
          <h3 class="font-semibold text-gray-900 mb-4">{{ $t('hr.allocate_leave') }}</h3>
          <form @submit.prevent="create" class="space-y-3">
            <div>
              <label class="label">{{ $t('common.employee') }}</label>
              <select v-model="form.user_id" class="input" required>
                <option value="">{{ $t('hr.choose') }}</option>
                <option v-for="u in staff" :key="u.id" :value="u.id">{{ u.name }}</option>
              </select>
            </div>
            <div>
              <label class="label">{{ $t('hr.year') }}</label>
              <input v-model="form.year" type="number" class="input" required />
            </div>
            <div>
              <label class="label">{{ $t('hr.leave_type') }}</label>
              <input v-model="form.leave_type" class="input" :placeholder="$t('hr.e_g_annual_leave')" required />
            </div>
            <div>
              <label class="label">{{ $t('hr.days') }}</label>
              <input v-model="form.total_days" type="number" min="0" class="input" required />
            </div>
            <div class="flex justify-end gap-2 pt-2">
              <button type="button" @click="showCreate = false" class="btn-ghost text-sm">
                {{ $t('common.cancel') }}
              </button>
              <button type="submit" class="btn-primary text-sm">{{ $t('common.save') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  balances: Object,
  pendingRequests: { type: Array, default: () => [] },
  year: Number,
  staff: { type: Array, default: () => [] },
})

const selectedYear = ref(props.year)
const showCreate = ref(false)
const form = reactive({ user_id: '', year: props.year, leave_type: '', total_days: 26 })
const years = computed(() => Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - 1 + i))

const remaining = (b) => b.total_days - (b.used_days ?? 0)
const apply = () =>
  router.get(
    route('tenant.manager.hr.leave-balances.index'),
    { year: selectedYear.value },
    { preserveState: true, replace: true },
  )
const create = () =>
  router.post(route('tenant.manager.hr.leave-balances.store'), form, {
    onSuccess: () => {
      showCreate.value = false
    },
  })
const approve = (req) => router.post(route('tenant.manager.hr.leave-balances.approve', req.id))
const reject = (req) => {
  const reason = prompt(t('hr.reason_for_rejecting_optional'))
  if (reason !== null) router.post(route('tenant.manager.hr.leave-balances.reject', req.id), { reason })
}
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')
</script>
