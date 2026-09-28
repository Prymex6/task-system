<template>
  <ManagerLayout :title="$t('settings.currencies')">
    <div class="max-w-4xl">
      <DictionaryTable
        :rows="currencies"
        :fields="fields"
        route-base="tenant.manager.currencies"
        :title="$t('settings.currencies')"
        description="Waluty rozliczeniowe i ich kursy do waluty bazowej"
        add-label="Nowa waluta"
        edit-label="Edytuj walutę"
        empty-label="Brak walut — dodaj pierwszą"
        empty-icon="fa-solid fa-coins"
      />
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import DictionaryTable from '@/Components/Manager/DictionaryTable.vue'

defineProps({ currencies: { type: Array, default: () => [] } })

const fields = [
  { key: 'code', label: t('settings.code_iso_4217'), required: true },
  { key: 'symbol', label: t('common.symbol'), required: true },
  { key: 'name', label: t('common.name'), required: true },
  {
    key: 'rate_to_base',
    label: t('settings.rate_against_the_base'),
    type: 'number',
    step: '0.000001',
    required: true,
    default: 1,
  },
  { key: 'is_default', label: t('settings.base'), type: 'checkbox' },
  { key: 'is_active', label: t('common.active_2'), type: 'checkbox', default: true },
]
</script>
