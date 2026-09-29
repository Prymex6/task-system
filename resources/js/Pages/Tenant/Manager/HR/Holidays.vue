<template>
  <ManagerLayout :title="$t('hr.public_holidays')">
    <div class="max-w-2xl space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('hr.public_holidays') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ $t('hr.public_and_statutory_holidays') }}</p>
        </div>
        <div class="flex items-center gap-2">
          <select v-model="selectedYear" @change="apply" class="input-sm">
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
          <button @click="showCreate = true" class="btn-primary text-sm">
            <i class="fa-solid fa-plus mr-1"></i> {{ $t('common.add') }}
          </button>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="th">{{ $t('common.name') }}</th>
              <th class="th">{{ $t('common.date') }}</th>
              <th class="th text-center">{{ $t('hr.paid') }}</th>
              <th class="th text-center">{{ $t('hr.every_year') }}</th>
              <th class="th text-right">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="h in holidays" :key="h.id" class="hover:bg-gray-50">
              <td class="td font-medium text-gray-900">{{ h.name }}</td>
              <td class="td text-gray-600">{{ formatDate(h.date) }}</td>
              <td class="td text-center">
                <i :class="h.is_paid ? 'fa-solid fa-check text-green-500' : 'fa-solid fa-xmark text-gray-300'"></i>
              </td>
              <td class="td text-center">
                <i :class="h.is_recurring ? 'fa-solid fa-rotate text-blue-500' : 'fa-solid fa-xmark text-gray-300'"></i>
              </td>
              <td class="td text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="edit(h)" class="text-xs text-indigo-600 hover:text-indigo-800">
                    {{ $t('common.edit') }}
                  </button>
                  <button @click="del(h)" class="text-xs text-red-400 hover:text-red-600">
                    {{ $t('common.delete') }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!holidays.length" class="py-16 text-center">
          <i class="fa-solid fa-calendar-xmark text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('hr.no_holidays_for') }} {{ year }}</p>
        </div>
      </div>

      <!-- Create/Edit modal -->
      <div
        v-if="showCreate || editing"
        class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center"
        @click.self="closeModal"
      >
        <div class="bg-white rounded-xl shadow-xl w-80 p-5">
          <h3 class="font-semibold text-gray-900 mb-4">
            {{ editing ? $t('common.edit_holiday') : $t('common.new_holiday') }}
          </h3>
          <form @submit.prevent="save" class="space-y-3">
            <div>
              <label class="label">{{ $t('common.name') }}</label>
              <input v-model="form.name" class="input" required />
            </div>
            <div>
              <label class="label">{{ $t('common.date') }}</label>
              <input v-model="form.date" type="date" class="input" required />
            </div>
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="form.is_paid" /> {{ $t('hr.paid') }}
            </label>
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="form.is_recurring" /> {{ $t('hr.repeats_every_year') }}
            </label>
            <div class="flex justify-end gap-2 pt-2">
              <button type="button" @click="closeModal" class="btn-ghost text-sm">{{ $t('common.cancel') }}</button>
              <button type="submit" class="btn-primary text-sm">{{ $t('common.save') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { intlLocale } from '@/format'

const props = defineProps({
  holidays: { type: Array, default: () => [] },
  year: Number,
})

const selectedYear = ref(props.year)
const showCreate = ref(false)
const editing = ref(null)
const form = reactive({ name: '', date: '', is_paid: true, is_recurring: false })
const years = computed(() => Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - 1 + i))

const apply = () =>
  router.get(
    route('tenant.manager.hr.holidays.index'),
    { year: selectedYear.value },
    { preserveState: true, replace: true },
  )
const edit = (h) => {
  editing.value = h
  Object.assign(form, { name: h.name, date: h.date, is_paid: h.is_paid, is_recurring: h.is_recurring })
}
const closeModal = () => {
  showCreate.value = false
  editing.value = null
  Object.assign(form, { name: '', date: '', is_paid: true, is_recurring: false })
}
const save = () => {
  if (editing.value)
    router.put(route('tenant.manager.hr.holidays.update', editing.value.id), form, { onSuccess: closeModal })
  else router.post(route('tenant.manager.hr.holidays.store'), form, { onSuccess: closeModal })
}
const del = (h) => {
  if (confirm(t('hr.delete_this_holiday'))) router.delete(route('tenant.manager.hr.holidays.destroy', h.id))
}
const formatDate = (d) => (d ? new Date(d).toLocaleDateString(intlLocale(), { day: 'numeric', month: 'long' }) : '—')
</script>
