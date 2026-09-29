<template>
  <ManagerLayout :title="$t('common.expenses')">
    <div class="space-y-5">
      <div class="page-header">
        <div>
          <h1 class="page-title">{{ $t('common.expenses') }}</h1>
          <p class="page-subtitle">{{ expenses.total }} {{ $t('common.in_total') }}</p>
        </div>
        <button @click="showAdd = true" class="btn-primary" data-testid="create-expense">
          <i class="fa-solid fa-plus"></i> {{ $t('finance.add_expense') }}
        </button>
      </div>

      <!-- Summary -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="stat-card">
          <div class="stat-icon bg-red-100 text-red-600"><i class="fa-solid fa-receipt"></i></div>
          <div>
            <div class="stat-value">{{ formatMoney(summary.total_month) }}</div>
            <div class="stat-label">{{ $t('finance.this_month') }}</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-amber-100 text-amber-600"><i class="fa-solid fa-clock-rotate-left"></i></div>
          <div>
            <div class="stat-value">{{ formatMoney(summary.total_year) }}</div>
            <div class="stat-label">{{ $t('finance.this_year') }}</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-orange-100 text-orange-600"><i class="fa-solid fa-hourglass-half"></i></div>
          <div>
            <div class="stat-value">{{ summary.pending }}</div>
            <div class="stat-label">{{ $t('common.pending') }}</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon bg-emerald-100 text-emerald-600"><i class="fa-solid fa-check-circle"></i></div>
          <div>
            <div class="stat-value">{{ summary.approved }}</div>
            <div class="stat-label">{{ $t('common.approved') }}</div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filter-bar">
        <input
          v-model="filters.search"
          type="text"
          :placeholder="$t('common.search')"
          class="input input-sm w-48"
          @input="search"
        />
        <select v-model="filters.category" class="select input-sm w-40" @change="search">
          <option value="">{{ $t('finance.all_categories') }}</option>
          <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
        </select>
        <select v-model="filters.status" class="select input-sm w-36" @change="search">
          <option value="">{{ $t('common.all_statuses') }}</option>
          <option value="pending">{{ $t('common.pending') }}</option>
          <option value="approved">{{ $t('common.approved') }}</option>
          <option value="rejected">{{ $t('common.rejected_2') }}</option>
        </select>
      </div>

      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th class="th">{{ $t('common.date') }}</th>
              <th class="th">{{ $t('common.description') }}</th>
              <th class="th">{{ $t('common.category') }}</th>
              <th class="th">{{ $t('common.project') }}</th>
              <th class="th">{{ $t('common.amount') }}</th>
              <th class="th">{{ $t('common.status') }}</th>
              <th class="th">{{ $t('common.employee') }}</th>
              <th class="th"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="!expenses.data.length">
              <td colspan="8" class="td text-center text-gray-400 py-8">{{ $t('finance.no_expenses') }}</td>
            </tr>
            <tr v-for="exp in expenses.data" :key="exp.id" class="tr-hover">
              <td class="td text-xs text-gray-500">{{ formatDate(exp.date) }}</td>
              <td class="td text-gray-800">{{ exp.description }}</td>
              <td class="td">
                <span class="badge badge-gray text-xs">{{ exp.category }}</span>
              </td>
              <td class="td text-xs text-gray-500">{{ exp.project?.name ?? '—' }}</td>
              <td class="td font-semibold text-gray-900">{{ formatMoney(exp.amount) }}</td>
              <td class="td">
                <span :class="statusClass(exp.status)" class="badge text-xs">{{ statusLabel(exp.status) }}</span>
              </td>
              <td class="td text-xs text-gray-500">{{ exp.user?.name }}</td>
              <td class="td">
                <div class="flex gap-1">
                  <a
                    v-if="exp.receipt_url"
                    :href="exp.receipt_url"
                    target="_blank"
                    class="btn-ghost btn-sm"
                    :title="$t('finance.receipt')"
                  >
                    <i class="fa-solid fa-paperclip"></i>
                  </a>
                  <button
                    v-if="exp.status === 'pending' && canApprove"
                    @click="approve(exp.id)"
                    class="btn-ghost btn-sm text-emerald-600"
                  >
                    <i class="fa-solid fa-check"></i>
                  </button>
                  <button
                    v-if="exp.status === 'pending'"
                    @click="deleteExpense(exp.id)"
                    class="btn-ghost btn-sm text-red-500"
                  >
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <Pagination :links="expenses.links" />
    </div>

    <!-- Add Expense Modal -->
    <div v-if="showAdd" class="modal-backdrop" @click.self="showAdd = false">
      <div class="modal-md">
        <div class="modal-header">
          <h3 class="modal-title">{{ $t('finance.add_expense') }}</h3>
          <button @click="showAdd = false" class="btn-ghost btn-sm"><i class="fa-solid fa-times"></i></button>
        </div>
        <form @submit.prevent="addExpense">
          <div class="modal-body space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="label">{{ $t('common.date') }}</label>
                <input v-model="addForm.date" type="date" class="input" required />
              </div>
              <div>
                <label class="label">{{ $t('common.amount') }}</label>
                <input v-model="addForm.amount" type="number" step="0.01" min="0" class="input" required />
              </div>
              <div class="col-span-2">
                <label class="label">{{ $t('common.description') }}</label>
                <input v-model="addForm.description" type="text" class="input" required />
              </div>
              <div>
                <label class="label">{{ $t('common.category') }}</label>
                <select v-model="addForm.category" class="select">
                  <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                </select>
              </div>
              <div>
                <label class="label">{{ $t('common.project') }}</label>
                <select v-model="addForm.project_id" class="select">
                  <option value="">{{ $t('common.none') }}</option>
                  <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
              </div>
              <div class="col-span-2">
                <label class="label">{{ $t('finance.receipt_or_invoice') }}</label>
                <input
                  type="file"
                  @change="(e) => (addForm.receipt = e.target.files[0])"
                  class="input"
                  accept="image/*,application/pdf"
                />
              </div>
              <div class="col-span-2">
                <label class="label">{{ $t('common.note') }}</label>
                <textarea v-model="addForm.notes" class="textarea" rows="2"></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" @click="showAdd = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="addForm.processing" class="btn-primary">
              {{ $t('finance.add_expense') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref, reactive } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  expenses: Object,
  summary: { type: Object, default: () => ({ total_month: 0, total_year: 0, pending: 0, approved: 0 }) },
  categories: { type: Array, default: () => [] },
  projects: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  canApprove: Boolean,
})

