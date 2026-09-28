<template>
  <ManagerLayout :title="$t('hr.invitations')">
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">{{ $t('hr.invitations') }}</h1>
          <p class="page-subtitle">{{ $t('hr.invite_new_people_to_the_team') }}</p>
        </div>
      </div>

      <div v-if="invitations.data.length === 0" class="card p-8 text-center text-gray-500">
        {{ $t('hr.no_invitations') }}
      </div>

      <div v-else class="card overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">E-mail</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('common.status') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('common.date') }}</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="invitation in invitations.data" :key="invitation.id">
              <td class="px-6 py-4 text-sm">{{ invitation.email }}</td>
              <td class="px-6 py-4 text-sm">{{ invitation.status }}</td>
              <td class="px-6 py-4 text-sm">{{ invitation.created_at }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

defineProps({
  invitations: Object,
})
</script>
