<template>
  <ManagerLayout :title="$t('hr.announcements')">
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">{{ $t('hr.announcements') }}</h1>
          <p class="page-subtitle">{{ $t('hr.announcements_for_the_team') }}</p>
        </div>
        <Link href="/hr/announcements/create" class="btn-primary">
          <i class="fa-solid fa-plus"></i> {{ $t('hr.new_announcement') }}
        </Link>
      </div>

      <div v-if="announcements.length === 0" class="card p-8 text-center text-gray-500">
        {{ $t('hr.no_announcements') }}
      </div>

      <div v-else class="space-y-4">
        <div v-for="announcement in announcements" :key="announcement.id" class="card p-4">
          <h3 class="font-semibold">{{ announcement.title }}</h3>
          <p class="text-sm text-gray-600 mt-1">{{ announcement.body }}</p>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
  announcements: {
    type: Array,
    default: () => [],
  },
})
</script>
