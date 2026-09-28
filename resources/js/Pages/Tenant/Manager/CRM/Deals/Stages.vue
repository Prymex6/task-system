<template>
  <ManagerLayout :title="$t('crm.pipeline_stages')">
    <div class="max-w-2xl mx-auto space-y-6">
      <div class="page-header">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.deals.pipeline')" class="btn-ghost btn-sm"
            ><i class="fa-solid fa-arrow-left"></i
          ></Link>
          <h1 class="page-title">{{ $t('crm.manage_the_pipeline_stages') }}</h1>
        </div>
      </div>

      <!-- Existing stages -->
      <div class="card">
        <div class="card-header">
          <h2 class="section-title mb-0">{{ $t('crm.stages') }}</h2>
        </div>
        <div class="divide-y divide-gray-100">
          <div v-if="!stages.length" class="px-6 py-6 text-center text-sm text-gray-400">{{ $t('crm.no_stages') }}</div>
          <div v-for="stage in stages" :key="stage.id" class="px-6 py-3 flex items-center gap-4">
            <div
              class="w-3 h-3 rounded-full flex-shrink-0"
              :style="{ backgroundColor: stage.color || '#6366f1' }"
            ></div>
            <div class="flex-1">
              <input
                v-if="editing === stage.id"
                v-model="editForm.name"
                type="text"
                class="input input-sm"
                @keydown.enter="saveEdit(stage.id)"
                @keydown.escape="editing = null"
              />
              <span v-else class="text-sm font-medium text-gray-800">{{ stage.name }}</span>
            </div>
            <div class="text-xs text-gray-400">{{ stage.deals_count ?? 0 }} {{ $t('crm.deals') }}</div>
            <div class="flex gap-1">
              <button v-if="editing !== stage.id" @click="startEdit(stage)" class="btn-ghost btn-sm">
                <i class="fa-solid fa-pen text-xs"></i>
              </button>
              <button v-else @click="saveEdit(stage.id)" class="btn-primary btn-sm">
                <i class="fa-solid fa-check text-xs"></i>
              </button>
              <button @click="deleteStage(stage.id)" class="btn-ghost btn-sm text-red-500">
                <i class="fa-solid fa-trash-can text-xs"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Add stage -->
      <div class="card p-5">
        <h3 class="section-title">{{ $t('crm.add_stage') }}</h3>
        <form @submit.prevent="addStage" class="flex gap-3">
          <input v-model="addForm.name" type="text" class="input flex-1" :placeholder="$t('crm.stage_name')" required />
          <input
            v-model="addForm.color"
            type="color"
            class="w-10 h-10 rounded border border-gray-300 cursor-pointer"
            value="#6366f1"
          />
          <button type="submit" :disabled="addForm.processing" class="btn-primary">
            <i class="fa-solid fa-plus"></i> {{ $t('common.add') }}
          </button>
        </form>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref, reactive } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  stages: { type: Array, default: () => [] },
})

const editing = ref(null)
const editForm = reactive({ name: '' })

const startEdit = (stage) => {
  editing.value = stage.id
  editForm.name = stage.name
}

const saveEdit = (id) => {
  useForm({ name: editForm.name }).patch(route('tenant.manager.deals.stages.update', id), {
    onSuccess: () => {
      editing.value = null
    },
  })
}

const deleteStage = (id) => {
  if (!confirm(t('crm.delete_this_stage_its_deals_will'))) return
  useForm({}).delete(route('tenant.manager.deals.stages.destroy', id))
}

const addForm = useForm({ name: '', color: '#6366f1' })
const addStage = () => {
  addForm.post(route('tenant.manager.deals.stages.store'), {
    onSuccess: () => addForm.reset(),
  })
}
</script>
