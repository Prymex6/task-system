<template>
  <ManagerLayout :title="project.name + ' — ' + $t('projects.team')">
    <div class="max-w-3xl mx-auto space-y-5">
      <div class="flex items-center gap-3">
        <Link :href="route('tenant.manager.projects.show', project.id)" class="text-gray-400 hover:text-gray-600">
          <i class="fa-solid fa-arrow-left"></i>
        </Link>
        <div>
          <h1 class="text-xl font-bold text-gray-900">{{ $t('projects.team_2') }}</h1>
          <p class="text-sm text-gray-500">{{ project.name }}</p>
        </div>
      </div>

      <!-- Add member -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('projects.add_member') }}</h2>
        <form @submit.prevent="addMember" class="flex items-end gap-3">
          <div class="flex-1">
            <label class="label">{{ $t('common.employee') }}</label>
            <select v-model="addForm.user_id" class="input">
              <option value="">{{ $t('projects.choose') }}</option>
              <option v-for="s in availableStaff" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div class="w-44">
            <label class="label">{{ $t('common.role') }}</label>
            <select v-model="addForm.project_role" class="input">
              <option value="viewer">Viewer</option>
              <option value="contributor">Contributor</option>
              <option value="project_manager">Project Manager</option>
            </select>
          </div>
          <button type="submit" :disabled="!addForm.user_id || addForm.processing" class="btn-primary">
            {{ $t('common.add') }}
          </button>
        </form>
      </div>

      <!-- Members list -->
      <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
        <div v-for="member in project.members" :key="member.id" class="flex items-center gap-4 p-4">
          <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
            {{ member.name?.charAt(0)?.toUpperCase() }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-medium text-gray-900">{{ member.name }}</p>
            <p class="text-sm text-gray-500">{{ member.email }}</p>
          </div>

          <!-- Role select -->
          <select
            :value="member.pivot?.project_role"
            @change="updateRole(member.id, $event.target.value)"
            class="input-sm w-40"
          >
            <option value="viewer">Viewer</option>
            <option value="contributor">Contributor</option>
            <option value="project_manager">Project Manager</option>
          </select>

          <!-- Workspace role badge -->
          <span class="text-xs text-gray-500 px-2 py-0.5 bg-gray-100 rounded-full">
            {{ member.workspace_role }}
          </span>

          <button
            @click="removeMember(member)"
            class="text-red-400 hover:text-red-600 text-sm"
            :disabled="member.id === project.created_by"
            :class="member.id === project.created_by ? 'opacity-30 cursor-not-allowed' : ''"
          >
            <i class="fa-solid fa-trash"></i>
          </button>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  project: Object,
  staff: Array,
})

const memberIds = computed(() => (props.project.members ?? []).map((m) => m.id))
const availableStaff = computed(() => props.staff.filter((s) => !memberIds.value.includes(s.id)))

const addForm = useForm({ user_id: '', project_role: 'contributor' })

const addMember = () => {
  addForm.post(route('tenant.manager.projects.members.add', props.project.id), {
    onSuccess: () => addForm.reset(),
  })
}

const updateRole = (userId, role) => {
  router.put(
    route('tenant.manager.projects.members.update', [props.project.id, userId]),
    { project_role: role },
    { preserveState: false },
  )
}

const removeMember = (member) => {
  if (!confirm(`Usunąć ${member.name} z projektu?`)) return
  router.delete(route('tenant.manager.projects.members.remove', [props.project.id, member.id]))
}
</script>
