<template>
  <ManagerLayout :title="$t('common.projects')">
    <div class="space-y-5">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('common.projects') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">
            {{ projects.total }} {{ $t('projects.project') }}{{ projects.total === 1 ? '' : $t('common.s') }}
          </p>
        </div>
        <Link
          :href="route('tenant.manager.projects.create')"
          class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
        >
          <i class="fa-solid fa-plus"></i> {{ $t('crm.new_project') }}
        </Link>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap gap-3">
        <input
          v-model="filters.search"
          @input="applyFilters"
          :placeholder="$t('projects.search_projects')"
          class="input-sm flex-1 min-w-[200px]"
        />
        <select v-model="filters.status" @change="applyFilters" class="input-sm">
          <option value="">{{ $t('common.all_statuses') }}</option>
          <option value="planning">{{ $t('common.planning') }}</option>
          <option value="in_progress">{{ $t('common.in_progress') }}</option>
          <option value="on_hold">{{ $t('common.on_hold') }}</option>
          <option value="completed">{{ $t('common.finished') }}</option>
          <option value="cancelled">{{ $t('common.cancelled') }}</option>
        </select>
        <select v-model="filters.client_id" @change="applyFilters" class="input-sm">
          <option value="">{{ $t('finance.all_clients') }}</option>
          <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.company_name || c.name }}</option>
        </select>
        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
          <input type="checkbox" v-model="filters.archived" @change="applyFilters" class="rounded border-gray-300" />
          {{ $t('projects.archived') }}
        </label>
      </div>

      <!-- Projects grid -->
      <div v-if="projects.data.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <ProjectCard v-for="project in projects.data" :key="project.id" :project="project" />
      </div>

      <div v-else class="bg-white rounded-xl border border-gray-200 py-16 text-center">
        <i class="fa-solid fa-folder-open text-4xl text-gray-300 mb-3"></i>
        <p class="text-gray-500 font-medium">{{ $t('common.no_projects') }}</p>
        <p class="text-sm text-gray-400 mt-1">{{ $t('projects.create_your_first_project_to_get') }}</p>
        <Link
          :href="route('tenant.manager.projects.create')"
          class="mt-4 inline-flex items-center gap-2 text-sm text-indigo-600 hover:underline"
        >
          <i class="fa-solid fa-plus"></i> {{ $t('projects.create_project') }}
        </Link>
      </div>

      <!-- Pagination -->
      <Pagination :links="projects.links" />
    </div>
  </ManagerLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import ProjectCard from '@/Components/Manager/ProjectCard.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  projects: Object,
  clients: Array,
  filters: Object,
})

const filters = reactive({ ...props.filters })

const applyFilters = () => {
  router.get(route('tenant.manager.projects.index'), filters, { preserveState: true, replace: true })
}
</script>
