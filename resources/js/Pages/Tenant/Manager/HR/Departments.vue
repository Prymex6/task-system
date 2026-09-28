<template>
  <ManagerLayout :title="$t('hr.departments')">
    <div class="max-w-4xl">
      <DictionaryTable
        :rows="departments"
        :fields="fields"
        route-base="tenant.manager.departments"
        :title="$t('hr.departments')"
        description="Struktura organizacyjna firmy i osoby nimi kierujące"
        add-label="Nowy dział"
        edit-label="Edytuj dział"
        empty-label="Brak działów — dodaj pierwszy"
        empty-icon="fa-solid fa-sitemap"
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
  { key: 'description', label: t('common.description'), type: 'textarea' },
]
</script>
