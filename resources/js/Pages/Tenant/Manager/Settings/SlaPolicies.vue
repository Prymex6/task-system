<template>
  <ManagerLayout :title="$t('settings.sla_policies')">
    <div class="max-w-4xl">
      <DictionaryTable
        :rows="policies"
        :fields="fields"
        route-base="tenant.manager.support.sla-policies"
        :title="$t('settings.sla_policies')"
        description="Czasy reakcji i rozwiązania zgłoszeń"
        add-label="Nowa polityka"
        edit-label="Edytuj politykę"
        empty-label="Brak polityk — dodaj pierwszą"
        empty-icon="fa-solid fa-stopwatch"
      />
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import DictionaryTable from '@/Components/Manager/DictionaryTable.vue'

defineProps({ policies: { type: Array, default: () => [] } })

const fields = [
  { key: 'name', label: t('common.name'), required: true },
  {
    key: 'priority',
    label: t('common.priority'),
    type: 'select',
    required: true,
    default: 'medium',
    options: [
      { value: 'low', label: t('common.low') },
      { value: 'medium', label: t('common.medium') },
      { value: 'high', label: t('common.high') },
      { value: 'urgent', label: t('common.urgent') },
    ],
  },
  { key: 'response_hours', label: t('settings.first_response_h'), type: 'number', required: true, default: 8 },
  { key: 'resolution_hours', label: t('settings.resolution_h'), type: 'number', required: true, default: 48 },
]
</script>
