<template>
  <ManagerLayout :title="$t('hr.positions')">
    <div class="max-w-4xl">
      <DictionaryTable
        :rows="positions"
        :fields="fields"
        route-base="tenant.manager.positions"
        :title="$t('hr.positions')"
        description="Nazwy stanowisk i działy, do których są przypisane"
        add-label="Nowe stanowisko"
        edit-label="Edytuj stanowisko"
        empty-label="Brak stanowisk — dodaj pierwsze"
        empty-icon="fa-solid fa-id-badge"
      />
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { computed } from 'vue'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import DictionaryTable from '@/Components/Manager/DictionaryTable.vue'

const props = defineProps({
  positions: { type: Array, default: () => [] },
  departments: { type: Array, default: () => [] },
})

const fields = computed(() => [
  { key: 'name', label: t('common.name'), required: true },
  {
    key: 'department_hr_id',
    label: t('common.department'),
    type: 'select',
    options: props.departments.map((d) => ({ value: d.id, label: d.name })),
  },
])
</script>
