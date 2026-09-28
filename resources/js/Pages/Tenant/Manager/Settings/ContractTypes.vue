<template>
  <ManagerLayout :title="$t('settings.contract_types')">
    <div class="max-w-4xl">
      <DictionaryTable
        :rows="types"
        :fields="fields"
        route-base="tenant.manager.contracts.types"
        :title="$t('settings.contract_types')"
        description="Kategorie, na jakie dzielą się umowy z klientami"
        add-label="Nowy typ"
        edit-label="Edytuj typ"
        empty-label="Brak typów — dodaj pierwszy"
        empty-icon="fa-solid fa-file-contract"
      />
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import DictionaryTable from '@/Components/Manager/DictionaryTable.vue'

defineProps({ types: { type: Array, default: () => [] } })

const fields = [{ key: 'name', label: t('common.name'), required: true }]
</script>
