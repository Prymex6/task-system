<template>
  <ClientLayout :title="$t('portal.project_files')">
    <div class="space-y-5">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('common.files') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $t('portal.files_shared_on_your_projects') }}</p>
      </div>

      <!-- Filter by project -->
      <div class="bg-white rounded-xl border border-gray-200 p-4 flex gap-3">
        <select v-model="selectedProject" @change="filter" class="input-sm">
          <option value="">{{ $t('common.all_projects') }}</option>
          <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
      </div>

      <!-- Files grid -->
      <div v-if="files.data?.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <a
          v-for="file in files.data"
          :key="file.id"
          :href="file.url"
          target="_blank"
          rel="noopener"
          class="bg-white rounded-xl border border-gray-200 p-4 hover:border-indigo-300 hover:shadow-sm transition-all text-center group"
        >
          <i :class="fileIcon(file.mime_type) + ' text-3xl mb-2 group-hover:scale-110 transition-transform'"></i>
          <p class="text-xs font-medium text-gray-900 truncate">{{ file.original_name }}</p>
          <p class="text-xs text-gray-400 mt-0.5">{{ formatSize(file.size) }}</p>
          <p class="text-xs text-gray-400">{{ file.project?.name }}</p>
        </a>
      </div>

      <div v-else class="py-16 text-center bg-white rounded-xl border border-gray-200">
        <i class="fa-solid fa-folder-open text-4xl text-gray-300 mb-3"></i>
        <p class="text-gray-500">{{ $t('portal.no_files') }}</p>
      </div>

      <Pagination :links="files.links" />
    </div>
  </ClientLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  files: Object,
  projects: { type: Array, default: () => [] },
  filters: Object,
})

const selectedProject = ref(props.filters?.project_id ?? '')

const filter = () => {
  router.get(
    route('tenant.portal.files'),
    { project_id: selectedProject.value },
    { preserveState: true, replace: true },
  )
}

const fileIcon = (mime) => {
  if (!mime) return 'fa-solid fa-file text-gray-400'
  if (mime.startsWith('image/')) return 'fa-solid fa-file-image text-blue-400'
  if (mime === 'application/pdf') return 'fa-solid fa-file-pdf text-red-400'
  if (mime.includes('word') || mime.includes('document')) return 'fa-solid fa-file-word text-blue-600'
  if (mime.includes('excel') || mime.includes('spreadsheet')) return 'fa-solid fa-file-excel text-green-600'
  if (mime.startsWith('video/')) return 'fa-solid fa-file-video text-purple-400'
  if (mime.startsWith('audio/')) return 'fa-solid fa-file-audio text-orange-400'
  if (mime.includes('zip') || mime.includes('archive')) return 'fa-solid fa-file-zipper text-yellow-500'
  return 'fa-solid fa-file text-gray-400'
}

const formatSize = (bytes) => {
  if (!bytes) return '—'
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}
</script>
