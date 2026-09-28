<template>
  <ManagerLayout :title="$t('settings.support_departments')">
    <div class="max-w-4xl">
      <DictionaryTable
        :rows="departments"
        :fields="fields"
        route-base="tenant.manager.support.departments"
        :title="$t('settings.support_departments')"
        description="Kolejki zgłoszeń i skrzynki, na które trafiają"
        add-label="Nowy dział"
        edit-label="Edytuj dział"
        empty-label="Brak działów — dodaj pierwszy"
        empty-icon="fa-solid fa-headset"
      />
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import DictionaryTable from '@/Components/Manager/DictionaryTable.vue'

defineProps({ departments: { type: Array, default: () => [] } })

const fields = [
  { key: 'name', label: t('common.name'), required: true },
  { key: 'email', label: t('settings.mailbox'), type: 'email' },
]
</script>
