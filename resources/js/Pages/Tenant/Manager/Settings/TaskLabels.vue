<template>
  <ManagerLayout :title="$t('settings.task_labels')">
    <div class="max-w-4xl">
      <DictionaryTable
        :rows="labels"
        :fields="fields"
        route-base="tenant.manager.task-labels"
        :title="$t('settings.task_labels')"
        description="Kolorowe etykiety do oznaczania zadań"
        add-label="Nowa etykieta"
        edit-label="Edytuj etykietę"
        empty-label="Brak etykiet — dodaj pierwszą"
        empty-icon="fa-solid fa-tag"
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

defineProps({ labels: { type: Array, default: () => [] } })

const fields = [
  { key: 'name', label: t('common.name'), required: true },
  { key: 'color', label: t('common.colour'), type: 'color', default: '#6366f1' },
]
</script>
