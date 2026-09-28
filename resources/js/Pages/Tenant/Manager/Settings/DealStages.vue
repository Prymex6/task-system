<template>
  <ManagerLayout :title="$t('crm.deal_stages')">
    <div class="max-w-4xl">
      <DictionaryTable
        :rows="stages"
        :fields="fields"
        route-base="tenant.manager.deals.stages"
        :title="$t('crm.deal_stages')"
        description="Kolumny lejka i szansa domknięcia deala na każdym etapie"
        add-label="Nowy etap"
        edit-label="Edytuj etap"
        empty-label="Brak etapów — dodaj pierwszy"
        empty-icon="fa-solid fa-diagram-project"
        count-key="deals_count"
        count-label="Deale"
      />
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import DictionaryTable from '@/Components/Manager/DictionaryTable.vue'

defineProps({ stages: { type: Array, default: () => [] } })

const fields = [
  { key: 'name', label: t('common.name'), required: true },
  { key: 'color', label: t('common.colour'), type: 'color', default: '#6366f1' },
  {
    key: 'win_probability',
    label: t('settings.win_probability'),
    type: 'number',
    step: '0.01',
    required: true,
    default: 0,
  },
  { key: 'is_won', label: t('settings.won_stage'), type: 'checkbox' },
  { key: 'is_lost', label: t('settings.lost_stage'), type: 'checkbox' },
]
</script>
