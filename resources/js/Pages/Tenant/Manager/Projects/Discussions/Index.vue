<template>
  <ManagerLayout :title="$t('projects.project_discussions')">
    <div class="max-w-3xl space-y-5">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Link :href="route('tenant.manager.projects.show', project.id)" class="text-gray-400 hover:text-gray-600">
            <i class="fa-solid fa-arrow-left"></i>
          </Link>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $t('projects.discussions') }}</h1>
            <p class="text-sm text-gray-500">{{ project.name }}</p>
          </div>
        </div>
        <button @click="showCreate = true" class="btn-primary text-sm">
          <i class="fa-solid fa-plus mr-1"></i> {{ $t('projects.new_discussion') }}
        </button>
      </div>

      <div class="space-y-3">
        <div
          v-for="d in discussions"
          :key="d.id"
          class="bg-white rounded-xl border border-gray-200 p-5 hover:border-indigo-300 hover:shadow-sm transition-all"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="flex-1">
              <Link
                :href="route('tenant.manager.projects.discussions.show', [project.id, d.id])"
                class="text-base font-semibold text-gray-900 hover:text-indigo-600"
              >
                {{ d.title }}
              </Link>
              <p class="text-sm text-gray-500 mt-0.5 line-clamp-2">{{ d.body }}</p>
            </div>
            <div class="text-right flex-shrink-0">
              <p class="text-xs text-gray-400">{{ d.creator?.name }}</p>
              <p class="text-xs text-gray-400">{{ formatDate(d.created_at) }}</p>
            </div>
          </div>
          <div class="flex items-center gap-3 mt-3 text-xs text-gray-400">
            <span
              ><i class="fa-regular fa-comment mr-1"></i>{{ d.comments_count ?? 0 }} {{ $t('projects.comments') }}</span
            >
            <button @click="del(d)" class="ml-auto text-red-400 hover:text-red-600">{{ $t('common.delete') }}</button>
          </div>
        </div>

        <div v-if="!discussions.length" class="py-16 text-center bg-white rounded-xl border border-gray-200">
          <i class="fa-solid fa-comments text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">{{ $t('projects.no_discussions_yet_start_the_first') }}</p>
        </div>
      </div>

      <!-- Create modal -->
      <div
        v-if="showCreate"
        class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center"
        @click.self="showCreate = false"
      >
        <div class="bg-white rounded-xl shadow-xl w-[500px] p-5">
          <h3 class="font-semibold text-gray-900 mb-4">{{ $t('projects.new_discussion') }}</h3>
          <form @submit.prevent="create" class="space-y-3">
            <div>
              <label class="label">{{ $t('common.title') }}</label>
              <input v-model="form.title" class="input" required />
            </div>
            <div>
              <label class="label">{{ $t('projects.body') }}</label>
              <textarea v-model="form.body" rows="5" class="input"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
              <button type="button" @click="showCreate = false" class="btn-ghost text-sm">
                {{ $t('common.cancel') }}
              </button>
              <button type="submit" class="btn-primary text-sm">{{ $t('projects.post') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()

import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'

const props = defineProps({
  project: Object,
  discussions: { type: Array, default: () => [] },
})

const showCreate = ref(false)
const form = reactive({ title: '', body: '' })

const create = () =>
  router.post(route('tenant.manager.projects.discussions.store', props.project.id), form, {
    onSuccess: () => {
      showCreate.value = false
    },
  })
const del = (d) => {
  if (confirm(t('projects.delete_this_discussion')))
    router.delete(route('tenant.manager.projects.discussions.destroy', [props.project.id, d.id]))
}
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('pl-PL') : '—')
</script>
