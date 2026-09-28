<template>
  <ManagerLayout :title="$t('common.milestones')">
    <div class="max-w-3xl space-y-5">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $t('common.milestones') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ project.name }}</p>
      </div>

      <MilestoneList :project="project" :milestones="milestones" />

      <p v-if="!canManage" class="text-xs text-gray-400">
        {{ $t('projects.only_an_administrator_or_a_manager') }}
      </p>
    </div>
  </ManagerLayout>
</template>

<script setup>
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import MilestoneList from '@/Components/Manager/MilestoneList.vue'

defineProps({
  project: { type: Object, required: true },
  milestones: { type: Array, default: () => [] },
  canManage: Boolean,
})
</script>
