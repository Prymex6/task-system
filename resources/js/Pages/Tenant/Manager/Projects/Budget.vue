<template>
  <ManagerLayout :title="project.name + ' — ' + $t('projects.budget')">
    <div class="max-w-3xl space-y-5">
      <div class="flex items-center gap-3">
        <Link :href="route('tenant.manager.projects.show', project.id)" class="text-gray-400 hover:text-gray-600">
          <i class="fa-solid fa-arrow-left"></i>
        </Link>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('projects.project_budget') }}</h1>
          <p class="text-sm text-gray-500">{{ project.name }}</p>
        </div>
      </div>

      <!-- Budget settings -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('projects.budget_settings') }}</h2>
        <form @submit.prevent="saveBudget" class="flex items-end gap-4">
          <div class="flex-1">
            <label class="label">{{ $t('projects.total_budget') }}</label>
            <input v-model="budgetForm.budget" type="number" step="0.01" class="input" />
          </div>
          <div class="flex-1">
            <label class="label">{{ $t('projects.hourly_rate') }}</label>
            <input v-model="budgetForm.hourly_rate" type="number" step="0.01" class="input" />
          </div>
          <button type="submit" class="btn-primary text-sm">{{ $t('common.save') }}</button>
        </form>
      </div>

      <!-- Summary cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
          <p class="text-xs text-gray-500">{{ $t('projects.budget') }}</p>
          <p class="text-xl font-bold text-gray-900 mt-1">{{ formatMoney(summary.budget) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
          <p class="text-xs text-gray-500">{{ $t('finance.revenue') }}</p>
          <p class="text-xl font-bold text-green-600 mt-1">{{ formatMoney(summary.total_income) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
          <p class="text-xs text-gray-500">{{ $t('common.expenses') }}</p>
          <p class="text-xl font-bold text-red-500 mt-1">{{ formatMoney(summary.total_expenses) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
          <p class="text-xs text-gray-500">{{ $t('projects.profit') }}</p>
          <p class="text-xl font-bold mt-1" :class="(summary.profit ?? 0) >= 0 ? 'text-green-600' : 'text-red-500'">
            {{ formatMoney(summary.profit) }}
          </p>
        </div>
      </div>

      <!-- Add entry -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('projects.add_budget_entry') }}</h2>
        <form @submit.prevent="addEntry" class="flex flex-wrap items-end gap-3">
          <div>
            <label class="label">{{ $t('common.type') }}</label>
            <select v-model="entryForm.type" class="input-sm" required>
              <option value="income">{{ $t('common.revenue') }}</option>
              <option value="expense">{{ $t('projects.expense') }}</option>
            </select>
          </div>
          <div>
            <label class="label">{{ $t('projects.amount') }}</label>
            <input v-model="entryForm.amount" type="number" step="0.01" class="input-sm w-32" required />
          </div>
          <div class="flex-1 min-w-40">
            <label class="label">{{ $t('common.description') }}</label>
            <input v-model="entryForm.description" class="input-sm" />
          </div>
          <div>
            <label class="label">{{ $t('common.date') }}</label>
            <input v-model="entryForm.date" type="date" class="input-sm" />
          </div>
          <button type="submit" class="btn-primary text-sm">{{ $t('common.add') }}</button>
        </form>
      </div>

      <!-- Entries table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200">
          <h2 class="text-sm font-semibold text-gray-700">{{ $t('projects.budget_entries') }}</h2>
        </div>
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="th">{{ $t('common.type') }}</th>
              <th class="th">{{ $t('common.description') }}</th>
              <th class="th text-right">{{ $t('common.amount') }}</th>
              <th class="th">{{ $t('common.date') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="e in summary.entries ?? []" :key="e.id" class="hover:bg-gray-50">
              <td class="td">
                <span
                  class="text-xs px-2 py-0.5 rounded-full font-medium"
                  :class="e.type === 'income' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                >
                  {{ e.type === 'income' ? $t('common.revenue') : $t('projects.expense') }}
                </span>
              </td>
              <td class="td text-gray-700">{{ e.description ?? '—' }}</td>
              <td class="td text-right font-semibold" :class="e.type === 'income' ? 'text-green-600' : 'text-red-500'">
                {{ e.type === 'income' ? '+' : '-' }}{{ formatMoney(e.amount) }}
              </td>
              <td class="td text-gray-400">{{ formatDate(e.entry_date) }}</td>
            </tr>
          </tbody>
        </table>
        <div v-if="!summary.entries?.length" class="py-12 text-center">
          <p class="text-gray-500 text-sm">{{ $t('projects.no_budget_entries') }}</p>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { intlLocale } from '@/format'

const props = defineProps({
  project: Object,
  summary: { type: Object, default: () => ({}) },
})

const budgetForm = reactive({ budget: props.project.budget ?? '', hourly_rate: props.project.hourly_rate ?? '' })
const entryForm = reactive({ type: 'income', amount: '', description: '', date: new Date().toISOString().slice(0, 10) })

const saveBudget = () => router.put(route('tenant.manager.projects.budget.update', props.project.id), budgetForm)
const addEntry = () =>
  router.post(route('tenant.manager.projects.budget.store', props.project.id), entryForm, {
    onSuccess: () =>
      Object.assign(entryForm, { amount: '', description: '', date: new Date().toISOString().slice(0, 10) }),
  })

const formatMoney = (v) =>
  v != null ? new Intl.NumberFormat(intlLocale(), { style: 'currency', currency: 'PLN' }).format(v) : '—'
const formatDate = (d) => (d ? new Date(d).toLocaleDateString(intlLocale()) : '—')
</script>
