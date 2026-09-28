<template>
  <ManagerLayout :title="$t('finance.credit_notes')">
    <div class="space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('finance.credit_notes') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ creditNotes.total }} {{ $t('finance.documents') }}</p>
        </div>
        <button @click="showCreate = true" class="btn-primary text-sm">
          <i class="fa-solid fa-plus mr-1"></i> {{ $t('finance.new_credit_note_2') }}
        </button>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">{{ $t('common.number') }}</th>
              <th class="th">{{ $t('common.invoice') }}</th>
              <th class="th">{{ $t('common.client') }}</th>
              <th class="th text-right">{{ $t('common.amount') }}</th>
              <th class="th">{{ $t('finance.reason') }}</th>
              <th class="th">{{ $t('common.date') }}</th>
              <th class="th"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="note in creditNotes.data" :key="note.id" class="hover:bg-gray-50">
              <td class="td">
                <span class="text-sm font-semibold text-gray-900">{{ note.number }}</span>
              </td>
              <td class="td text-sm text-indigo-600">
                <Link :href="route('tenant.manager.invoices.show', note.invoice_id)">
                  {{ note.invoice?.number }}
                </Link>
              </td>
              <td class="td text-sm text-gray-700">{{ note.invoice?.client?.name }}</td>
              <td class="td text-right text-sm font-semibold text-red-600">-{{ fmt(note.amount) }}</td>
              <td class="td text-sm text-gray-500 max-w-xs truncate">{{ note.reason }}</td>
              <td class="td text-sm text-gray-500">{{ formatDate(note.issued_at) }}</td>
              <td class="td text-right">
                <a
                  :href="route('tenant.manager.credit-notes.pdf', note.id)"
                  target="_blank"
                  class="text-xs text-indigo-600 hover:text-indigo-800"
                >
                  <i class="fa-solid fa-file-pdf mr-0.5"></i> PDF
                </a>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="!creditNotes.data?.length" class="py-16 text-center">
          <i class="fa-solid fa-file-circle-minus text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('finance.no_credit_notes') }}</p>
        </div>
      </div>

      <!-- Create modal -->
      <div
        v-if="showCreate"
        class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center"
        @click.self="showCreate = false"
      >
        <div class="bg-white rounded-xl shadow-xl w-96 p-5">
          <h3 class="font-semibold text-gray-900 mb-4">{{ $t('finance.new_credit_note') }}</h3>
          <form @submit.prevent="create">
            <div class="space-y-3">
              <div>
                <label class="label">{{ $t('common.invoice') }}</label>
                <select v-model="form.invoice_id" class="input" required>
                  <option value="">{{ $t('finance.choose_an_invoice') }}</option>
                  <option v-for="inv in invoices" :key="inv.id" :value="inv.id">
                    {{ inv.number }} — {{ inv.client?.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="label">{{ $t('finance.credit_amount') }}</label>
                <input v-model="form.amount" type="number" step="0.01" min="0.01" class="input" required />
              </div>
              <div>
                <label class="label">{{ $t('finance.reason_for_the_credit') }}</label>
                <textarea v-model="form.reason" rows="3" class="input" required></textarea>
              </div>
            </div>
            <div class="flex justify-end gap-2 mt-5">
              <button type="button" @click="showCreate = false" class="btn-ghost text-sm">
                {{ $t('common.cancel') }}
              </button>
              <button type="submit" class="btn-primary text-sm">{{ $t('finance.issue_credit_note') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  creditNotes: Object,
  invoices: { type: Array, default: () => [] },
})

const showCreate = ref(false)
const form = reactive({ invoice_id: '', amount: '', reason: '' })

const create = () => {
  router.post(route('tenant.manager.credit-notes.store'), form, {
    onSuccess: () => {
      showCreate.value = false
    },
  })
}

const fmt = (n) => new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(n ?? 0)
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')
</script>
