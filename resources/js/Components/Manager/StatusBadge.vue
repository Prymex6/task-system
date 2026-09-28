<template>
  <span
    :class="badgeClass"
    class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full font-medium whitespace-nowrap"
  >
    {{ label }}
  </span>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { computed } from 'vue'

const props = defineProps({ status: String })

const map = {
  planning: { label: t('common.planning'), cls: 'bg-blue-100 text-blue-700' },
  in_progress: { label: t('common.in_progress'), cls: 'bg-indigo-100 text-indigo-700' },
  on_hold: { label: t('common.on_hold'), cls: 'bg-yellow-100 text-yellow-700' },
  completed: { label: t('common.finished'), cls: 'bg-green-100 text-green-700' },
  cancelled: { label: t('common.cancelled'), cls: 'bg-red-100 text-red-700' },
  open: { label: t('common.open'), cls: 'bg-blue-100 text-blue-700' },
  resolved: { label: t('common.resolved'), cls: 'bg-green-100 text-green-700' },
  closed: { label: t('common.closed'), cls: 'bg-gray-100 text-gray-600' },
  draft: { label: t('common.draft'), cls: 'bg-gray-100 text-gray-600' },
  sent: { label: t('common.sent_2'), cls: 'bg-blue-100 text-blue-700' },
  paid: { label: t('common.paid'), cls: 'bg-green-100 text-green-700' },
  overdue: { label: t('common.overdue_2'), cls: 'bg-red-100 text-red-700' },
  accepted: { label: t('common.accepted_2'), cls: 'bg-green-100 text-green-700' },
  rejected: { label: t('common.rejected_2'), cls: 'bg-red-100 text-red-700' },
}

const label = computed(() => map[props.status]?.label ?? props.status ?? '—')
const badgeClass = computed(() => map[props.status]?.cls ?? 'bg-gray-100 text-gray-600')
</script>
