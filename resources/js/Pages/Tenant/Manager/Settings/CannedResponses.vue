<template>
  <ManagerLayout :title="$t('settings.canned_responses')">
    <div class="max-w-4xl">
      <DictionaryTable
        :rows="responses"
        :fields="fields"
        route-base="tenant.manager.support.canned-responses"
        :title="$t('settings.canned_responses')"
        description="Gotowe odpowiedzi wstawiane do zgłoszeń"
        add-label="Nowy szablon"
        edit-label="Edytuj szablon"
        empty-label="Brak szablonów — dodaj pierwszy"
        empty-icon="fa-solid fa-comment-dots"
      />
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import DictionaryTable from '@/Components/Manager/DictionaryTable.vue'

defineProps({ responses: { type: Array, default: () => [] } })

const fields = [
  { key: 'title', label: t('common.title'), required: true },
  { key: 'body', label: t('projects.body'), type: 'textarea', required: true },
]
</script>
