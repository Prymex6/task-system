<template>
  <ManagerLayout :title="$t('tasks.task_templates')">
    <div class="space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('tasks.task_templates') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ $t('tasks.reusable_sets_of_tasks') }}</p>
        </div>
        <button @click="showCreate = true" class="btn-primary text-sm">
          <i class="fa-solid fa-plus mr-1"></i> {{ $t('projects.new_template') }}
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="t in templates.data"
          :key="t.id"
          class="bg-white rounded-xl border border-gray-200 p-5 hover:border-indigo-300 hover:shadow-sm transition-all"
        >
          <div class="flex items-start justify-between gap-2 mb-3">
            <h3 class="font-semibold text-gray-900">{{ t.name }}</h3>
            <span class="text-xs text-gray-400 flex-shrink-0">{{ t.tasks_count ?? 0 }} {{ $t('common.tasks_2') }}</span>
          </div>
          <p v-if="t.description" class="text-xs text-gray-500 mb-3 line-clamp-2">{{ t.description }}</p>
          <div class="flex items-center justify-between">
            <button @click="del(t)" class="text-xs text-red-400 hover:text-red-600">{{ $t('common.delete') }}</button>
            <button @click="edit(t)" class="text-xs text-indigo-600 hover:text-indigo-800">
              {{ $t('common.edit') }}
            </button>
          </div>
        </div>
      </div>

      <div v-if="!templates.data?.length" class="py-16 text-center bg-white rounded-xl border border-gray-200">
        <i class="fa-solid fa-layer-group text-4xl text-gray-300 mb-3"></i>
        <p class="text-gray-500">{{ $t('tasks.no_templates_yet_create_the_first') }}</p>
      </div>

      <Pagination :links="templates.links" />

      <!-- Create modal -->
      <div
        v-if="showCreate || editing"
        class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center"
        @click.self="closeModal"
      >
        <div class="bg-white rounded-xl shadow-xl w-[500px] p-5 max-h-[90vh] overflow-y-auto">
          <h3 class="font-semibold text-gray-900 mb-4">
            {{ editing ? 'Edytuj szablon' : $t('common.new_task_template') }}
          </h3>
          <form @submit.prevent="save" class="space-y-4">
            <div>
              <label class="label">{{ $t('tasks.template_name') }}</label>
              <input v-model="form.name" class="input" required />
            </div>
            <div>
              <label class="label">{{ $t('common.description') }}</label>
              <textarea v-model="form.description" rows="2" class="input"></textarea>
            </div>
            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="label mb-0">{{ $t('common.tasks') }}</label>
                <button type="button" @click="addTask" class="text-xs text-indigo-600 hover:text-indigo-800">
                  <i class="fa-solid fa-plus mr-0.5"></i> {{ $t('common.add') }}
                </button>
              </div>
              <div class="space-y-2">
                <div v-for="(task, i) in form.tasks" :key="i" class="flex items-center gap-2">
                  <input
                    v-model="task.title"
                    class="input flex-1 text-sm"
                    :placeholder="$t('projects.task_title')"
                    required
                  />
                  <select v-model="task.priority" class="input-sm w-28">
                    <option value="low">{{ $t('common.low') }}</option>
                    <option value="medium">{{ $t('common.medium') }}</option>
                    <option value="high">{{ $t('common.high') }}</option>
                  </select>
                  <button type="button" @click="form.tasks.splice(i, 1)" class="text-red-400 hover:text-red-600">
                    <i class="fa-solid fa-xmark"></i>
                  </button>
                </div>
              </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
              <button type="button" @click="closeModal" class="btn-ghost text-sm">{{ $t('common.cancel') }}</button>
              <button type="submit" class="btn-primary text-sm">{{ $t('settings.save_template') }}</button>
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

import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({ templates: Object })

const showCreate = ref(false)
const editing = ref(null)
const form = reactive({ name: '', description: '', tasks: [] })

const addTask = () => form.tasks.push({ title: '', priority: 'medium' })
const edit = (t) => {
  editing.value = t
  Object.assign(form, { name: t.name, description: t.description ?? '', tasks: [] })
}
const closeModal = () => {
  showCreate.value = false
  editing.value = null
  Object.assign(form, { name: '', description: '', tasks: [] })
}

const save = () => {
  if (editing.value)
    router.put(route('tenant.manager.task-templates.update', editing.value.id), form, { onSuccess: closeModal })
  else router.post(route('tenant.manager.task-templates.store'), form, { onSuccess: closeModal })
}
const del = (t) => {
  if (confirm(t('tasks.delete_this_template'))) router.delete(route('tenant.manager.task-templates.destroy', t.id))
}
</script>