const showAdd = ref(false)
const filters = reactive({
  search: props.filters.search ?? '',
  category: props.filters.category ?? '',
  status: props.filters.status ?? '',
})

const search = () => {
  router.get(route('tenant.manager.expenses.index'), filters, { preserveState: true, replace: true })
}

const addForm = useForm({
  date: new Date().toISOString().slice(0, 10),
  amount: '',
  description: '',
  category: props.categories[0] ?? '',
  project_id: '',
  notes: '',
  receipt: null,
})

const addExpense = () => {
  addForm.post(route('tenant.manager.expenses.store'), {
    onSuccess: () => {
      showAdd.value = false
      addForm.reset()
    },
  })
}

const approve = (id) => {
  useForm({}).patch(route('tenant.manager.expenses.approve', id))
}

const deleteExpense = (id) => {
  if (!confirm(t('finance.delete_this_expense'))) return
  useForm({}).delete(route('tenant.manager.expenses.destroy', id))
}

const statusLabel = (s) => ({ pending: t('common.pending_3'), approved: 'Zatwierdzony', rejected: 'Odrzucony' })[s] ?? s
const statusClass = (s) =>
  ({ pending: 'badge-yellow', approved: 'badge-green', rejected: 'badge-red' })[s] ?? 'badge-gray'
const formatMoney = (v) => new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(v ?? 0)
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')
</script>
