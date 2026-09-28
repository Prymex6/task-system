<template>
  <ManagerLayout :title="$t('settings.settings_finance')">
    <div class="max-w-2xl space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('settings.finance_settings') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $t('settings.document_numbering_currencies_and_tax') }}</p>
      </div>

      <form @submit.prevent="save" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="label">{{ $t('settings.invoice_prefix') }}</label>
            <input v-model="form.invoice_prefix" class="input" placeholder="FV" />
          </div>
          <div>
            <label class="label">{{ $t('settings.estimate_prefix') }}</label>
            <input v-model="form.estimate_prefix" class="input" placeholder="WYC" />
          </div>
          <div>
            <label class="label">{{ $t('settings.credit_note_prefix') }}</label>
            <input v-model="form.credit_note_prefix" class="input" placeholder="KOR" />
          </div>
          <div>
            <label class="label">{{ $t('settings.default_payment_terms_days') }}</label>
            <input v-model="form.default_payment_days" type="number" min="0" class="input" />
          </div>
          <div>
            <label class="label">{{ $t('settings.default_currency') }}</label>
            <select v-model="form.default_currency" class="input">
              <option v-for="c in currencies" :key="c.code" :value="c.code">{{ c.code }} — {{ c.name }}</option>
            </select>
          </div>
          <div>
            <label class="label">{{ $t('settings.default_tax_rate') }}</label>
            <select v-model="form.default_tax_rate_id" class="input">
              <option value="">{{ $t('settings.none') }}</option>
              <option v-for="t in taxRates" :key="t.id" :value="t.id">{{ t.name }} ({{ t.rate }}%)</option>
            </select>
          </div>
          <div class="col-span-2">
            <label class="label">{{ $t('settings.invoice_reminders_days') }}</label>
            <p class="text-xs text-gray-500 mb-1">{{ $t('settings.before_and_after_the_due_date') }}</p>
            <input v-model="form.invoice_reminder_days" class="input" placeholder="7, 3, 1, -3, -7" />
          </div>
        </div>

        <!-- Tax rates -->
        <div class="border-t border-gray-100 pt-5">
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-gray-700">{{ $t('settings.tax_rates') }}</h2>
            <button type="button" @click="addRate" class="btn-ghost text-sm">
              <i class="fa-solid fa-plus mr-1"></i> {{ $t('common.add') }}
            </button>
          </div>
          <div class="space-y-2">
            <div v-for="(rate, i) in form.tax_rates" :key="i" class="flex items-center gap-2">
              <input v-model="rate.name" class="input flex-1" :placeholder="$t('settings.name_e_g_vat_23')" />
              <input v-model="rate.rate" type="number" step="0.01" class="input w-24" placeholder="%" />
              <label class="flex items-center gap-1 text-xs text-gray-600">
                <input type="checkbox" v-model="rate.is_default" />{{ $t('settings.def') }}
              </label>
              <button type="button" @click="form.tax_rates.splice(i, 1)" class="text-red-400 hover:text-red-600">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <button type="submit" class="btn-primary">{{ $t('common.save_changes') }}</button>
        </div>
      </form>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  settings: Object,
  currencies: Array,
  taxRates: Array,
})

const form = reactive({
  invoice_prefix: props.settings?.invoice_prefix ?? 'FV',
  estimate_prefix: props.settings?.estimate_prefix ?? 'WYC',
  credit_note_prefix: props.settings?.credit_note_prefix ?? 'KOR',
  default_payment_days: props.settings?.default_payment_days ?? 14,
  default_currency: props.settings?.default_currency ?? 'PLN',
  default_tax_rate_id: props.settings?.default_tax_rate_id ?? '',
  invoice_reminder_days: props.settings?.invoice_reminder_days ?? '7, 3, 1',
  tax_rates: props.taxRates ?? [],
})

const addRate = () => form.tax_rates.push({ name: '', rate: 0, is_default: false })

const save = () => router.post(route('tenant.manager.settings.finance.update'), form, { preserveScroll: true })
</script>
