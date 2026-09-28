<template>
  <ManagerLayout :title="$t('projects.project_templates')">
    <div class="max-w-4xl space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('projects.project_templates') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ $t('projects.ready_made_sets_of_tasks_a') }}</p>
        </div>
        <button class="btn-primary" @click="creating = true">{{ $t('projects.new_template') }}</button>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <div v-for="t in templates" :key="t.id" class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="flex items-start justify-between">
            <div>
              <h2 class="font-semibold text-gray-900">{{ t.name }}</h2>
              <p class="text-xs text-gray-500 mt-0.5">{{ t.tasks_count }} {{ $t('common.tasks_2') }}</p>
            </div>
            <button class="text-xs text-red-400 hover:text-red-600" @click="remove(t)">
              {{ $t('common.delete') }}
            </button>
          </div>

          <p v-if="t.description" class="text-sm text-gray-600 mt-2">{{ t.description }}</p>

          <ul class="mt-3 space-y-1">
            <li
              v-for="task in (t.tasks ?? []).slice(0, 4)"
              :key="task.id"
              class="text-xs text-gray-500 flex items-center gap-1.5"
            >
              <i class="fa-regular fa-square text-gray-300"></i>{{ task.title }}
            </li>
            <li v-if="(t.tasks?.length ?? 0) > 4" class="text-xs text-gray-400">
              {{ $t('projects.and') }} {{ t.tasks.length - 4 }} {{ $t('projects.more') }}
            </li>
          </ul>

          <button class="btn-primary w-full mt-4" @click="openUse(t)">{{ $t('projects.create_project') }}</button>
        </div>
      </div>

      <div v-if="!templates.length" class="bg-white rounded-xl border border-gray-200 py-16 text-center">
        <i class="fa-solid fa-layer-group text-4xl text-gray-300 mb-3"></i>
        <p class="text-gray-500">{{ $t('projects.no_templates_yet_add_the_first') }}</p>
      </div>
    </div>

    <div
      v-if="creating"
      class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50"
      @click.self="creating = false"
    >
      <form class="bg-white rounded-xl w-full max-w-lg p-5 space-y-4" @submit.prevent="store">
        <h2 class="text-lg font-semibold text-gray-900">{{ $t('projects.new_template') }}</h2>

        <FormField :label="$t('common.name')" :error="createForm.errors.name" required>
          <input v-model="createForm.name" type="text" class="input" required />
        </FormField>
        <FormField :label="$t('common.description')" :error="createForm.errors.description">
          <textarea v-model="createForm.description" rows="2" class="input"></textarea>
        </FormField>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('common.tasks') }}</label>
          <div v-for="(task, i) in createForm.tasks" :key="i" class="flex items-center gap-2 mb-2">
            <input v-model="task.title" type="text" class="input flex-1" :placeholder="$t('projects.task_title')" />
            <select v-model="task.priority" class="input-sm w-28">
              <option value="low">{{ $t('common.low') }}</option>
              <option value="medium">{{ $t('common.medium') }}</option>
              <option value="high">{{ $t('common.high') }}</option>
              <option value="urgent">{{ $t('common.urgent') }}</option>
            </select>
            <button
              type="button"
              class="text-red-400 hover:text-red-600 text-sm"
              @click="createForm.tasks.splice(i, 1)"
            >
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
          <button type="button" class="text-xs text-indigo-600 hover:text-indigo-800" @click="addTask">
            {{ $t('projects.add_task') }}
          </button>
        </div>

        <div class="flex items-center gap-3 pt-1">
          <button type="submit" class="btn-primary" :disabled="createForm.processing">{{ $t('common.save') }}</button>
          <button type="button" class="btn-ghost" @click="creating = false">{{ $t('common.cancel') }}</button>
        </div>
      </form>
    </div>

    <div
      v-if="using"
      class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50"
      @click.self="using = null"
    >
      <form class="bg-white rounded-xl w-full max-w-md p-5 space-y-4" @submit.prevent="createProject">
        <h2 class="text-lg font-semibold text-gray-900">{{ $t('projects.project_from_template') }}{{ using.name }}”</h2>

        <FormField :label="$t('projects.project_name')" :error="useForm_.errors.name" required>
          <input v-model="useForm_.name" type="text" class="input" required />
        </FormField>
        <FormField :label="$t('projects.start_date')" :error="useForm_.errors.start_date">
          <input v-model="useForm_.start_date" type="date" class="input" />
        </FormField>

        <div class="flex items-center gap-3 pt-1">
          <button type="submit" class="btn-primary" :disabled="useForm_.processing">{{ $t('projects.create') }}</button>
          <button type="button" class="btn-ghost" @click="using = null">{{ $t('common.cancel') }}</button>
        </div>
      </form>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import FormField from '@/Components/Manager/FormField.vue'

defineProps({ templates: { type: Array, default: () => [] } })

const creating = ref(false)
const using = ref(null)

const createForm = useForm({
  name: '',
  description: '',
  tasks: [{ title: '', priority: 'medium' }],
})

const useForm_ = useForm({ name: '', start_date: '' })

const addTask = () => createForm.tasks.push({ title: '', priority: 'medium' })

const store = () => {
  createForm
    .transform((data) => ({
      ...data,
      tasks: data.tasks.filter((t) => t.title.trim() !== ''),
    }))
    .post(route('tenant.manager.project-templates.store'), {
      onSuccess: () => {
        creating.value = false
        createForm.reset()
      },
    })
}

const openUse = (template) => {
  useForm_.reset()
  useForm_.name = template.name
  using.value = template
}

const createProject = () => useForm_.post(route('tenant.manager.project-templates.use', using.value.id))

const remove = (template) => {
  if (confirm(t('projects.delete_template') + template.name + '?')) {
    router.delete(route('tenant.manager.project-templates.destroy', template.id))
  }
}
</script>
