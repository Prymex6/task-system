<template>
  <ManagerLayout :title="$t('settings.tags')">
    <div class="max-w-2xl space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $t('settings.tags') }}</h1>
          <p class="text-sm text-gray-500 mt-0.5">{{ $t('settings.labels_for_categorising_tasks') }}</p>
        </div>
        <button @click="showCreate = true" class="btn-primary text-sm">
          <i class="fa-solid fa-plus mr-1"></i> {{ $t('settings.new_tag') }}
        </button>
      </div>

      <!-- Tags grid -->
      <div class="flex flex-wrap gap-2">
        <div
          v-for="tag in tags"
          :key="tag.id"
          class="group flex items-center gap-2 px-3 py-1.5 rounded-full border"
          :style="{ borderColor: tag.color, background: tag.color + '18' }"
        >
          <span class="w-2 h-2 rounded-full" :style="{ background: tag.color }"></span>
          <span class="text-sm font-medium" :style="{ color: tag.color }">{{ tag.name }}</span>
          <span class="text-xs text-gray-400">{{ tag.tasks_count ?? 0 }}</span>
          <button
            @click="edit(tag)"
            class="text-gray-400 hover:text-gray-700 opacity-0 group-hover:opacity-100 transition-opacity"
          >
            <i class="fa-solid fa-pen text-xs"></i>
          </button>
          <button
            @click="del(tag)"
            class="text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity"
          >
            <i class="fa-solid fa-xmark text-xs"></i>
          </button>
        </div>
      </div>

      <div v-if="!tags.length" class="py-16 text-center bg-white rounded-xl border border-gray-200">
        <i class="fa-solid fa-tags text-4xl text-gray-300 mb-3"></i>
        <p class="text-gray-500">{{ $t('settings.no_tags_yet_create_the_first') }}</p>
      </div>

      <!-- Create/Edit modal -->
      <div
        v-if="showCreate || editing"
        class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center"
        @click.self="closeModal"
      >
        <div class="bg-white rounded-xl shadow-xl w-72 p-5">
          <h3 class="font-semibold text-gray-900 mb-4">{{ editing ? 'Edytuj tag' : 'Nowy tag' }}</h3>
          <form @submit.prevent="save" class="space-y-3">
            <div>
              <label class="label">{{ $t('common.name') }}</label>
              <input v-model="form.name" class="input" required />
            </div>
            <div>
              <label class="label">{{ $t('common.colour') }}</label>
              <div class="flex items-center gap-3">
                <input v-model="form.color" type="color" class="w-10 h-10 rounded cursor-pointer border-0 p-0.5" />
                <input v-model="form.color" class="input flex-1" placeholder="#6366f1" />
              </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
              <button type="button" @click="closeModal" class="btn-ghost text-sm">{{ $t('common.cancel') }}</button>
              <button type="submit" class="btn-primary text-sm">{{ $t('common.save') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({ tags: { type: Array, default: () => [] } })

const showCreate = ref(false)
const editing = ref(null)
const form = reactive({ name: '', color: '#6366f1' })

const edit = (t) => {
  editing.value = t
  Object.assign(form, { name: t.name, color: t.color })
}
const closeModal = () => {
  showCreate.value = false
  editing.value = null
  Object.assign(form, { name: '', color: '#6366f1' })
}
const save = () => {
  if (editing.value) router.put(route('tenant.manager.tags.update', editing.value.id), form, { onSuccess: closeModal })
  else router.post(route('tenant.manager.tags.store'), form, { onSuccess: closeModal })
}
const del = (t) => {
  if (confirm(`Usunąć tag "${t.name}"?`)) router.delete(route('tenant.manager.tags.destroy', t.id))
}
</script>
