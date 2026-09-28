<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between">
      <h3 class="text-sm font-semibold text-gray-700">{{ $t('common.milestones') }}</h3>
      <button @click="showForm = !showForm" class="text-sm text-indigo-600 hover:underline">
        <i class="fa-solid fa-plus"></i> {{ $t('common.add') }}
      </button>
    </div>

    <!-- Add form -->
    <form v-if="showForm" @submit.prevent="submit" class="bg-white rounded-xl border border-indigo-200 p-4 space-y-3">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="label">{{ $t('common.name_2') }}</label>
          <input v-model="form.name" class="input" :placeholder="$t('common.e_g_mvp')" />
        </div>
        <div>
          <label class="label">{{ $t('common.due') }}</label>
          <input v-model="form.due_date" type="date" class="input" />
        </div>
      </div>
      <div class="flex justify-end gap-2">
        <button type="button" @click="showForm = false" class="btn-secondary text-sm">{{ $t('common.cancel') }}</button>
        <button type="submit" :disabled="form.processing" class="btn-primary text-sm">{{ $t('common.add') }}</button>
      </div>
    </form>

    <!-- List -->
    <div v-if="milestones?.length" class="space-y-2">
      <div
        v-for="m in milestones"
        :key="m.id"
        class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-4"
      >
        <button
          @click="toggleComplete(m)"
          :class="m.is_completed ? 'text-green-500' : 'text-gray-300 hover:text-green-400'"
          class="text-lg flex-shrink-0"
        >
          <i :class="m.is_completed ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle'"></i>
        </button>
        <div class="flex-1 min-w-0">
          <p :class="m.is_completed ? 'line-through text-gray-400' : 'text-gray-900'" class="text-sm font-medium">
            {{ m.name }}
          </p>
          <p v-if="m.due_date" class="text-xs text-gray-400 mt-0.5">
            <i class="fa-regular fa-calendar mr-1"></i>{{ m.due_date }}
          </p>
        </div>
        <button @click="remove(m)" class="text-red-400 hover:text-red-600 text-sm">
          <i class="fa-solid fa-trash"></i>
        </button>
      </div>
    </div>
    <div v-else class="bg-white rounded-xl border border-gray-200 py-10 text-center text-sm text-gray-400">
      {{ $t('common.no_milestones') }}
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'

const props = defineProps({
  project: Object,
  milestones: Array,
})

const showForm = ref(false)
const form = useForm({ name: '', due_date: '', description: '' })

const submit = () => {
  form.post(route('tenant.manager.projects.milestones.store', props.project.id), {
    onSuccess: () => {
      form.reset()
      showForm.value = false
    },
  })
}

const toggleComplete = (m) => {
  router.put(
    route('tenant.manager.projects.milestones.update', [props.project.id, m.id]),
    { ...m, is_completed: !m.is_completed },
    { preserveScroll: true },
  )
}

const remove = (m) => {
  if (!confirm(t('common.delete_this_milestone'))) return
  router.delete(route('tenant.manager.projects.milestones.destroy', [props.project.id, m.id]), { preserveScroll: true })
}
</script>
