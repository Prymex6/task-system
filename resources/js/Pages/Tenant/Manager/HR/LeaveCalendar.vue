<template>
  <ManagerLayout :title="$t('hr.leave_calendar')">
    <div class="max-w-5xl space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('hr.leave_calendar') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ $t('hr.approved_absence_for_the_chosen_month') }}</p>
        </div>
        <div class="flex items-center gap-2">
          <button @click="go(-1)" class="btn-ghost text-sm"><i class="fa-solid fa-chevron-left"></i></button>
          <span class="text-sm font-medium text-gray-700 w-40 text-center">{{ monthLabel }}</span>
          <button @click="go(1)" class="btn-ghost text-sm"><i class="fa-solid fa-chevron-right"></i></button>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="grid grid-cols-7 bg-gray-50 border-b border-gray-200">
          <div v-for="day in weekdays" :key="day" class="px-2 py-2 text-xs font-medium text-gray-500 text-center">
            {{ day }}
          </div>
        </div>
        <div class="grid grid-cols-7">
          <div
            v-for="(cell, index) in cells"
            :key="index"
            class="min-h-24 border-b border-r border-gray-100 p-1.5"
            :class="{ 'bg-gray-50': !cell.date }"
          >
            <template v-if="cell.date">
              <span class="text-xs text-gray-400">{{ cell.day }}</span>
              <div class="mt-1 space-y-0.5">
                <span
                  v-for="leave in cell.leaves"
                  :key="leave.id"
                  class="block text-[11px] px-1.5 py-0.5 rounded truncate"
                  :style="{
                    background: (leave.leave_type?.color ?? '#6366f1') + '22',
                    color: leave.leave_type?.color ?? '#4338ca',
                  }"
                  :title="`${leave.user?.name} — ${leave.leave_type?.name ?? ''}`"
                >
                  {{ leave.user?.name }}
                </span>
              </div>
            </template>
          </div>
        </div>
      </div>

      <p v-if="!leaves.length" class="text-sm text-gray-500 text-center">{{ $t('hr.no_approved_leave_this_month') }}</p>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  month: { type: String, required: true },
  leaves: { type: Array, default: () => [] },
})

const weekdays = ['Pn', 'Wt', t('common.wed'), 'Cz', 'Pt', 'So', 'Nd']

const first = computed(() => new Date(props.month + 'T00:00:00'))

const monthLabel = computed(() => first.value.toLocaleDateString('pl-PL', { month: 'long', year: 'numeric' }))

/**
 * The grid starts on a Monday, so the days before the first of the month are
 * blank and the ones after the last are dropped.
 */
const cells = computed(() => {
  const year = first.value.getFullYear()
  const month = first.value.getMonth()
  const daysInMonth = new Date(year, month + 1, 0).getDate()
  const leading = (new Date(year, month, 1).getDay() + 6) % 7

  const out = Array.from({ length: leading }, () => ({ date: null }))

  for (let day = 1; day <= daysInMonth; day++) {
    const date = new Date(Date.UTC(year, month, day)).toISOString().slice(0, 10)
    out.push({
      date,
      day,
      leaves: props.leaves.filter((l) => l.start_date <= date && l.end_date >= date),
    })
  }

  return out
})

const go = (delta) => {
  const target = new Date(first.value.getFullYear(), first.value.getMonth() + delta, 1)
  const month = new Date(Date.UTC(target.getFullYear(), target.getMonth(), 1)).toISOString().slice(0, 10)
  router.get(route('tenant.manager.hr.leave.calendar'), { month }, { preserveState: false })
}
</script>
