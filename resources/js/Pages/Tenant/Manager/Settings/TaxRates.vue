<template>
  <ManagerLayout :title="$t('settings.tax_rates')">
    <div class="max-w-4xl">
      <DictionaryTable
        :rows="rates"
        :fields="fields"
        route-base="tenant.manager.tax-rates"
        :title="$t('settings.tax_rates')"
        description="Stawki podatku używane na fakturach i wycenach"
        add-label="Nowa stawka"
        edit-label="Edytuj stawkę"
        empty-label="Brak stawek — dodaj pierwszą"
        empty-icon="fa-solid fa-percent"
      />
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import DictionaryTable from '@/Components/Manager/DictionaryTable.vue'

defineProps({ rates: { type: Array, default: () => [] } })

const fields = [
  { key: 'name', label: t('common.name'), required: true },
  { key: 'rate', label: t('settings.rate'), type: 'number', step: '0.01', required: true },
  { key: 'country', label: t('settings.country_iso_code'), default: 'PL' },
  { key: 'is_default', label: t('settings.default'), type: 'checkbox' },
  { key: 'is_active', label: t('common.active_2'), type: 'checkbox', default: true },
]
</script>
