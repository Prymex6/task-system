<template>
  <ManagerLayout :title="$t('sprints.sprints')">
    <div class="space-y-5">
      <div class="page-header">
        <div>
          <h1 class="page-title">{{ $t('sprints.sprints') }}</h1>
          <p class="page-subtitle">{{ $t('sprints.run_the_project_in_iterations') }}</p>
        </div>
        <button @click="showCreate = true" class="btn-primary">
          <i class="fa-solid fa-plus"></i> {{ $t('sprints.new_sprint') }}
        </button>
      </div>

      <!-- Filter by project -->
      <div class="filter-bar">
        <select v-model="selectedProject" class="select input-sm w-52" @change="filterSprints">
          <option value="">{{ $t('common.all_projects') }}</option>
          <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
      </div>

      <!-- Sprints list -->
      <div class="space-y-4">
        <div v-if="!sprints.length" class="empty-state">
          <div class="empty-icon">🏃</div>
          <div class="empty-title">{{ $t('sprints.no_sprints') }}</div>
          <div class="empty-text">{{ $t('sprints.create_the_first_sprint_to_start') }}</div>
          <button @click="showCreate = true" class="btn-primary">{{ $t('sprints.create_sprint') }}</button>
        </div>

        <div v-for="sprint in sprints" :key="sprint.id" class="card">
          <div class="card-header flex items-center justify-between">
            <div class="flex items-center gap-3">
              <Link
                :href="route('tenant.manager.sprints.show', sprint.id)"
                class="font-semibold text-gray-900 hover:text-indigo-600"
              >
                {{ sprint.name }}
              </Link>
              <span :class="sprintStatusClass(sprint.status)" class="badge text-xs">{{
                sprintStatusLabel(sprint.status)
              }}</span>
            </div>
            <div class="flex items-center gap-3">
              <span class="text-xs text-gray-400"
                >{{ formatDate(sprint.start_date) }} → {{ formatDate(sprint.end_date) }}</span
              >
              <div class="flex gap-1">
                <button v-if="sprint.status === 'planning'" @click="startSprint(sprint.id)" class="btn-primary btn-sm">
                  {{ $t('sprints.start_2') }}
                </button>
                <button
                  v-if="sprint.status === 'active'"
                  @click="completeSprint(sprint.id)"
                  class="btn-secondary btn-sm"
                >
                  {{ $t('sprints.finish') }}
                </button>
                <button @click="deleteSprint(sprint.id)" class="btn-ghost btn-sm text-red-500">
                  <i class="fa-solid fa-trash-can"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="card-body">
            <div class="flex items-center gap-6 text-sm text-gray-600 mb-3">
              <span
                ><i class="fa-solid fa-list-check mr-1.5 text-gray-400"></i> {{ sprint.tasks_count ?? 0 }}
                {{ $t('common.tasks_2') }}</span
              >
              <span><i class="fa-solid fa-diagram-project mr-1.5 text-gray-400"></i> {{ sprint.project?.name }}</span>
            </div>
            <div v-if="sprint.goal" class="text-sm text-gray-500 italic">{{ sprint.goal }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Sprint Modal -->
    <div v-if="showCreate" class="modal-backdrop" @click.self="showCreate = false">
      <div class="modal-md">
        <div class="modal-header">
          <h3 class="modal-title">{{ $t('sprints.new_sprint') }}</h3>
          <button @click="showCreate = false" class="btn-ghost btn-sm"><i class="fa-solid fa-times"></i></button>
        </div>
        <form @submit.prevent="createSprint">
          <div class="modal-body space-y-4">
            <div>
              <label class="label">{{ $t('common.project') }} <span class="text-red-500">*</span></label>
              <select v-model="createForm.project_id" class="select" required>
                <option value="">{{ $t('sprints.choose_a_project') }}</option>
                <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>
            <div>
              <label class="label">{{ $t('sprints.sprint_name') }} <span class="text-red-500">*</span></label>
              <input v-model="createForm.name" type="text" class="input" placeholder="Sprint 1" required />
            </div>
            <div>
              <label class="label">{{ $t('sprints.sprint_goal') }}</label>
              <textarea
                v-model="createForm.goal"
                class="textarea"
                rows="2"
                :placeholder="$t('sprints.what_should_this_sprint_achieve')"
              ></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="label">{{ $t('common.start_date') }}</label>
                <input v-model="createForm.start_date" type="date" class="input" required />
              </div>
              <div>
                <label class="label">{{ $t('manager.end_date') }}</label>
                <input v-model="createForm.end_date" type="date" class="input" required />
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" @click="showCreate = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="createForm.processing" class="btn-primary">
              {{ $t('sprints.create_sprint') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  sprints: { type: Array, default: () => [] },
  projects: { type: Array, default: () => [] },
})

const showCreate = ref(false)
const selectedProject = ref('')

const filterSprints = () => {
  router.get(
    route('tenant.manager.sprints.index'),
    { project_id: selectedProject.value },
    { preserveState: true, replace: true },
  )
}

const createForm = useForm({
  project_id: '',
  name: '',
  goal: '',
  start_date: new Date().toISOString().slice(0, 10),
  end_date: '',
})

const createSprint = () => {
  createForm.post(route('tenant.manager.sprints.store'), {
    onSuccess: () => {
      showCreate.value = false
      createForm.reset()
    },
  })
}

const startSprint = (id) => useForm({}).post(route('tenant.manager.sprints.start', id))
const completeSprint = (id) => useForm({}).post(route('tenant.manager.sprints.complete', id))

const deleteSprint = (id) => {
  if (!confirm(t('sprints.delete_this_sprint'))) return
  useForm({}).delete(route('tenant.manager.sprints.destroy', id))
}

const sprintStatusLabel = (s) =>
  ({ planning: 'Planowanie', active: 'Aktywny', completed: t('common.finished') })[s] ?? s
const sprintStatusClass = (s) =>
  ({ planning: 'badge-blue', active: 'badge-green', completed: 'badge-gray' })[s] ?? 'badge-gray'
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')
</script>
