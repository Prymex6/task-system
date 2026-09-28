<template>
  <Link
    :href="route('tenant.manager.projects.show', project.id)"
    class="bg-white rounded-xl border border-gray-200 hover:border-indigo-300 hover:shadow-md transition-all block"
  >
    <div class="p-5 space-y-3">
      <!-- Header -->
      <div class="flex items-start justify-between gap-2">
        <div class="flex items-center gap-2 min-w-0">
          <span
            v-if="project.color"
            class="w-3 h-3 rounded-full flex-shrink-0"
            :style="{ background: project.color }"
          ></span>
          <h3 class="font-semibold text-gray-900 text-sm leading-snug truncate">{{ project.name }}</h3>
        </div>
        <StatusBadge :status="project.status" class="flex-shrink-0" />
      </div>

      <!-- Client -->
      <p v-if="project.client" class="text-xs text-gray-500 flex items-center gap-1">
        <i class="fa-solid fa-building"></i>
        {{ project.client.company_name || project.client.name }}
      </p>

      <!-- Progress -->
      <div>
        <div class="flex justify-between text-xs text-gray-500 mb-1">
          <span>{{ $t('common.progress') }}</span>
          <span>{{ progress }}%</span>
        </div>
        <div class="h-1.5 bg-gray-100 rounded-full">
          <div class="h-1.5 bg-indigo-500 rounded-full transition-all" :style="{ width: progress + '%' }"></div>
        </div>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-between pt-1">
        <!-- Members -->
        <div class="flex items-center">
          <div
            v-for="(m, i) in (project.members ?? []).slice(0, 4)"
            :key="m.id"
            class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold ring-2 ring-white"
            :style="{ marginLeft: i > 0 ? '-4px' : '0' }"
            :title="m.name"
          >
            {{ m.name?.charAt(0)?.toUpperCase() }}
          </div>
          <span v-if="(project.members?.length ?? 0) > 4" class="ml-1 text-xs text-gray-400"
            >+{{ project.members.length - 4 }}</span
          >
        </div>

        <!-- Due date -->
        <div
          v-if="project.due_date"
          class="text-xs flex items-center gap-1"
          :class="isOverdue ? 'text-red-500' : 'text-gray-400'"
        >
          <i class="fa-regular fa-calendar"></i>
          {{ project.due_date }}
        </div>
      </div>
    </div>
  </Link>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import StatusBadge from '@/Components/Manager/StatusBadge.vue'

const props = defineProps({ project: Object })

const progress = computed(() => {
  const tasks = props.project.tasks ?? []
  if (!tasks.length) return 0
  const done = tasks.filter((t) => t.is_completed).length
  return Math.round((done / tasks.length) * 100)
})

const isOverdue = computed(() => {
  if (!props.project.due_date) return false
  return new Date(props.project.due_date) < new Date() && props.project.status !== 'completed'
})
</script>
