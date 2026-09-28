<template>
  <ManagerLayout :title="isEdit ? 'Edycja leada' : 'Nowy lead'">
    <form class="max-w-3xl space-y-5" @submit.prevent="submit">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ isEdit ? 'Edycja leada' : 'Nowy lead' }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $t('crm.a_prospect_before_they_become_a') }}</p>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-5 grid gap-4 sm:grid-cols-2">
        <FormField :label="$t('common.company')" :error="form.errors.company_name" required class="sm:col-span-2">
          <input v-model="form.company_name" type="text" class="input" required />
        </FormField>
        <FormField :label="$t('platform.contact')" :error="form.errors.contact_name">
          <input v-model="form.contact_name" type="text" class="input" />
        </FormField>
        <FormField label="E-mail" :error="form.errors.email">
          <input v-model="form.email" type="email" class="input" />
        </FormField>
        <FormField :label="$t('common.phone')" :error="form.errors.phone">
          <input v-model="form.phone" type="text" class="input" />
        </FormField>
        <FormField :label="$t('common.website')" :error="form.errors.website">
          <input v-model="form.website" type="text" class="input" />
        </FormField>
        <FormField :label="$t('common.city')" :error="form.errors.city">
          <input v-model="form.city" type="text" class="input" />
        </FormField>
        <FormField :label="$t('common.industry')" :error="form.errors.industry">
          <input v-model="form.industry" type="text" class="input" />
        </FormField>
        <FormField :label="$t('common.source')" :error="form.errors.source">
          <input v-model="form.source" type="text" class="input" :placeholder="$t('crm.referral_trade_show_website')" />
        </FormField>
        <FormField :label="$t('common.status')" :error="form.errors.status">
          <select v-model="form.status" class="input">
            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
          </select>
        </FormField>
        <FormField :label="$t('crm.estimated_value')" :error="form.errors.value">
          <input v-model="form.value" type="number" step="0.01" min="0" class="input" />
        </FormField>
        <FormField :label="$t('common.currency')" :error="form.errors.currency">
          <input v-model="form.currency" type="text" maxlength="5" class="input" />
        </FormField>
        <FormField :label="$t('common.notes')" :error="form.errors.notes" class="sm:col-span-2">
          <textarea v-model="form.notes" rows="4" class="input"></textarea>
        </FormField>
      </div>

      <div class="flex items-center gap-3">
        <button type="submit" class="btn-primary" :disabled="form.processing">
          {{ isEdit ? 'Zapisz zmiany' : 'Dodaj leada' }}
        </button>
        <Link :href="route('tenant.manager.leads.index')" class="btn-ghost">{{ $t('common.cancel') }}</Link>
      </div>
    </form>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import FormField from '@/Components/Manager/FormField.vue'

const props = defineProps({ lead: { type: Object, default: null } })

const isEdit = computed(() => Boolean(props.lead))

const statuses = [
  { value: 'new', label: t('common.new') },
  { value: 'contacted', label: t('common.contacted') },
  { value: 'qualified', label: t('crm.qualified_2') },
  { value: 'unqualified', label: t('crm.disqualified') },
  { value: 'proposal', label: t('common.proposal') },
  { value: 'negotiation', label: t('crm.negotiation') },
  { value: 'won', label: t('common.won') },
  { value: 'lost', label: t('common.lost') },
]

const form = useForm({
  company_name: props.lead?.company_name ?? '',
  contact_name: props.lead?.contact_name ?? '',
  email: props.lead?.email ?? '',
  phone: props.lead?.phone ?? '',
  website: props.lead?.website ?? '',
  city: props.lead?.city ?? '',
  industry: props.lead?.industry ?? '',
  source: props.lead?.source ?? '',
  status: props.lead?.status ?? 'new',
  value: props.lead?.value ?? '',
  currency: props.lead?.currency ?? 'PLN',
  notes: props.lead?.notes ?? '',
})

const submit = () => {
  if (isEdit.value) {
    form.put(route('tenant.manager.leads.update', props.lead.id))
  } else {
    form.post(route('tenant.manager.leads.store'))
  }
}
</script>
