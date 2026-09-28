<template>
  <ManagerLayout :title="$t('settings.client_groups')">
    <div class="max-w-4xl">
      <DictionaryTable
        :rows="groups"
        :fields="fields"
        route-base="tenant.manager.client-groups"
        :title="$t('settings.client_groups')"
        description="Podział klientów na segmenty"
        add-label="Nowa grupa"
        edit-label="Edytuj grupę"
        empty-label="Brak grup — dodaj pierwszą"
        empty-icon="fa-solid fa-users-rectangle"
      />
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import DictionaryTable from '@/Components/Manager/DictionaryTable.vue'

defineProps({ groups: { type: Array, default: () => [] } })

const fields = [
  { key: 'name', label: t('common.name'), required: true },
  { key: 'color', label: t('common.colour'), type: 'color', default: '#6366f1' },
  { key: 'description', label: t('common.description'), type: 'textarea' },
]
</script>
