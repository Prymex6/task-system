<template>
  <ManagerLayout :title="task ? 'Edytuj zadanie' : 'Nowe zadanie'">
    <div class="max-w-3xl mx-auto space-y-5">
      <div class="flex items-center gap-3">
        <Link :href="route('tenant.manager.tasks.index')" class="text-gray-400 hover:text-gray-600">
          <i class="fa-solid fa-arrow-left"></i>
        </Link>
        <h1 class="text-2xl font-bold text-gray-900">{{ task ? 'Edytuj zadanie' : 'Nowe zadanie' }}</h1>
      </div>

      <form @submit.prevent="submit" class="space-y-5">
        <!-- Podstawowe -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
          <div>
            <label class="label">{{ $t('tasks.task_title') }}</label>
            <input v-model="form.title" type="text" class="input" :placeholder="$t('tasks.what_needs_doing')" />
            <p v-if="form.errors.title" class="form-error">{{ form.errors.title }}</p>
          </div>
          <div>
            <label class="label">{{ $t('common.description') }}</label>
            <textarea
              v-model="form.description"
              rows="5"
              class="input"
              :placeholder="$t('tasks.a_fuller_description_of_the_task')"
            ></textarea>
          </div>
        </div>

        <!-- Projekt i ustawienia -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="label">{{ $t('tasks.project') }}</label>
              <select v-model="form.project_id" class="input" :disabled="!!task">
                <option value="">{{ $t('tasks.choose_a_project_2') }}</option>
                <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
              <p v-if="form.errors.project_id" class="form-error">{{ form.errors.project_id }}</p>
            </div>
            <div>
              <label class="label">{{ $t('common.status') }}</label>
              <select v-model="form.status_id" class="input">
                <option value="">{{ $t('tasks.default') }}</option>
                <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="label">{{ $t('tasks.priority') }}</label>
              <select v-model="form.priority" class="input">
                <option value="low">{{ $t('common.low') }}</option>
                <option value="medium">{{ $t('common.medium') }}</option>
                <option value="high">{{ $t('common.high') }}</option>
                <option value="urgent">{{ $t('common.urgent') }}</option>
              </select>
            </div>
            <div>
              <label class="label">{{ $t('common.start_date') }}</label>
              <input v-model="form.start_date" type="date" class="input" />
            </div>
            <div>
              <label class="label">{{ $t('common.due') }}</label>
              <input v-model="form.due_date" type="date" class="input" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="label">{{ $t('tasks.estimate_h') }}</label>
              <input
                v-model="form.estimated_hours"
                type="number"
                step="0.25"
                min="0"
                class="input"
                :placeholder="$t('common.e_g_4_5')"
              />
            </div>
            <div>
              <label class="label">Story Points</label>
              <input
                v-model="form.story_points"
                type="number"
                min="0"
                max="100"
                class="input"
                :placeholder="$t('common.e_g_3')"
              />
            </div>
          </div>
        </div>

        <!-- Przypisane osoby -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
          <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('tasks.assignees') }}</h2>
          <div class="flex flex-wrap gap-2">
            <label
              v-for="s in staff"
              :key="s.id"
              :class="
                form.assignees.includes(s.id) ? 'ring-2 ring-indigo-500 bg-indigo-50' : 'bg-gray-50 hover:bg-gray-100'
              "
              class="flex items-center gap-2 px-3 py-2 rounded-lg cursor-pointer transition-all border border-gray-200"
            >
              <input type="checkbox" :value="s.id" v-model="form.assignees" class="sr-only" />
              <div
                class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold"
              >
                {{ s.name?.charAt(0)?.toUpperCase() }}
              </div>
              <span class="text-sm text-gray-700">{{ s.name }}</span>
            </label>
          </div>
        </div>

        <!-- Etykiety -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
          <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('tasks.labels') }}</h2>
          <div class="flex flex-wrap gap-2">
            <label
              v-for="label in labels"
              :key="label.id"
              :class="form.labels.includes(label.id) ? 'ring-2' : 'opacity-70 hover:opacity-100'"
              class="px-3 py-1 rounded-full cursor-pointer transition-all text-xs font-medium"
              :style="{ background: label.color + '22', color: label.color, borderColor: label.color }"
            >
              <input type="checkbox" :value="label.id" v-model="form.labels" class="sr-only" />
              {{ label.name }}
            </label>
          </div>
        </div>

        <div class="flex justify-end gap-3">
          <Link :href="route('tenant.manager.tasks.index')" class="btn-secondary">{{ $t('common.cancel') }}</Link>
          <button type="submit" :disabled="form.processing" class="btn-primary">
            <i v-if="form.processing" class="fa-solid fa-spinner fa-spin mr-1"></i>
            {{ task ? 'Zapisz zmiany' : $t('common.create_task') }}
          </button>
        </div>
      </form>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  task: { type: Object, default: null },
  projects: Array,
  statuses: Array,
  labels: Array,
  staff: Array,
  defaultProjectId: { type: Number, default: null },
})

const form = useForm({
  title: props.task?.title ?? '',
  description: props.task?.description ?? '',
  project_id: props.task?.project_id ?? props.defaultProjectId ?? '',
  status_id: props.task?.status_id ?? '',
  priority: props.task?.priority ?? 'medium',
  due_date: props.task?.due_date ?? '',
  start_date: props.task?.start_date ?? '',
  estimated_hours: props.task?.estimated_hours ?? '',
  story_points: props.task?.story_points ?? '',
  assignees: props.task?.assignees?.map((a) => a.id) ?? [],
  labels: props.task?.labels?.map((l) => l.id) ?? [],
})

const submit = () => {
  if (props.task) {
    form.put(route('tenant.manager.tasks.update', props.task.id))
  } else {
    form.post(route('tenant.manager.tasks.store'))
  }
}
</script>
