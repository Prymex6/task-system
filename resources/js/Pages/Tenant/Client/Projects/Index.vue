<template>
  <ClientLayout :title="$t('common.my_projects')">
    <div class="space-y-5">
      <h1 class="text-xl font-bold text-gray-900">{{ $t('common.my_projects') }}</h1>

      <div v-if="!projects.data?.length" class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <div class="text-4xl mb-3">📁</div>
        <div class="font-semibold text-gray-600">{{ $t('common.no_projects') }}</div>
        <p class="text-sm text-gray-400 mt-1">{{ $t('portal.get_in_touch_and_we_will') }}</p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
          v-for="p in projects.data"
          :key="p.id"
          class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-shadow"
        >
          <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-2">
              <div class="w-2 h-2 rounded-full" :style="{ backgroundColor: p.color || '#6366f1' }"></div>
              <Link
                :href="route('tenant.portal.projects.show', p.id)"
                class="font-semibold text-gray-900 hover:text-indigo-600"
              >
                {{ p.name }}
              </Link>
            </div>
            <span class="badge badge-indigo text-xs">{{ statusLabel(p.status) }}</span>
          </div>
          <p v-if="p.description" class="text-sm text-gray-500 mb-3 line-clamp-2">{{ p.description }}</p>
          <div class="flex items-center gap-2 mb-3">
            <div class="h-1.5 flex-1 bg-gray-200 rounded-full overflow-hidden">
              <div class="h-1.5 bg-indigo-500 rounded-full" :style="{ width: p.progress + '%' }"></div>
            </div>
            <span class="text-xs text-gray-500 font-medium">{{ p.progress }}%</span>
          </div>
          <div class="flex gap-4 text-xs text-gray-400">
            <span v-if="p.due_date"><i class="fa-solid fa-calendar mr-1"></i>{{ formatDate(p.due_date) }}</span>
            <span><i class="fa-solid fa-list-check mr-1"></i>{{ p.tasks_count ?? 0 }} {{ $t('common.tasks_2') }}</span>
          </div>
        </div>
      </div>

      <Pagination :links="projects.links" />
    </div>
  </ClientLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { Link } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { intlLocale } from '@/format'

const props = defineProps({ projects: Object })

const statusLabel = (s) =>
  ({ planning: 'Planowanie', in_progress: 'W trakcie', on_hold: 'Wstrzymany', completed: t('common.completed_3') })[
    s
  ] ?? s
const formatDate = (d) => (d ? new Date(d).toLocaleDateString(intlLocale()) : '—')
</script>
