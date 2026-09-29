<template>
  <ManagerLayout :title="$t('portal.project_files')">
    <div class="max-w-3xl space-y-5">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.projects.show', project.id)" class="text-gray-400 hover:text-gray-600">
            <i class="fa-solid fa-arrow-left"></i>
          </Link>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $t('common.files') }}</h1>
            <p class="text-sm text-gray-500">{{ project.name }}</p>
          </div>
        </div>
        <label class="btn-primary text-sm cursor-pointer">
          <i class="fa-solid fa-upload mr-1"></i> {{ $t('projects.upload_file') }}
          <input type="file" class="hidden" @change="upload" multiple />
        </label>
      </div>

      <!-- Files grid -->
      <div v-if="files.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <div
          v-for="f in files"
          :key="f.id"
          class="bg-white rounded-xl border border-gray-200 p-4 text-center group relative"
        >
          <a :href="f.url" target="_blank" rel="noopener">
            <i :class="fileIcon(f.mime_type) + ' text-3xl mb-2'"></i>
            <p class="text-xs font-medium text-gray-900 truncate">{{ f.original_name }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ formatSize(f.size) }}</p>
            <p class="text-xs text-gray-400">{{ formatDate(f.created_at) }}</p>
          </a>
          <button
            @click="del(f)"
            class="absolute top-2 right-2 w-5 h-5 rounded-full bg-red-100 text-red-500 text-xs opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-200"
          >
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      </div>

      <div v-else class="py-16 text-center bg-white rounded-xl border border-gray-200">
        <i class="fa-solid fa-folder-open text-4xl text-gray-300 mb-3"></i>
        <p class="text-gray-500">{{ $t('projects.no_files_yet_upload_the_first') }}</p>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { intlLocale } from '@/format'

const props = defineProps({
  project: Object,
  files: { type: Array, default: () => [] },
})

const upload = (e) => {
  const formData = new FormData()
  for (const file of e.target.files) formData.append('files[]', file)
  router.post(route('tenant.manager.projects.files.store', props.project.id), formData, { forceFormData: true })
}

const del = (f) => {
  if (confirm(t('projects.delete_this_file')))
    router.delete(route('tenant.manager.projects.files.destroy', [props.project.id, f.id]))
}

const fileIcon = (mime) => {
  if (!mime) return 'fa-solid fa-file text-gray-400'
  if (mime.startsWith('image/')) return 'fa-solid fa-file-image text-blue-400'
  if (mime === 'application/pdf') return 'fa-solid fa-file-pdf text-red-400'
  if (mime.includes('word')) return 'fa-solid fa-file-word text-blue-600'
  if (mime.includes('excel') || mime.includes('spreadsheet')) return 'fa-solid fa-file-excel text-green-600'
  if (mime.includes('zip') || mime.includes('archive')) return 'fa-solid fa-file-zipper text-yellow-500'
  return 'fa-solid fa-file text-gray-400'
}
const formatSize = (b) =>
  !b ? '—' : b < 1024 ? `${b} B` : b < 1048576 ? `${(b / 1024).toFixed(1)} KB` : `${(b / 1048576).toFixed(1)} MB`
const formatDate = (d) => (d ? new Date(d).toLocaleDateString(intlLocale()) : '—')
</script>
