<template>
  <ManagerLayout :title="$t('hr.leave_types')">
    <div class="max-w-4xl">
      <DictionaryTable
        :rows="leaveTypes"
        :fields="fields"
        route-base="tenant.manager.leave-types"
        :title="$t('hr.leave_types')"
        description="Kategorie nieobecności i przysługujące na nie limity dni"
        add-label="Nowy rodzaj"
        edit-label="Edytuj rodzaj"
        empty-label="Brak rodzajów urlopu — dodaj pierwszy"
        empty-icon="fa-solid fa-umbrella-beach"
      />
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import DictionaryTable from '@/Components/Manager/DictionaryTable.vue'

defineProps({ leaveTypes: { type: Array, default: () => [] } })

const fields = [
  { key: 'name', label: t('common.name'), required: true },
  { key: 'color', label: t('common.colour'), type: 'color', default: '#6366f1' },
  { key: 'days_per_year', label: t('hr.days_per_year_leave_empty_for'), type: 'number' },
  { key: 'requires_approval', label: t('hr.needs_approval'), type: 'checkbox', default: true },
]
</script>
