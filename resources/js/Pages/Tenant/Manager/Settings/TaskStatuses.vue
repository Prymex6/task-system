<template>
  <ManagerLayout :title="$t('settings.task_statuses')">
    <div class="max-w-4xl">
      <DictionaryTable
        :rows="statuses"
        :fields="fields"
        route-base="tenant.manager.task-statuses"
        :title="$t('settings.task_statuses')"
        description="Kolumny tablicy zadań i etapy, przez które przechodzi zadanie"
        add-label="Nowy status"
        edit-label="Edytuj status"
        empty-label="Brak statusów — dodaj pierwszy"
        empty-icon="fa-solid fa-list-check"
        count-key="tasks_count"
        count-label="Zadania"
      />
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import DictionaryTable from '@/Components/Manager/DictionaryTable.vue'

defineProps({ statuses: { type: Array, default: () => [] } })

const fields = [
  { key: 'name', label: t('common.name'), required: true },
  { key: 'color', label: t('common.colour'), type: 'color', default: '#6b7280' },
  { key: 'is_default', label: t('settings.default_for_new_tasks'), type: 'checkbox' },
  { key: 'is_closed', label: t('settings.marks_the_task_finished'), type: 'checkbox' },
]
</script>
