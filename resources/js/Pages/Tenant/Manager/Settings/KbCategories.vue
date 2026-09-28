<template>
  <ManagerLayout :title="$t('kb.knowledge_base_categories')">
    <div class="max-w-4xl">
      <DictionaryTable
        :rows="categories"
        :fields="fields"
        route-base="tenant.manager.kb.categories"
        :title="$t('kb.knowledge_base_categories')"
        description="Podział artykułów bazy wiedzy"
        add-label="Nowa kategoria"
        edit-label="Edytuj kategorię"
        empty-label="Brak kategorii — dodaj pierwszą"
        empty-icon="fa-solid fa-folder-tree"
      />
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import DictionaryTable from '@/Components/Manager/DictionaryTable.vue'

defineProps({ categories: { type: Array, default: () => [] } })

const fields = [
  { key: 'name', label: t('common.name'), required: true },
  { key: 'icon', label: t('settings.icon_font_awesome_class') },
  { key: 'order', label: t('common.order'), type: 'number', default: 0 },
  { key: 'is_public', label: t('kb.visible_to_clients'), type: 'checkbox' },
]
</script>
