<template>
  <ManagerLayout :title="project ? $t('common.edit_project') : $t('crm.new_project')">
    <div class="max-w-3xl mx-auto space-y-6">
      <div class="flex items-center gap-3">
        <Link :href="route('tenant.manager.projects.index')" class="text-gray-400 hover:text-gray-600">
          <i class="fa-solid fa-arrow-left"></i>
        </Link>
        <h1 class="text-2xl font-bold text-gray-900">
          {{ project ? $t('common.edit_project') : $t('crm.new_project') }}
        </h1>
      </div>

      <form @submit.prevent="submit" class="space-y-5">
        <!-- Podstawowe info -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
          <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ $t('platform.basics') }}</h2>

          <div>
            <label class="label">{{ $t('projects.project_name_2') }}</label>
            <input v-model="form.name" type="text" class="input" :placeholder="$t('projects.e_g_website_redesign')" />
            <p v-if="form.errors.name" class="form-error">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="label">{{ $t('common.description') }}</label>
            <textarea
              v-model="form.description"
              rows="4"
              class="input"
              :placeholder="$t('projects.what_is_this_project_about')"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="label">{{ $t('common.client') }}</label>
              <select v-model="form.client_id" class="input">
                <option value="">{{ $t('projects.no_client') }}</option>
                <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.company_name || c.name }}</option>
              </select>
            </div>
            <div>
              <label class="label">{{ $t('projects.visibility') }}</label>
              <select v-model="form.visibility" class="input">
                <option value="private">{{ $t('projects.private_just_you') }}</option>
                <option value="team">{{ $t('projects.team') }}</option>
                <option value="client">{{ $t('projects.client_and_team') }}</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Status i daty -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
          <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
            {{ $t('projects.status_and_schedule') }}
          </h2>
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="label">{{ $t('projects.status') }}</label>
              <select v-model="form.status" class="input">
                <option value="planning">{{ $t('common.planning') }}</option>
                <option value="in_progress">{{ $t('common.in_progress') }}</option>
                <option value="on_hold">{{ $t('common.on_hold') }}</option>
                <option value="completed">{{ $t('common.finished') }}</option>
                <option value="cancelled">{{ $t('common.cancelled') }}</option>
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
              <label class="label">{{ $t('projects.budget_3') }}</label>
              <input v-model="form.budget" type="number" step="0.01" min="0" class="input" placeholder="0.00" />
            </div>
            <div>
              <label class="label">{{ $t('projects.label_colour') }}</label>
              <input
                v-model="form.color"
                type="color"
                class="h-10 w-full rounded-lg border border-gray-300 cursor-pointer"
              />
            </div>
          </div>
        </div>

        <!-- Członkowie (tylko nowy projekt) -->
        <div v-if="!project" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
          <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ $t('projects.add_members') }}</h2>
          <p class="text-sm text-gray-500">{{ $t('projects.you_will_be_added_as_the') }}</p>

          <div v-for="(m, i) in form.members" :key="i" class="flex items-center gap-3">
            <select v-model="m.user_id" class="input flex-1">
              <option value="">{{ $t('projects.choose_a_person') }}</option>
              <option v-for="s in availableStaff(m.user_id)" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
            <select v-model="m.project_role" class="input w-44">
              <option value="contributor">Contributor</option>
              <option value="project_manager">Project Manager</option>
              <option value="viewer">Viewer</option>
            </select>
            <button type="button" @click="form.members.splice(i, 1)" class="text-red-400 hover:text-red-600">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>

          <button
            type="button"
            @click="form.members.push({ user_id: '', project_role: 'contributor' })"
            class="text-sm text-indigo-600 hover:underline flex items-center gap-1"
          >
            <i class="fa-solid fa-plus"></i> {{ $t('projects.add_member') }}
          </button>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3">
          <Link :href="route('tenant.manager.projects.index')" class="btn-secondary">{{ $t('common.cancel') }}</Link>
          <button type="submit" :disabled="form.processing" class="btn-primary">
            <i v-if="form.processing" class="fa-solid fa-spinner fa-spin mr-1"></i>
            {{ project ? $t('common.save_changes') : $t('projects.create_project') }}
          </button>
        </div>
      </form>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  project: { type: Object, default: null },
  clients: Array,
  staff: Array,
})

const form = useForm({
  name: props.project?.name ?? '',
  description: props.project?.description ?? '',
  client_id: props.project?.client_id ?? '',
  status: props.project?.status ?? 'planning',
  visibility: props.project?.visibility ?? 'team',
  start_date: props.project?.start_date ?? '',
  due_date: props.project?.due_date ?? '',
  budget: props.project?.budget ?? '',
  color: props.project?.color ?? '#6366f1',
  members: [],
})

const selectedUserIds = computed(() => form.members.map((m) => m.user_id).filter(Boolean))

const availableStaff = (currentId) =>
  props.staff.filter((s) => s.id === currentId || !selectedUserIds.value.includes(s.id))

const submit = () => {
  if (props.project) {
    form.put(route('tenant.manager.projects.update', props.project.id))
  } else {
    form.post(route('tenant.manager.projects.store'))
  }
}
</script>
